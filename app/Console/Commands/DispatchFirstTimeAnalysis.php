<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DispatchFirstTimeAnalysis extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dispatch:first-time-analysis {user_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch FirstTimeCostAnalysisJob for a user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->argument('user_id');
        
        // Delete existing analysis
        \App\Models\Automation::where('user_id', $userId)
            ->where('type', 'first_time_cost_analysis')
            ->delete();
        
        // Dispatch job
        \App\Jobs\FirstTimeCostAnalysisJob::dispatch((int)$userId);
        
        $this->info("FirstTimeCostAnalysisJob dispatched for user {$userId}");
    }
}
