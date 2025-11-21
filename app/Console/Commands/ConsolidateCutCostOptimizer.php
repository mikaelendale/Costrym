<?php

namespace App\Console\Commands;

use App\Models\CompanyData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ConsolidateCutCostOptimizer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cost:consolidate-cutcost {--dry-run : Show what would be done without changing the DB}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Consolidate legacy cut cost optimizer records into a single `cutCostOptimizer` record per user.';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $names = ['cutCostOptimizer', 'cut_cost_optimizer'];

        $this->info('Scanning CompanyData for legacy cut cost optimizer records...');

        $records = CompanyData::whereIn('name', $names)->get()->groupBy('user_id');

        if ($records->isEmpty()) {
            $this->info('No matching records found.');

            return 0;
        }

        foreach ($records as $userId => $group) {
            $this->info("Processing user_id={$userId}, records_count={$group->count()}");

            $merged = [];

            foreach ($group as $rec) {
                $data = $rec->data ?? [];
                $data = is_array($data) ? $data : (is_string($data) ? json_decode($data, true) ?? [] : []);

                foreach ($data as $category => $entries) {
                    if (! is_array($entries)) {
                        $entries = [$entries];
                    }

                    if (! array_key_exists($category, $merged) || ! is_array($merged[$category])) {
                        $merged[$category] = [];
                    }

                    $merged[$category] = array_merge($merged[$category], $entries);
                }
            }

            if ($dry) {
                $this->line("[dry-run] Would consolidate for user {$userId}: categories=".implode(', ', array_keys($merged)).", total_records={$group->count()}");

                continue;
            }

            DB::transaction(function () use ($userId, $merged, $names) {
                // Delete legacy records for this user
                CompanyData::whereIn('name', $names)->where('user_id', $userId)->delete();

                // Create the canonical record
                CompanyData::create([
                    'name' => 'cutCostOptimizer',
                    'user_id' => $userId,
                    'data' => $merged,
                ]);
            });

            $this->info("Consolidated user {$userId} -> categories: ".implode(', ', array_keys($merged)));
        }

        $this->info('Consolidation complete.');

        return 0;
    }
}
