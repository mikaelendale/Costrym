<?php

namespace App\Console\Commands;

use App\Models\ConnectedAccount;
use App\Models\FinancialRecord;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DiagnoseIngestion extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'diagnose:ingestion {user_id? : The user ID to diagnose}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Diagnose data ingestion and categorization issues';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $userId = $this->argument('user_id');

        if ($userId) {
            $this->diagnoseUser($userId);
        } else {
            $this->diagnoseAllUsers();
        }

        return 0;
    }

    /**
     * Diagnose all users
     */
    protected function diagnoseAllUsers(): void
    {
        $this->info('🔍 Diagnosing All Users');
        $this->newLine();

        $users = User::all();

        if ($users->isEmpty()) {
            $this->warn('No users found in the system.');
            return;
        }

        foreach ($users as $user) {
            $this->line("User #{$user->id}: {$user->name} ({$user->email})");
            $this->diagnoseUser($user->id, false);
            $this->newLine();
        }
    }

    /**
     * Diagnose specific user
     */
    protected function diagnoseUser(int $userId, bool $verbose = true): void
    {
        $user = User::find($userId);

        if (!$user) {
            $this->error("User #{$userId} not found.");
            return;
        }

        if ($verbose) {
            $this->info("🔍 Diagnosing User #{$userId}: {$user->name}");
            $this->newLine();
        }

        // 1. Check Onboarding Status
        $this->checkOnboardingStatus($user);

        // 2. Check Connected Accounts
        $this->checkConnectedAccounts($user);

        // 3. Check Uploaded JSON Files
        $this->checkUploadedFiles($user);

        // 4. Check Financial Records
        $this->checkFinancialRecords($user);

        // 5. Check Queue Jobs
        $this->checkQueueJobs($user);

        // 6. Check Failed Jobs
        $this->checkFailedJobs($user);

        // 7. Overall Status
        $this->showOverallStatus($user);
    }

    /**
     * Check onboarding status
     */
    protected function checkOnboardingStatus(User $user): void
    {
        $this->line('📋 Onboarding Status:');
        
        $status = $user->onboarding_status ? '✅ Completed' : '❌ Not Completed';
        $this->line("  Status: {$status}");
        
        if ($user->plan) {
            $this->line("  Plan: {$user->plan}");
        }
        
        $this->newLine();
    }

    /**
     * Check connected accounts
     */
    protected function checkConnectedAccounts(User $user): void
    {
        $accounts = ConnectedAccount::where('user_id', $user->id)->get();
        
        $this->line('🔗 Connected Accounts:');
        
        if ($accounts->isEmpty()) {
            $this->warn('  No connected accounts found.');
        } else {
            foreach ($accounts as $account) {
                $status = $account->is_active ? '✅ Active' : '❌ Inactive';
                $this->line("  - {$account->app_name}: {$status}");
            }
        }
        
        $this->newLine();
    }

    /**
     * Check uploaded files in S3
     */
    protected function checkUploadedFiles(User $user): void
    {
        $this->line('📁 Uploaded JSON Files:');
        
        $path = "financial_data/{$user->id}";
        
        if (!Storage::exists($path)) {
            $this->warn('  No upload directory found.');
        } else {
            $files = Storage::files($path);
            
            if (empty($files)) {
                $this->warn('  No files found in upload directory.');
            } else {
                foreach ($files as $file) {
                    $size = Storage::size($file);
                    $lastModified = Storage::lastModified($file);
                    $this->line("  - " . basename($file) . " (" . $this->formatBytes($size) . ", " . date('Y-m-d H:i:s', $lastModified) . ")");
                }
            }
        }
        
        $this->newLine();
    }

    /**
     * Check financial records
     */
    protected function checkFinancialRecords(User $user): void
    {
        $this->line('💰 Financial Records:');
        
        $totalRecords = FinancialRecord::where('user_id', $user->id)->count();
        $categorized = FinancialRecord::where('user_id', $user->id)->whereNotNull('category_id')->count();
        $uncategorized = FinancialRecord::where('user_id', $user->id)->whereNull('category_id')->count();
        
        $this->line("  Total Records: {$totalRecords}");
        $this->line("  Categorized: {$categorized}");
        
        if ($uncategorized > 0) {
            $this->warn("  Uncategorized: {$uncategorized} ⚠️");
        } else {
            $this->line("  Uncategorized: {$uncategorized}");
        }
        
        // Group by integration type
        $byIntegration = FinancialRecord::where('user_id', $user->id)
            ->select('integration_type', DB::raw('count(*) as count'))
            ->groupBy('integration_type')
            ->get();
        
        if ($byIntegration->isNotEmpty()) {
            $this->line('  By Integration:');
            foreach ($byIntegration as $row) {
                $this->line("    - {$row->integration_type}: {$row->count} records");
            }
        }
        
        $this->newLine();
    }

    /**
     * Check queue jobs
     */
    protected function checkQueueJobs(User $user): void
    {
        $this->line('⏳ Pending Queue Jobs:');
        
        // Check jobs table for pending jobs
        $pendingJobs = DB::table('jobs')
            ->whereRaw("payload LIKE ?", ['%"userId";i:' . $user->id . '%'])
            ->orWhereRaw("payload LIKE ?", ['%"user_id";i:' . $user->id . '%'])
            ->count();
        
        if ($pendingJobs > 0) {
            $this->warn("  {$pendingJobs} pending jobs found ⚠️");
        } else {
            $this->line("  No pending jobs");
        }
        
        $this->newLine();
    }

    /**
     * Check failed jobs
     */
    protected function checkFailedJobs(User $user): void
    {
        $this->line('❌ Failed Jobs:');
        
        // Check failed_jobs table
        $failedJobs = DB::table('failed_jobs')
            ->whereRaw("payload LIKE ?", ['%"userId";i:' . $user->id . '%'])
            ->orWhereRaw("payload LIKE ?", ['%"user_id";i:' . $user->id . '%'])
            ->get();
        
        if ($failedJobs->isEmpty()) {
            $this->line("  No failed jobs");
        } else {
            $this->warn("  {$failedJobs->count()} failed jobs found ⚠️");
            
            foreach ($failedJobs as $job) {
                $payload = json_decode($job->payload, true);
                $displayName = $payload['displayName'] ?? 'Unknown Job';
                $this->line("    - {$displayName} (Failed at: " . date('Y-m-d H:i:s', strtotime($job->failed_at)) . ")");
            }
            
            $this->newLine();
            $this->comment('  💡 Run "php artisan queue:retry all" to retry all failed jobs');
            $this->comment('  💡 Run "php artisan queue:flush" to clear all failed jobs');
        }
        
        $this->newLine();
    }

    /**
     * Show overall status
     */
    protected function showOverallStatus(User $user): void
    {
        $this->line('📊 Overall Status:');
        
        $hasRecords = FinancialRecord::where('user_id', $user->id)->exists();
        $hasConnections = ConnectedAccount::where('user_id', $user->id)->where('is_active', true)->exists();
        $hasFiles = Storage::disk('private')->exists("financial_data/{$user->id}") && 
                    !empty(Storage::disk('private')->files("financial_data/{$user->id}"));
        
        if ($hasRecords) {
            $this->info('  ✅ Financial records are being ingested');
        } elseif ($hasConnections || $hasFiles) {
            $this->warn('  ⚠️  Has data sources but NO financial records found!');
            $this->newLine();
            $this->comment('  💡 Possible issues:');
            $this->comment('     1. Queue worker is not running');
            $this->comment('     2. Jobs are failing (check failed jobs above)');
            $this->comment('     3. Integration is not returning data');
            $this->newLine();
            $this->comment('  💡 Solutions:');
            $this->comment('     1. Start queue worker: php artisan queue:work');
            $this->comment('     2. Check logs: tail -f storage/logs/laravel.log');
            $this->comment('     3. Retry failed jobs: php artisan queue:retry all');
        } else {
            $this->warn('  ⚠️  No data sources configured (no integrations or uploaded files)');
        }
        
        $this->newLine();
    }

    /**
     * Format bytes to human readable
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
