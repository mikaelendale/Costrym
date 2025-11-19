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

    /**
     * Run the full cost decomposition flow:
     * - Use categorized expenses to generate associated/direct cost breakdowns
     * - Build a benchmark (should-cost OPEX model)
     * - Compute CER (Cost Efficiency Ratios) comparing actual vs benchmark
     *
     * Returns a concise array of persisted results for observability.
     */
    public function run(): array
    {
        $rawExpenses = $this->expenseRepository->getExpense() ?? [];

        // Compact JSON for agent prompts
        if (is_array($rawExpenses) || $rawExpenses instanceof \JsonSerializable || $rawExpenses instanceof \Illuminate\Support\Collection) {
            $expensesJson = json_encode($rawExpenses, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } else {
            $expensesJson = (string) $rawExpenses;
        }

        // 1) Cost decomposition based on categorized expenses
        $decompPrompt = 'use categorized expense to decompose costs '.$expensesJson;
        Log::info('CostDecomposition: prepared decomposition prompt', [
            'prompt_length' => strlen($decompPrompt),
        ]);

        $decompositionResponse = CostDecompositionAgent::run($decompPrompt)->go();
        Log::info('CostDecomposition: raw cost decomposition agent response received');

        $associatedCosts = [];
        try {
            $associatedCosts = CleanUpResponse::extractJsonPayload($decompositionResponse);
        } catch (\Throwable $e) {
            Log::warning('CostDecomposition: failed to parse decomposition response, storing empty array', [
                'error' => $e->getMessage(),
            ]);
        }

        // Persist associated costs
        $persistedAssociated = $this->costDecompositionRepository->updateAssociatedCosts($associatedCosts);
        Log::info('CostDecomposition: associated costs persisted', [
            'items' => is_array($persistedAssociated) ? count($persistedAssociated) : 0,
        ]);

        // 2) Benchmarking (should-cost model). The agent can gather context via tools.
        // We pass a short nudge string; the agent will fetch context using FireCrawler + GetCompanyContext.
        $benchmarkInput = 'Build should-cost OPEX model for the current company context.';
        $benchmarkResponse = BenchmarkingAgent::run($benchmarkInput)->go();
        Log::info('CostDecomposition: benchmarking agent response captured');

        // 3) Compute actual OPEX by category percent from categorized expenses
        $byCategoryPercent = $this->computeOpexByCategoryPercent($rawExpenses);
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

        $persistedCer = $this->costDecompositionRepository->updateCER($cerParsed);
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
    protected function computeOpexByCategoryPercent(array $expenses): array
    {
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
