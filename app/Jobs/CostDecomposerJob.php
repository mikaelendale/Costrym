<?php

namespace App\Jobs;

use App\Repositories\ExpenseRepository;
use App\Services\CostDecompositionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class CostDecomposerJob implements ShouldQueue
{
    use Queueable;

    /**
     * Allow this job to run longer than the default 60s spawned by queue:listen children.
     * Note: This is honored by queue:work; queue:listen may still need an explicit --timeout flag.
     */
    public int $timeout = 300; // seconds

    public int $tries = 1;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $userId)
    {
        $this->onQueue('cost_decomposer_jobs');
    }

    /**
     * Execute the job.
     */
    public function handle(CostDecompositionService $service, ExpenseRepository $expenseRepository): void
    {
        Log::info('CostDecomposerJob: orchestrator starting');

        $directCosts = $expenseRepository->getDirectCosts($this->userId) ?? [];
        $total = is_array($directCosts) ? count($directCosts) : 0;
        if ($total === 0) {
            Log::info('CostDecomposerJob: no direct costs found; scheduling immediate finalization');
            // Run finalization only (benchmark + CER)
            // $result = $service->run($this->userId);
            // Log::info('CostDecomposerJob: completed (finalization only)', [
            //     'associated_costs_count' => is_array($result['associated_costs'] ?? null) ? count($result['associated_costs']) : 0,
            //     'cer_items' => is_array($result['cer'] ?? null) ? count($result['cer']) : 0,
            // ]);

            return;
        }

        $chunkSize = 15; // same philosophy as categorization chunking
        $spacingSeconds = 30; // TPM spacing
        $bufferAfterLastChunkSeconds = 45; // extra buffer before finalization
        $chunks = array_chunk($directCosts, $chunkSize);
        $totalChunks = count($chunks);

        Log::info('CostDecomposerJob: scheduling chunk jobs', [
            'total_direct_costs' => $total,
            'chunk_size' => $chunkSize,
            'total_chunks' => $totalChunks,
            'spacing_seconds' => $spacingSeconds,
        ]);

        $delaySeconds = 0;
        foreach ($chunks as $i => $chunk) {
            CostDecompositionChunkJob::dispatch(
                directCostsChunk: $chunk,
                chunkIndex: $i,
                totalChunks: $totalChunks,
                userId: $this->userId,
            )->delay(now()->addSeconds($delaySeconds));

            Log::info('CostDecomposerJob: queued CostDecompositionChunkJob', [
                'chunk_index' => $i,
                'delay' => $delaySeconds.'s',
                'chunk_count' => count($chunk),
            ]);

            $delaySeconds += $spacingSeconds;
        }

        // Schedule finalization after last chunk with buffer
        $finalizationDelay = $delaySeconds + $bufferAfterLastChunkSeconds;
        // Reuse service->run() for finalization (benchmark + CER + gather merged associated costs)
        Log::info('CostDecomposerJob: scheduling finalization', [
            'finalization_delay' => $finalizationDelay.'s',
        ]);

        Log::info('CostDecomposerJob: orchestration scheduled all chunk and finalization jobs');

        BenchmarkJob::dispatch(userId: $this->userId)->delay(now()->addSeconds($finalizationDelay));

        //  CostOptimizationJob::dispatch(userId: $userId)->delay(now()->addSeconds($startAfterSeconds + 2 * $spacingBetweenJobsSeconds));
    }
}
