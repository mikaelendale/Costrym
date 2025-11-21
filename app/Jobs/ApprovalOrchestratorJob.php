<?php

namespace App\Jobs;

use App\Repositories\AutomationRepository;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ApprovalOrchestratorJob implements ShouldQueue
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
        $this->onQueue('approval_orchestrator');
    }

    public function handle(AutomationRepository $autoRepo): void
    {
        Log::info('ApprovalOrchestratorJob: starting', ['user_id' => $this->userId]);

        // Use the persisted automation plans (per category) as inputs for approval generation
        $automations = $autoRepo->getAutomations($this->userId) ?? [];

        $spacingSeconds = 20;
        $perCategoryChunkSize = 10;
        $delaySeconds = 0;
        $totalDispatched = 0;

        foreach ($automations as $category => $plans) {
            if (empty($plans)) {
                continue;
            }

            $list = is_array($plans) ? $plans : [$plans];
            $chunks = array_chunk($list, $perCategoryChunkSize);
            $totalChunks = count($chunks);

            foreach ($chunks as $i => $chunk) {
                ApprovalChunkJob::dispatch(
                    category: $category,
                    plansChunk: $chunk,
                    chunkIndex: $i,
                    totalChunks: $totalChunks,
                    userId: $this->userId,
                )->delay(now()->addSeconds($delaySeconds));

                Log::info('ApprovalOrchestratorJob: queued approval chunk', [
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
        Log::info('ApprovalOrchestratorJob: scheduling finalization', [
            'final_delay' => $finalDelay.'s',
            'total_chunks' => $totalDispatched,
        ]);

        ApprovalFinalizeJob::dispatch(userId: $this->userId)
            ->delay(now()->addSeconds($finalDelay));

        Log::info('ApprovalOrchestratorJob: completed scheduling');
    }
}
