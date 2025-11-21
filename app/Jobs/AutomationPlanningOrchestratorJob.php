<?php

namespace App\Jobs;

use App\Repositories\CostOptimizationRepository;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class AutomationPlanningOrchestratorJob implements ShouldQueue
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
        $this->onQueue('automation_planning_orchestrator');
    }

    public function handle(CostOptimizationRepository $optRepo): void
    {
        Log::info('AutomationPlanningOrchestratorJob: starting', ['user_id' => $this->userId]);

        // Use the persisted cost value alignment results as inputs for planning
        $alignment = $optRepo->getCostValueAlignment($this->userId) ?? [];

        $spacingSeconds = 20;
        $perCategoryChunkSize = 10;
        $delaySeconds = 0;
        $totalDispatched = 0;

        foreach ($alignment as $category => $data) {
            if (empty($data)) {
                continue;
            }

            $list = is_array($data) ? $data : [$data];
            $chunks = array_chunk($list, $perCategoryChunkSize);
            $totalChunks = count($chunks);

            foreach ($chunks as $i => $chunk) {
                AutomationPlanningChunkJob::dispatch(
                    category: $category,
                    alignedDataChunk: $chunk,
                    chunkIndex: $i,
                    totalChunks: $totalChunks,
                    userId: $this->userId,
                )->delay(now()->addSeconds($delaySeconds));

                Log::info('AutomationPlanningOrchestratorJob: queued planner chunk', [
                    'category' => $category,
                    'chunk_index' => $i,
                    'delay' => $delaySeconds.'s',
                ]);

                $delaySeconds += $spacingSeconds;
                $totalDispatched++;
            }
        }

        $finalBuffer = 30;
        $finalDelay = $delaySeconds + $finalBuffer;
        Log::info('AutomationPlanningOrchestratorJob: scheduling finalization', [
            'final_delay' => $finalDelay.'s',
            'total_chunks' => $totalDispatched,
        ]);

        AutomationPlanningFinalizeJob::dispatch(userId: $this->userId)
            ->delay(now()->addSeconds($finalDelay));

        Log::info('AutomationPlanningOrchestratorJob: completed scheduling');
    }
}
