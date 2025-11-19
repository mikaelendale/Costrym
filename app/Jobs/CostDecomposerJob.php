<?php

namespace App\Jobs;

use App\Services\CostDecompositionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class CostDecomposerJob implements ShouldQueue
{
    use Queueable;

    /**
     * Allow this job to run longer than the default 60s spawned by queue:listen children.
     * Note: This is honored by queue:work; queue:listen may still need an explicit --timeout flag.
     */
    public int $timeout = 300; // seconds

    public int $tries = 1;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(CostDecompositionService $service): void
    {
        Log::info('CostDecomposerJob: starting');
        $result = $service->run();
        Log::info('CostDecomposerJob: completed', [
            'associated_costs_count' => is_array($result['associated_costs'] ?? null) ? count($result['associated_costs']) : 0,
            'cer_items' => is_array($result['cer'] ?? null) ? count($result['cer']) : 0,
        ]);
    }
}
