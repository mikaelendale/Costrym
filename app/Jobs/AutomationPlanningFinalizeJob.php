<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class AutomationPlanningFinalizeJob implements ShouldQueue
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
        $this->onQueue('automation_planning_finalize');
    }

    public function handle(): void
    {
        Log::info('AutomationPlanningFinalizeJob: starting final aggregation', ['user_id' => $this->userId]);

        // At this point per-category automation plans should be persisted by chunk jobs.
        // Now schedule the approval orchestrator to convert plans into approval requests.
        $delay = 5; // small buffer
        ApprovalOrchestratorJob::dispatch(userId: $this->userId)->delay(now()->addSeconds($delay));

        Log::info('AutomationPlanningFinalizeJob: dispatched ApprovalOrchestratorJob', ['delay' => $delay.'s']);
    }
}
