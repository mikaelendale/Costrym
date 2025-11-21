<?php

namespace App\Jobs;

use App\Repositories\CostOptimizationRepository;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class CostValueAlignmentOrchestratorJob implements ShouldQueue
{
    use Queueable;

    /**
     * Backoff schedule (seconds) for retry attempts when the orchestrator fails.
     *
     * @var array<int>
     */
    public array $backoff = [30, 60];

    public function __construct(public int $userId)
    {
        $this->onQueue('alignment_orchestrator');
    }

    public function handle(CostOptimizationRepository $optRepo): void
    {
        Log::info('CostValueAlignmentOrchestratorJob: starting', ['user_id' => $this->userId]);

        $optimizerData = $optRepo->getCutCostOptimizer($this->userId) ?? [];
        $spacingSeconds = 20;
        $perCategoryChunkSize = 20;
        $delaySeconds = 0;
        $totalDispatched = 0;

        foreach ($optimizerData as $category => $data) {
            if (empty($data)) {
                continue;
            }

            // ensure list format
            $list = is_array($data) ? $data : [$data];
            $chunks = array_chunk($list, $perCategoryChunkSize);
            $totalChunks = count($chunks);
            foreach ($chunks as $i => $chunk) {
                CostValueAlignmentChunkJob::dispatch(
                    category: $category,
                    optimizerDataChunk: $chunk,
                    chunkIndex: $i,
                    totalChunks: $totalChunks,
                    userId: $this->userId,
                )->delay(now()->addSeconds($delaySeconds));

                Log::info('CostValueAlignmentOrchestratorJob: queued alignment chunk', ['category' => $category, 'chunk_index' => $i, 'delay' => $delaySeconds.'s']);
                $delaySeconds += $spacingSeconds;
                $totalDispatched++;
            }
        }

        $finalBuffer = 30;
        $finalDelay = $delaySeconds + $finalBuffer;
        Log::info('CostValueAlignmentOrchestratorJob: scheduling finalization', ['final_delay' => $finalDelay.'s', 'total_chunks' => $totalDispatched]);

        CostValueAlignmentFinalizeJob::dispatch(userId: $this->userId)->delay(now()->addSeconds($finalDelay));

        Log::info('CostValueAlignmentOrchestratorJob: completed scheduling');
    }
}
