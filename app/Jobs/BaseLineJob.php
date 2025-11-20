<?php

namespace App\Jobs;

use App\Services\BaseLineService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class BaseLineJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $userId)
    {
        $this->onQueue('baseline_jobs');
    }

    public function handle(BaseLineService $baseLineService): void
    {
        Log::info('baseline Job', ['user_id' => $this->userId]);
        $baseLineService->run($this->userId);
    }
}
