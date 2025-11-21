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
    public function __construct(
        private ExpenseRepository $expenseRepository,
        private CostDecompositionRepository $costDecompositionRepository,
    ) {
        //
    }

    public function run(?int $userId = null): array
    {
        $expenses = $this->expenseRepository->getDirectCosts($userId) ?? [];
        $totalExpenses = is_array($expenses) ? count($expenses) : 0;
        Log::info('CostDecomposition: starting chunked decomposition run', [
            'expense_count' => $totalExpenses,
        ]);

        // Guard: nothing to do
        if ($totalExpenses === 0) {
            Log::info('CostDecomposition: no direct costs found; skipping decomposition phase');
        } else {
            $chunkSize = 30; // tune as needed for token limits / performance
            $chunks = array_chunk($expenses, $chunkSize);
            $totalChunks = count($chunks);

            foreach ($chunks as $i => $chunk) {
                $chunkJson = json_encode($chunk, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $prompt = 'Cost decomposition chunk '.($i + 1).' of '.$totalChunks.'; using ONLY this subset of direct costs. Return JSON per schema. DirectCostsSubset: '.$chunkJson;
                Log::info('CostDecomposition: prepared chunk prompt', [
                    'chunk_index' => $i,
                    'prompt_length' => strlen($prompt),
                    'chunk_size' => count($chunk),
                ]);

                $raw = CostDecompositionAgent::run($prompt)->go();
                Log::info('CostDecomposition: raw agent response for chunk', [
                    'chunk_index' => $i,
                    // Avoid logging entire payload if huge; length only
                    'response_length' => is_string($raw) ? strlen($raw) : 0,
                ]);

                $parsed = [];
                $productDecompositions = [];
                try {
                    $parsed = CleanUpResponse::extractJsonPayload($raw);
                    $productDecompositions = $parsed['cost_decomposition_response']['product_decompositions'] ?? [];
                } catch (\Throwable $e) {
                    Log::warning('CostDecomposition: parse failure for chunk', [
                        'chunk_index' => $i,
                        'error' => $e->getMessage(),
                    ]);
                }

                if (! empty($productDecompositions)) {
                    $merged = $this->costDecompositionRepository->updateAssociatedCosts($productDecompositions, $userId);
                    Log::info('CostDecomposition: merged associated costs after chunk', [
                        'chunk_index' => $i,
                        'cumulative_products' => is_array($merged) ? count($merged) : 0,
                    ]);
                } else {
                    Log::info('CostDecomposition: no product_decompositions produced for chunk', [
                        'chunk_index' => $i,
                    ]);
                }
            }
        }

        // Retrieve full merged associated costs after all chunks
        $persistedAssociated = $this->costDecompositionRepository->getassociatedCosts($userId);
        Log::info('CostDecomposition: final associated costs count', [
            'items' => is_array($persistedAssociated) ? count($persistedAssociated) : 0,
        ]);

        // 2) Benchmarking (should-cost model). The agent can gather context via tools.
        // We pass a short nudge string; the agent will fetch context using FireCrawler + GetCompanyContext.
        $benchmarkInput = 'Build should-cost OPEX model for the current company context.';
        $benchmarkResponse = BenchmarkingAgent::run($benchmarkInput)->go();
        Log::info('CostDecomposition: benchmarking agent response captured');

        // 3) Compute actual OPEX by category percent from categorized expenses
        $byCategoryPercent = $this->computeOpexByCategoryPercent($userId);
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
            'associated_costs' => $persistedAssociated,
            'benchmark' => $benchmarkResponse,
            'cer' => $persistedCer,
        ];
    }

    /**
     * Compute OPEX by category percent from an expenses list.
     * Accepts a flat array of expense rows. Each row may contain:
     * - amount (numeric)
     * - category (string) optional; defaults to expense_name if absent
     */
    protected function computeOpexByCategoryPercent($userId): array
    {

        $expenses = $this->expenseRepository->getExpense($userId) ?? [];
        $sumByCategory = [];
        $total = 0.0;

        foreach ($expenses as $row) {
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
