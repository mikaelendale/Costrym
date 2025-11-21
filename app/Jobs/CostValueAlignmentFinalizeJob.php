<?php

namespace App\Jobs;

use App\Repositories\CostOptimizationRepository;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class CostValueAlignmentFinalizeJob implements ShouldQueue
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
        $this->onQueue('alignment_finalize');
    }

    public function handle(CostOptimizationRepository $optRepo): void
    {
        Log::info('CostValueAlignmentFinalizeJob: starting final aggregation', ['user_id' => $this->userId]);

        // Collect per-category alignment results that were persisted by chunk jobs.
        $perCategory = $optRepo->getCostValueAlignment($this->userId) ?? [];

        // Persist an aggregated top-level alignment record (keeps same shape but ensures
        // a single canonical `costValueAlignment` CompanyData entry exists).
        $aggregated = is_array($perCategory) ? $perCategory : [];
        $persisted = $optRepo->updateCostValueAlignment($aggregated, $this->userId);

        Log::info('CostValueAlignmentFinalizeJob: completed aggregation', [
            'categories_finalized' => count(array_keys($persisted ?? [])),
        ]);

        // Trigger the next stage: Automation Planning
        $delay = 5; // small buffer
        AutomationPlanningOrchestratorJob::dispatch(userId: $this->userId)->delay(now()->addSeconds($delay));
        Log::info('CostValueAlignmentFinalizeJob: dispatched AutomationPlanningOrchestratorJob', ['delay' => $delay.'s']);
    }
}
