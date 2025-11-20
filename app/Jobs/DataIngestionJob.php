<?php

namespace App\Jobs;

use App\Models\ConnectedAccount;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

/**
 * Main Data Ingestion Orchestrator
 *
 * This job dispatches individual ingestion jobs for each connected integration.
 * Triggered after user onboarding or can be run periodically for incremental syncs.
 */
class DataIngestionJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [60, 300, 900]; // 1min, 5min, 15min

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $userId,
        public bool $isInitialSync = true
    ) {
        $this->onQueue('data_ingestion');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $user = User::find($this->userId);

        if (! $user) {
            Log::error('DataIngestionJob: User not found', ['user_id' => $this->userId]);

            return;
        }

        Log::info('DataIngestionJob started', [
            'user_id' => $this->userId,
            'is_initial_sync' => $this->isInitialSync,
        ]);

        // Get all active connected accounts for this user
        $connectedAccounts = ConnectedAccount::where('user_id', $this->userId)
            ->where('is_active', true)
            ->get();

        if ($connectedAccounts->isEmpty()) {
            Log::warning('DataIngestionJob: No connected accounts found', ['user_id' => $this->userId]);

            return;
        }

        Log::info('DataIngestionJob: Found connected accounts', [
            'user_id' => $this->userId,
            'count' => $connectedAccounts->count(),
            'integrations' => $connectedAccounts->pluck('app_name')->toArray(),
        ]);

        // Collect all ingestion jobs
        $ingestionJobs = [];
        foreach ($connectedAccounts as $account) {
            $integrationType = $account->app_name;

            // Map integration type to ingestion job class
            $jobClass = $this->getIngestionJobClass($integrationType);

            if (! $jobClass) {
                Log::warning('DataIngestionJob: No ingestion job found for integration', [
                    'integration_type' => $integrationType,
                    'user_id' => $this->userId,
                ]);

                continue;
            }

            Log::info('DataIngestionJob: Preparing integration ingestion job', [
                'user_id' => $this->userId,
                'integration_type' => $integrationType,
                'job_class' => $jobClass,
            ]);

            $ingestionJobs[] = new $jobClass($this->userId, $integrationType, $this->isInitialSync);
        }

        if (empty($ingestionJobs)) {
            Log::warning('DataIngestionJob: No valid ingestion jobs to dispatch', [
                'user_id' => $this->userId,
            ]);

            return;
        }

        // Batch all ingestion jobs together
        // When ALL complete successfully, dispatch MasterOrchestratorJob
        $userId = $this->userId;
        $isInitialSync = $this->isInitialSync;

        Bus::batch($ingestionJobs)
            ->name("Data Ingestion - User {$userId}")
            ->then(function () use ($userId, $isInitialSync) {
                Log::info('All ingestion jobs completed, starting categorization', [
                    'user_id' => $userId,
                ]);

                // First, categorize all the ingested financial records
                FinancialCategorizerJob::dispatch($userId, batchSize: 20);

                // Then dispatch MasterOrchestratorJob with categorized data available
                // Add a delay to give categorization time to process at least one batch
                MasterOrchestratorJob::dispatch(
                    userId: $userId,
                    scenario: $isInitialSync ? 'first_onboarding' : 'task_generation',
                    additionalContext: ['trigger' => 'post_ingestion']
                )->delay(now()->addSeconds(5));

                Log::info('Dispatched FinancialCategorizerJob and MasterOrchestratorJob', [
                    'user_id' => $userId,
                ]);
            })
            ->catch(function (\Throwable $e) use ($userId) {
                Log::error('Ingestion batch failed', [
                    'user_id' => $userId,
                    'error' => $e->getMessage(),
                ]);
            })
            ->onQueue('data_ingestion')
            ->dispatch();

        Log::info('DataIngestionJob: Batch dispatched with ingestion jobs', [
            'user_id' => $this->userId,
            'job_count' => count($ingestionJobs),
            'note' => 'MasterOrchestratorJob will run after all ingestion jobs complete',
        ]);
    }

    /**
     * Get the ingestion job class for a specific integration type
     */
    protected function getIngestionJobClass(string $integrationType): ?string
    {
        $jobMap = [
            'xero_accounting_api' => XeroIngestionJob::class,
            'zoho_books' => ZohoBooksIngestionJob::class,
            'quickbooks' => QuickBooksIngestionJob::class,
            'sevdesk' => SevdeskIngestionJob::class,
            'expensify' => ExpensifyIngestionJob::class,
            'gmail' => null, // Gmail doesn't need financial ingestion
            'notion' => null, // Notion doesn't need financial ingestion
        ];

        return $jobMap[$integrationType] ?? null;
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('DataIngestionJob failed', [
            'user_id' => $this->userId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
