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
    public function run(int $userId): array
    {
        $rawData = $this->expenseRepository->getExpense($userId) ?? [];
        $categoryAgentResponse = $this->expenseRepository->getExpensesGroupedByCategory($userId) ?? [];
        $benchMarkData = $this->costDecompositionRepository->getCER($userId) ?? [];

        // Build structured payload matching the CostOptimizer agent instruction
        $payload = json_encode([
            'rawData' => $rawData,
            'categoryAgentResponse' => $categoryAgentResponse,
            'benchMarkData' => $benchMarkData,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        Log::info('CostOptimization: prepared optimizer payload', ['payload_length' => strlen($payload)]);

        $optimizerRaw = CostOptomizerAgent::run($payload)->go();
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

    /**
     * Process a single chunk of rows for a given category. Called by CostOptimizationChunkJob.
     * The method will call the optimizer agent with the category context and the chunk rows,
     * parse the response and merge it into the per-category optimization store.
     *
     * @param  array<int,mixed>  $rows
     */
    public function processCategoryChunk(string $category, array $rows, int $userId, int $chunkIndex = 0, int $totalChunks = 1): void
    {
        // Build structured payload per agent instruction. Supply expense data
        // scoped to this category as `rawData` (user requested per-category rawData),
        // keep categoryAgentResponse keyed by category and include benchmark/CER.
        $rawDataForCategory = [$category => $rows];
        $benchMarkData = $this->costDecompositionRepository->getCER($userId) ?? [];

        $payload = json_encode([
            'rawData' => $rawDataForCategory,
            'benchMarkData' => $benchMarkData,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        Log::info('CostOptimization: calling optimizer for category chunk', [
            'category' => $category,
            'chunk_index' => $chunkIndex,
            'total_chunks' => $totalChunks,
            'rows_count' => count($rows),
            'payload_length' => strlen($payload),
        ]);

        $raw = CostOptomizerAgent::run($payload)->go();
        Log::info('CostOptimization: optimizer raw chunk response', [
            'category' => $category,
            'response_length' => is_string($raw) ? strlen($raw) : 0,
        ]);

        $parsed = [];
        $newData = [];
        try {
            $parsed = CleanUpResponse::extractJsonPayload($raw);
            $newData = $parsed['cost_cut_portfolio'] ?? [];
        } catch (\Throwable $e) {
            Log::warning('CostOptimization: failed to parse optimizer chunk response', ['category' => $category, 'error' => $e->getMessage()]);

            return;
        }

        // Merge with existing per-category optimizer data
        $existingAll = $this->costOptimizationRepository->getCutCostOptimizer($userId) ?? [];
        $existingCategory = is_array($existingAll[$category] ?? null) ? $existingAll[$category] : [];

        // If both are lists, append; else merge associative arrays with new values overwriting
        $merged = [];
        $isExistingList = array_keys($existingCategory) === range(0, count($existingCategory) - 1);
        $isNewList = array_keys($newData) === range(0, count($newData) - 1);
        if ($isExistingList && $isNewList) {
            $merged = array_values(array_merge($existingCategory, $newData));
        } else {
            $merged = array_merge(is_array($existingCategory) ? $existingCategory : [], is_array($newData) ? $newData : []);
        }

        $updated = $this->costOptimizationRepository->updateCutCostOptimizerByCategory($category, $merged, $userId);
        Log::info('CostOptimization: merged and persisted category optimizer data', ['category' => $category, 'cumulative_items' => is_array($updated[$category] ?? null) ? count($updated[$category]) : 0]);
    }

    /**
     * Finalize optimization across categories: gather per-category optimizer outputs,
     * run the value aligner over the aggregated optimizer output and persist results.
     */
    public function finalizeOptimization(int $userId): array
    {
        $allOptimized = $this->costOptimizationRepository->getCutCostOptimizer($userId) ?? [];
        $payload = json_encode($allOptimized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        Log::info('CostOptimization: running final alignment over aggregated optimizer data', ['user_id' => $userId, 'payload_length' => strlen($payload)]);

        $alignmentRaw = CostValueAlignerAgent::run('cutcostoptimizer: '.$payload)->go();
        Log::info('CostOptimization: value aligner raw response', ['response_length' => is_string($alignmentRaw) ? strlen($alignmentRaw) : 0]);

        $alignmentParsed = [];
        try {
            $alignmentParsed = CleanUpResponse::extractJsonPayload($alignmentRaw);
        } catch (\Throwable $e) {
            Log::warning('CostOptimization: failed to parse alignment response', ['error' => $e->getMessage()]);
        }

        $persistedAlignment = $this->costOptimizationRepository->updateCostValueAlignment($alignmentParsed, $userId);

        return [
            'cost_cut_portfolio' => $allOptimized,
            'cost_value_alignment' => $persistedAlignment,
        ];
    }

    /**
     * Process a chunk of optimizer outputs for alignment. Called by CostValueAlignmentChunkJob.
     * This will call the value aligner agent with the chunk and persist per-category alignment results.
     *
     * @param  array<int,mixed>  $optimizerChunk
     */
    public function processAlignmentChunk(string $category, array $optimizerChunk, int $userId, int $chunkIndex = 0, int $totalChunks = 1): void
    {
        $payload = json_encode($optimizerChunk, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $prompt = 'Perform value alignment for category "'.$category.'" using ONLY this optimizer output slice. Return JSON cost_value_alignment for this category. Data: '.$payload;
        Log::info('CostOptimization: calling value aligner for category chunk', [
            'category' => $category,
            'chunk_index' => $chunkIndex,
            'total_chunks' => $totalChunks,
            'prompt_length' => strlen($prompt),
        ]);

        $raw = CostValueAlignerAgent::run($prompt)->go();
        Log::info('CostOptimization: raw alignment chunk response', ['category' => $category, 'response_length' => is_string($raw) ? strlen($raw) : 0]);

        $parsed = [];
        $newAlignment = [];
        try {
            $parsed = CleanUpResponse::extractJsonPayload($raw);
            $newAlignment = $parsed['cost_value_alignment'] ?? [];
        } catch (\Throwable $e) {
            Log::warning('CostOptimization: failed to parse alignment chunk response', ['category' => $category, 'error' => $e->getMessage()]);

            return;
        }

        // Merge with existing per-category alignment
        $existingAll = $this->costOptimizationRepository->getCostValueAlignment($userId) ?? [];
        $existingCategory = is_array($existingAll[$category] ?? null) ? $existingAll[$category] : [];

        $isExistingList = array_keys($existingCategory) === range(0, count($existingCategory) - 1);
        $isNewList = array_keys($newAlignment) === range(0, count($newAlignment) - 1);
        if ($isExistingList && $isNewList) {
            $merged = array_values(array_merge($existingCategory, $newAlignment));
        } else {
            $merged = array_merge(is_array($existingCategory) ? $existingCategory : [], is_array($newAlignment) ? $newAlignment : []);
        }

        $updated = $this->costOptimizationRepository->updateCostValueAlignmentByCategory($category, $merged, $userId);
        Log::info('CostOptimization: merged and persisted category alignment data', ['category' => $category, 'cumulative_items' => is_array($updated[$category] ?? null) ? count($updated[$category]) : 0]);
    }
}
