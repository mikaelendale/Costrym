<?php

namespace App\Services;

use App\Agents\BenchmarkingAgent;
use App\Agents\CERAgent;
use App\Agents\CostDecompositionAgent;
use App\Repositories\CostDecompositionRepository;
use App\Repositories\ExpenseRepository;
use Illuminate\Support\Facades\Log;

class CostDecompositionService
{
    private $expenses;

    public function __construct(
        private ExpenseRepository $expenseRepository,
        private CostDecompositionRepository $costDecompositionRepository,
    ) {
        //
    }

    /**
     * Run the full cost decomposition flow:
     * - Use categorized expenses to generate associated/direct cost breakdowns
     * - Build a benchmark (should-cost OPEX model)
     * - Compute CER (Cost Efficiency Ratios) comparing actual vs benchmark
     *
     * Returns a concise array of persisted results for observability.
     */
    public function run(?int $userId = null): array
    {
        $rawExpenses = $this->expenseRepository->getExpense($userId) ?? [];

        // Chunk expenses into groups of 10
        $chunks = array_chunk(is_array($this->expenses) ? $this->expenses : [], 10);

        $allDecompositions = [];
        $persistedAssociated = [];

        foreach ($chunks as $chunk) {
            // Compact JSON for agent prompts
            $expensesJson = json_encode($chunk, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            Log::info('CostDecomposition: starting decomposition run for chunk', [
                'chunk_size' => count($chunk),
            ]);

            // 1) Cost decomposition based on categorized expenses
            $decompPrompt = 'use categorized expense to decompose costs '.$expensesJson;
            Log::info('CostDecomposition: prepared decomposition prompt', [
                'prompt_length' => strlen($decompPrompt),
            ]);

            $decompositionResponse = CostDecompositionAgent::run($decompPrompt)->go();

            Log::info('CostDecomposition: raw decomposition response', [
                'response' => $decompositionResponse,
            ]);

            try {
                $associatedCosts = CleanUpResponse::extractJsonPayload($decompositionResponse);
                $data = $associatedCosts['cost_decomposition_response']['product_decompositions'] ?? [];
            } catch (\Throwable $e) {
                Log::warning('CostDecomposition: failed to parse decomposition response, storing empty array', [
                    'error' => $e->getMessage(),
                ]);
                $data = [];
            }

            // Collect decompositions from each chunk
            if (! empty($data)) {
                $allDecompositions = array_merge($allDecompositions, $data);

                // Persist associated costs immediately for this chunk
                $persistedThisChunk = $this->costDecompositionRepository->updateAssociatedCosts($data, $userId);

                Log::info('CostDecomposition: persisted associated costs for chunk', [
                    'chunk_size' => count($data),
                    'persisted_items' => is_array($persistedThisChunk) ? count($persistedThisChunk) : 0,
                ]);

                if (is_array($persistedThisChunk)) {
                    $persistedAssociated = array_merge($persistedAssociated, $persistedThisChunk);
                }
            }
            sleep(15);
        }

        Log::info('CostDecomposition: associated costs persisted', [
            'items' => is_array($persistedAssociated) ? count($persistedAssociated) : 0,
        ]);

        return $this->benchmarking();
    }

    public function benchmarking()
    {

        $benchmarkInput = 'Build should-cost OPEX model for the current company context.';
        $benchmarkResponse = BenchmarkingAgent::run($benchmarkInput)->go();
        Log::info('CostDecomposition: benchmarking agent response captured');

        // 3) Compute actual OPEX by category percent from categorized expenses
        $byCategoryPercent = $this->computeOpexByCategoryPercent();
        Log::info('CostDecomposition: computed actual OPEX by category percent', [
            'by_category_percent' => $byCategoryPercent,
        ]);

        // 4) CER (Cost Efficiency Ratios): actual vs benchmark
        $cerPayload = json_encode([
            'actual_opex' => $byCategoryPercent,
            'benchmark' => $benchmarkResponse,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $cerResponse = CERAgent::run($cerPayload)->go();

        $cerParsed = [];
        try {
            $cerParsed = CleanUpResponse::extractJsonPayload($cerResponse);
        } catch (\Throwable $e) {
            Log::warning('CostDecomposition: failed to parse CER response, storing empty array', [
                'error' => $e->getMessage(),
            ]);
        }

        $persistedCer = $this->costDecompositionRepository->updateCER($cerParsed, $userId);
        Log::info('CostDecomposition: CER data persisted', [
            'items' => is_array($persistedCer) ? count($persistedCer) : 0,
        ]);

        return [
            'benchmark' => $benchmarkResponse,
            'cer' => $persistedCer,
        ];
    }

    protected function computeOpexByCategoryPercent(): array
    {

        $this->expenses = $this->expenseRepository->getExpense() ?? [];

        $sumByCategory = [];
        $total = 0.0;

        foreach ($this->expenses as $row) {
            if (! is_array($row)) {
                continue;
            }
            $amount = (float) ($row['amount'] ?? 0);
            if ($amount === 0.0) {
                continue;
            }
            $cat = trim((string) ($row['category'] ?? ($row['expense_name'] ?? 'Uncategorized')));
            $cat = $cat !== '' ? $cat : 'Uncategorized';
            $sumByCategory[$cat] = ($sumByCategory[$cat] ?? 0) + $amount;
            $total += $amount;
        }

        if ($total <= 0) {
            return [];
        }

        // Convert to percent map with two decimals
        $byPercent = [];
        foreach ($sumByCategory as $cat => $amt) {
            $byPercent[$cat] = round(($amt / $total) * 100, 2);
        }

        return $byPercent;
    }
}
