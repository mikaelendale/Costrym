<?php

namespace App\Jobs;

use App\Services\AutomationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class AutomationJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 300;

    public int $tries = 1;

    public function __construct(public ?int $userId = null)
    {
        //
    }

    public function handle(AutomationService $service): void
    {
        Log::info('AutomationJob: starting', ['user_id' => $this->userId]);
        $result = $service->run($this->userId);
        Log::info('AutomationJob: completed', [
            'automations_count' => is_array($result['automations'] ?? null) ? count($result['automations']) : 0,
            'approval_items' => is_array($result['approvalLayer'] ?? null) ? count($result['approvalLayer']) : 0,
        ]);
    }
}
