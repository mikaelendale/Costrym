<?php

namespace App\Jobs;

use App\Services\CostDecompositionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class BenchmarkJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $userId)
    {
        $this->onQueue('benchmark_jobs');
    }

    public function handle(CostDecompositionService $costDecompositionService): void
    {
        Log::info('benchmark Job', ['user_id' => $this->userId]);
        $costDecompositionService->run($this->userId);
        // After benchmark completes, start the cost optimization orchestrator which will
        // dispatch per-category chunk jobs and finalize optimization when done.
        CostOptimizationOrchestratorJob::dispatch(userId: $this->userId)->delay(now()->addSeconds(5));
    }
}
