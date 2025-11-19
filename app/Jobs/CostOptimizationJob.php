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

    public function __construct()
    {
        //
    }

    public function handle(CostOptimizationService $service): void
    {
        Log::info('CostOptimizationJob: starting');
        $result = $service->run();
        Log::info('CostOptimizationJob: completed', [
            'cut_cost_optimizer_count' => is_array($result['cut_cost_optimizer'] ?? null) ? count($result['cut_cost_optimizer']) : 0,
            'cost_value_alignment_count' => is_array($result['cost_value_alignment'] ?? null) ? count($result['cost_value_alignment']) : 0,
        ]);
    }
}
