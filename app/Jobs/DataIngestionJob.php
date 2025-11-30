<?php

namespace App\Jobs;

use App\Events\DataIngestionStatusUpdated;
use App\Models\ConnectedAccount;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
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
        public bool $isInitialSync = true,
        public ?string $jsonFilePath = null
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
            'has_json_file' => ! is_null($this->jsonFilePath),
        ]);

        // Store status in cache for polling
        Cache::put("ingestion_status_{$this->userId}", [
            'status' => 'started',
            'message' => 'Starting data ingestion...',
            'data' => ['is_initial_sync' => $this->isInitialSync],
            'updated_at' => now()->toDateTimeString(),
        ], 3600); // 1 hour TTL

        // Broadcast ingestion started (optional, for real-time if working)
        Log::info('🚀 Broadcasting ingestion STARTED', ['user_id' => $this->userId]);
        try {
            broadcast(new DataIngestionStatusUpdated(
                $this->userId,
                'started',
                [
                    'message' => 'Starting data ingestion...',
                    'is_initial_sync' => $this->isInitialSync,
                ]
            ));
            Log::info('✅ Broadcast sent: ingestion STARTED', ['user_id' => $this->userId]);
        } catch (\Exception $e) {
            Log::warning('Broadcast failed, using polling fallback', ['error' => $e->getMessage()]);
        }

        // Get all active connected accounts for this user
        $connectedAccounts = ConnectedAccount::where('user_id', $this->userId)
            ->where('is_active', true)
            ->get();

        // Collect all ingestion jobs
        $ingestionJobs = [];

        // 1. Add JSON Ingestion Job if file path provided
        if ($this->jsonFilePath) {
            Log::info('DataIngestionJob: Adding JsonFileIngestionJob', [
                'user_id' => $this->userId,
                'file_path' => $this->jsonFilePath,
            ]);
            $ingestionJobs[] = new JsonFileIngestionJob($this->userId, $this->jsonFilePath);
        }

        // 2. Add Integration Ingestion Jobs
        if ($connectedAccounts->isNotEmpty()) {
            Log::info('DataIngestionJob: Found connected accounts', [
                'user_id' => $this->userId,
                'count' => $connectedAccounts->count(),
                'integrations' => $connectedAccounts->pluck('app_name')->toArray(),
            ]);

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
        } else {
            Log::warning('DataIngestionJob: No connected accounts found', ['user_id' => $this->userId]);
        }

        // If no jobs to run (no JSON file AND no connected accounts)
        if (empty($ingestionJobs)) {
            Log::warning('DataIngestionJob: No valid ingestion jobs to dispatch', [
                'user_id' => $this->userId,
            ]);

            // Even without connected accounts/files, if this is initial sync, dispatch FirstTimeCostAnalysisJob
            // This handles cases where data might have been seeded or uploaded separately
            if ($this->isInitialSync) {
                Log::info('DataIngestionJob: No ingestion jobs, but dispatching FirstTimeCostAnalysisJob for fallback', [
                    'user_id' => $this->userId,
                ]);

                FirstTimeCostAnalysisJob::dispatch($this->userId)
                    ->delay(now()->addSeconds(5));
            }

            return;
        }

        // Batch all ingestion jobs together
        // When ALL complete successfully, dispatch MasterOrchestratorJob
        $userId = $this->userId;
        $isInitialSync = $this->isInitialSync;

        Bus::batch($ingestionJobs)
            ->name("Data Ingestion - User {$userId}")
            ->then(function () use ($userId) {
                Log::info('All ingestion jobs completed, starting categorization', [
                    'user_id' => $userId,
                ]);

                // Store status in cache
                Cache::put("ingestion_status_{$userId}", [
                    'status' => 'categorizing',
                    'message' => 'Data ingested successfully. Categorizing records...',
                    'data' => [],
                    'updated_at' => now()->toDateTimeString(),
                ], 3600);

                // Broadcast processing status (optional)
                Log::info('📊 Broadcasting ingestion CATEGORIZING', ['user_id' => $userId]);
                try {
                    broadcast(new DataIngestionStatusUpdated(
                        $userId,
                        'categorizing',
                        ['message' => 'Data ingested successfully. Categorizing records...']
                    ));
                    Log::info('✅ Broadcast sent: ingestion CATEGORIZING', ['user_id' => $userId]);
                } catch (\Exception $e) {
                    Log::warning('Broadcast failed, using polling fallback', ['error' => $e->getMessage()]);
                }

                // First, categorize all the ingested financial records
                // And trigger FirstTimeCostAnalysisJob when done
                FinancialCategorizerJob::dispatch($userId, batchSize: 20, triggerAnalysis: true);

                Log::info('Dispatched FinancialCategorizerJob (with analysis trigger)', [
                    'user_id' => $userId,
                ]);
            })
            ->catch(function (\Throwable $e) use ($userId) {
                Log::error('Ingestion batch failed', [
                    'user_id' => $userId,
                    'error' => $e->getMessage(),
                ]);

                // Store failure status in cache
                Cache::put("ingestion_status_{$userId}", [
                    'status' => 'failed',
                    'message' => 'Data ingestion failed. Please try again.',
                    'data' => ['error' => $e->getMessage()],
                    'updated_at' => now()->toDateTimeString(),
                ], 3600);

                // Broadcast failure (optional)
                Log::error('❌ Broadcasting ingestion FAILED', [
                    'user_id' => $userId,
                    'error' => $e->getMessage(),
                ]);
                try {
                    broadcast(new DataIngestionStatusUpdated(
                        $userId,
                        'failed',
                        [
                            'message' => 'Data ingestion failed. Please try again.',
                            'error' => $e->getMessage(),
                        ]
                    ));
                    Log::info('✅ Broadcast sent: ingestion FAILED', ['user_id' => $userId]);
                } catch (\Exception $broadcastError) {
                    Log::warning('Broadcast failed, using polling fallback', ['error' => $broadcastError->getMessage()]);
                }
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
