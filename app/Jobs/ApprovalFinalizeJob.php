<?php

namespace App\Jobs;

use App\Repositories\AutomationRepository;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ApprovalFinalizeJob implements ShouldQueue
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
        $this->onQueue('approval_finalize');
    }

    public function handle(AutomationRepository $autoRepo): void
    {
        Log::info('ApprovalFinalizeJob: starting final aggregation', ['user_id' => $this->userId]);

        // Gather per-category approval requests (persisted by chunk jobs)
        $perCategory = $autoRepo->getApprovalLayer($this->userId) ?? [];

        // Persist a canonical top-level approval layer record (keeps same shape)
        $aggregated = is_array($perCategory) ? $perCategory : [];
        $persisted = $autoRepo->updateApprovalLayer($aggregated, $this->userId);

        Log::info('ApprovalFinalizeJob: completed aggregation', [
            'categories_finalized' => is_array($persisted) ? count(array_keys($persisted)) : 0,
        ]);
    }
}
