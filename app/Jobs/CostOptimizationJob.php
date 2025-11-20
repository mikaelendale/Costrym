<?php

namespace App\Jobs;

use App\Services\CostOptimizationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class CostOptimizationJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 300; // allow long-running LLM orchestration

    public int $tries = 1;

    public function __construct(public int $userId)
    {
        //
    }

    public function handle(CostOptimizationService $service): void
    {
        Log::info('CostOptimizationJob: starting', ['user_id' => $this->userId]);
        $result = $service->run($this->userId);

        Log::info('CostOptimizationJob: completed', [
            'cost_cut_portfolio_count' => is_array($result['cost_cut_portfolio'] ?? null) ? count($result['cost_cut_portfolio']) : 0,
            'cost_value_alignment_count' => is_array($result['cost_value_alignment'] ?? null) ? count($result['cost_value_alignment']) : 0,
        ]);
    }
}
