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
    }
}
