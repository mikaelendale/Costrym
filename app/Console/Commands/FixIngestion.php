<?php

namespace App\Console\Commands;

use App\Jobs\DataIngestionJob;
use App\Jobs\FinancialCategorizerJob;
use App\Jobs\JsonFileIngestionJob;
use App\Models\FinancialRecord;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class FixIngestion extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:ingestion {user_id : The user ID to fix}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix ingestion issues for a user and re-trigger the ingestion pipeline';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $userId = $this->argument('user_id');
        $user = User::find($userId);

        if (!$user) {
            $this->error("User #{$userId} not found.");
            return 1;
        }

        $this->info("🔧 Fixing Ingestion for User #{$userId}: {$user->name}");
        $this->newLine();

        // Step 1: Clear failed jobs for this user
        $this->info('Step 1: Clearing failed jobs...');
        $failedCount = $this->clearFailedJobs($userId);
        $this->line("  ✅ Cleared {$failedCount} failed jobs");
        $this->newLine();

        // Step 2: Clear pending jobs for this user
        $this->info('Step 2: Clearing pending jobs...');
        $pendingCount = $this->clearPendingJobs($userId);
        $this->line("  ✅ Cleared {$pendingCount} pending jobs");
        $this->newLine();

        // Step 3: Delete existing uncategorized financial records (to avoid duplicates)
        if ($this->confirm('Do you want to delete existing financial records to start fresh?', true)) {
            $this->info('Step 3: Deleting existing financial records...');
            $recordCount = FinancialRecord::where('user_id', $userId)->count();
            FinancialRecord::where('user_id', $userId)->delete();
            $this->line("  ✅ Deleted {$recordCount} financial records");
            $this->newLine();
        }

        // Step 4: Find uploaded JSON files
        $this->info('Step 4: Locating uploaded JSON files...');
        $jsonFiles = $this->findJsonFiles($userId);
        
        if (empty($jsonFiles)) {
            $this->warn('  ⚠️  No JSON files found. Upload a file first!');
            $this->newLine();
            return 1;
        }

        $this->line("  Found " . count($jsonFiles) . " JSON files:");
        foreach ($jsonFiles as $file) {
            $this->line("    - " . basename($file));
        }
        $this->newLine();

        // Step 5: Ask which file to process
        $selectedFile = null;
        if (count($jsonFiles) === 1) {
            $selectedFile = $jsonFiles[0];
            $this->line("  Using: " . basename($selectedFile));
        } else {
            $choices = array_map('basename', $jsonFiles);
            $choice = $this->choice('Which file would you like to process?', $choices, 0);
            $selectedFile = $jsonFiles[array_search($choice, $choices)];
        }
        $this->newLine();

        // Step 6: Dispatch DataIngestionJob with the JSON file
        $this->info('Step 5: Dispatching DataIngestionJob...');
        
        if ($this->confirm('Process this file with DataIngestionJob?', true)) {
            DataIngestionJob::dispatch($userId, isInitialSync: true, jsonFilePath: $selectedFile);
            $this->line("  ✅ DataIngestionJob dispatched!");
            $this->newLine();
            
            // Show next steps
            $this->info('📋 Next Steps:');
            $this->line('  1. Start the queue worker:');
            $this->comment('     php artisan queue:work --queue=data_ingestion,categorization,default');
            $this->newLine();
            $this->line('  2. Monitor logs in another terminal:');
            $this->comment('     Get-Content storage/logs/laravel.log -Wait -Tail 50');
            $this->newLine();
            $this->line('  3. Check progress:');
            $this->comment('     php artisan diagnose:ingestion ' . $userId);
            $this->newLine();
            
            return 0;
        }

        return 0;
    }

    /**
     * Clear failed jobs for user
     */
    protected function clearFailedJobs(int $userId): int
    {
        $deleted = DB::table('failed_jobs')
            ->where(function ($query) use ($userId) {
                $query->whereRaw("payload LIKE ?", ['%"userId";i:' . $userId . '%'])
                      ->orWhereRaw("payload LIKE ?", ['%"user_id";i:' . $userId . '%']);
            })
            ->delete();

        return $deleted;
    }

    /**
     * Clear pending jobs for user
     */
    protected function clearPendingJobs(int $userId): int
    {
        $deleted = DB::table('jobs')
            ->where(function ($query) use ($userId) {
                $query->whereRaw("payload LIKE ?", ['%"userId";i:' . $userId . '%'])
                      ->orWhereRaw("payload LIKE ?", ['%"user_id";i:' . $userId . '%']);
            })
            ->delete();

        return $deleted;
    }

    /**
     * Find uploaded JSON files for user in local storage
     */
    protected function findJsonFiles(int $userId): array
    {
        $path = "financial_data/{$userId}";
        
        if (!Storage::disk('local')->exists($path)) {
            return [];
        }

        $files = Storage::disk('local')->files($path);
        
        // Filter only JSON files and sort by most recent
        $jsonFiles = array_filter($files, function ($file) {
            return str_ends_with($file, '.json');
        });

        // Sort by last modified time (most recent first)
        usort($jsonFiles, function ($a, $b) {
            return Storage::disk('local')->lastModified($b) - Storage::disk('local')->lastModified($a);
        });

        return $jsonFiles;
    }
}
