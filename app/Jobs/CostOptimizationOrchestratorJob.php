<?php

namespace App\Jobs;

use App\Repositories\CategoryRepository;
use App\Services\CostOptimizationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class CostOptimizationOrchestratorJob implements ShouldQueue
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
        $this->onQueue('optimization_orchestrator');
    }

    public function handle(CostOptimizationService $service, CategoryRepository $categoryRepository): void
    {
        Log::info('CostOptimizationOrchestratorJob: starting', ['user_id' => $this->userId]);

        $categories = $categoryRepository->getCategoryNamesAndDescriptions();
        $spacingSeconds = 20; // space category jobs to avoid bursting tokens
        $perCategoryChunkSize = 20; // split large category expense lists
        $delaySeconds = 0;

        $totalCategories = count($categories);
        Log::info('CostOptimizationOrchestratorJob: categories to consider', ['count' => $totalCategories]);

        $totalDispatched = 0;
        foreach ($categories as $cat) {
            $name = $cat['name'] ?? null;
            if (! $name) {
                continue;
            }

            $rows = $categoryRepository->getExpensesByCategory($name, $this->userId);
            if (empty($rows)) {
                continue;
            }

            $chunks = array_chunk($rows, $perCategoryChunkSize);
            $totalChunks = count($chunks);
            foreach ($chunks as $i => $chunk) {
                CostOptimizationChunkJob::dispatch(
                    category: $name,
                    rows: $chunk,
                    chunkIndex: $i,
                    totalChunks: $totalChunks,
                    userId: $this->userId,
                )->delay(now()->addSeconds($delaySeconds));

                Log::info('CostOptimizationOrchestratorJob: queued CostOptimizationChunkJob', [
                    'category' => $name,
                    'chunk_index' => $i,
                    'delay' => $delaySeconds.'s',
                    'rows' => count($chunk),
                ]);

                $delaySeconds += $spacingSeconds;
                $totalDispatched++;
            }
        }

        $buffer = 30;
        $finalDelay = $delaySeconds + $buffer;
        Log::info('CostOptimizationOrchestratorJob: scheduling finalization', ['final_delay' => $finalDelay.'s', 'total_chunk_jobs' => $totalDispatched]);

        CostOptimizationFinalizeJob::dispatch(userId: $this->userId)->delay(now()->addSeconds($finalDelay));

        Log::info('CostOptimizationOrchestratorJob: completed scheduling');
    }
}
