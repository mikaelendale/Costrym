<?php

namespace App\Services;

use App\Agents\CostOptomizerAgent\CostOptomizerAgent;
use App\Agents\CostValueAlignerAgent;
use App\Repositories\CostDecompositionRepository;
use App\Repositories\CostOptimizationRepository;
use App\Repositories\ExpenseRepository;
use Illuminate\Support\Facades\Log;

class CostOptimizationService
{
    public function __construct(
        private ExpenseRepository $expenseRepository,
        private CostDecompositionRepository $costDecompositionRepository,
        private CostOptimizationRepository $costOptimizationRepository,
    ) {
        //
    }

    /**
     * Orchestrate cost optimization (cut cost portfolio) followed by cost-to-value alignment.
     * Returns array with both persisted structures.
     */
    public function run(?int $userId = null): array
    {
        $expenses = $this->expenseRepository->getExpense($userId) ?? [];
        $cer = $this->costDecompositionRepository->getCER($userId) ?? [];

        $expensesJson = json_encode($expenses, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $cerJson = json_encode($cer, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        // Prompt construction mirroring ExpenseIngestionService pattern (raw categorized + benchmark/CER)
        $optPrompt = 'categoryAgentResponse: '.$expensesJson.' benchMarkData '.$cerJson;
        Log::info('CostOptimization: prepared optimizer prompt', ['length' => strlen($optPrompt)]);

        $optimizerRaw = CostOptomizerAgent::run($optPrompt)->go();
        Log::info('CostOptimization: optimizer agent raw response received', [
            'response_length' => is_string($optimizerRaw) ? strlen($optimizerRaw) : 0,
        ]);

        $optimizerParsed = [];
        try {
            $optimizerParsed = CleanUpResponse::extractJsonPayload($optimizerRaw);
            $data = $optimizerParsed['cost_cut_portfolio'] ?? [];
        } catch (\Throwable $e) {
            Log::warning('CostOptimization: failed to parse optimizer response, storing empty array', ['error' => $e->getMessage()]);
        }
        $persistedOptimizer = $this->costOptimizationRepository->updateCutCostOptimizer($data, $userId);
        Log::info('CostOptimization: cut cost optimizer data persisted', [
            'items' => is_array($persistedOptimizer) ? count($persistedOptimizer) : 0,
        ]);

        // Alignment step uses raw optimizer output (mirrors previous prompt style)
        $alignmentPrompt = 'cutcostoptimizer: '.(is_string($optimizerRaw) ? $optimizerRaw : json_encode($optimizerRaw, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $alignmentRaw = CostValueAlignerAgent::run($alignmentPrompt)->go();
        Log::info('CostOptimization: value aligner agent raw response received', [
            'response_length' => is_string($alignmentRaw) ? strlen($alignmentRaw) : 0,
        ]);

        $alignmentParsed = [];
        try {
            $alignmentParsed = CleanUpResponse::extractJsonPayload($alignmentRaw);
        } catch (\Throwable $e) {
            Log::warning('CostOptimization: failed to parse value alignment response, storing empty array', ['error' => $e->getMessage()]);
        }
        $persistedAlignment = $this->costOptimizationRepository->updateCostValueAlignment($alignmentParsed, $userId);
        Log::info('CostOptimization: cost value alignment data persisted', [
            'items' => is_array($persistedAlignment) ? count($persistedAlignment) : 0,
        ]);

        return [
            'cost_cut_portfolio' => $persistedOptimizer,
            'cost_value_alignment' => $persistedAlignment,
        ];
    }
}
