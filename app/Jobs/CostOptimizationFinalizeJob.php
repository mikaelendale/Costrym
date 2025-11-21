<?php

namespace App\Jobs;

use App\Services\CostOptimizationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class CostOptimizationFinalizeJob implements ShouldQueue
{
    use Queueable;

    /**
     * Backoff schedule (seconds) for retry attempts on finalize.
     *
     * @var array<int>
     */
    public array $backoff = [30, 60];

    public function __construct(public int $userId)
    {
        $this->onQueue('optimization_finalize');
    }

    public function handle(CostOptimizationService $service): void
    {
        Log::info('CostOptimizationFinalizeJob: starting finalization (mark optimizer complete)', ['user_id' => $this->userId]);

        CostValueAlignmentOrchestratorJob::dispatch(userId: $this->userId)->delay(now()->addSeconds(5));

        Log::info('CostOptimizationFinalizeJob: dispatched CostValueAlignmentOrchestratorJob');
    }
}
