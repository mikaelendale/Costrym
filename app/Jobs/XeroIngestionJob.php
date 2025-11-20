<?php

namespace App\Jobs;

use App\Agents\IntegrationIngestor;
use App\Models\ConnectedAccount;
use App\Models\FinancialCategory;
use App\Models\FinancialRecord;
use App\Models\IngestionLog;
use App\Models\User;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Xero Data Ingestion Job
 *
 * Uses the IntegrationIngestor agent to fetch data from Xero,
 * normalize it using AI, and store it in the financial_records table.
 */
class XeroIngestionJob implements ShouldQueue
{
    use Batchable, Queueable;

    public int $tries = 3;

    public array $backoff = [120, 600, 1800]; // 2min, 10min, 30min

    public int $timeout = 900; // 15 minutes

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $userId,
        public string $integrationType,
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
            Log::error('XeroIngestionJob: User not found', ['user_id' => $this->userId]);

            return;
        }

        // Create ingestion log
        $ingestionLog = IngestionLog::create([
            'user_id' => $this->userId,
            'integration_type' => $this->integrationType,
            'status' => 'pending',
        ]);

        $ingestionLog->markAsRunning();

        Log::info('XeroIngestionJob started', [
            'user_id' => $this->userId,
            'integration_type' => $this->integrationType,
            'is_initial_sync' => $this->isInitialSync,
            'ingestion_log_id' => $ingestionLog->id,
        ]);

        try {
            // Get connected account
            $connectedAccount = ConnectedAccount::where('user_id', $this->userId)
                ->where('app_name', $this->integrationType)
                ->where('is_active', true)
                ->first();

            if (! $connectedAccount) {
                throw new \Exception("{$this->integrationType} account not connected");
            }

            // Create agent context with prompt variables
            $startDate = $this->isInitialSync
                ? now()->subMonths(3)->toDateString()
                : now()->subDay()->toDateString();

            $endDate = now()->toDateString();

            $dateRange = $this->isInitialSync
                ? "Last 3 months ({$startDate} to {$endDate})"
                : "Last 24 hours ({$startDate} to {$endDate})";

            // Build the dynamic prompt ourselves using the Blade template
            $promptData = [
                'integration_type' => $this->integrationType,
                'integration_name' => $this->getIntegrationName(),
                'task_type' => 'data_ingestion',
                'is_initial_sync' => $this->isInitialSync,
                'date_range' => $dateRange,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'user_id' => $this->userId,
                'ingestion_log_id' => $ingestionLog->id,
            ];

            $promptPath = resource_path('prompts/jobs/xero_ingestion.blade.php');
            $prompt = view()->file($promptPath, $promptData)->render();

            Log::info('XeroIngestionJob: Running IntegrationIngestor agent', [
                'integration_type' => $this->integrationType,
                'date_range' => $dateRange,
                'prompt_length' => strlen($prompt),
            ]);

            // Run the agent with the fully-rendered prompt
            // Set context state so agent can load correct tools
            $sessionId = "xero_ingestion_{$this->userId}_".time();
            $agentResponse = IntegrationIngestor::run(input: $prompt)
                ->forUser($user)
                ->withSession($sessionId)
                ->withContext([
                    'integration_type' => $this->integrationType,
                    'user_id' => $this->userId,
                ])
                ->go();

            Log::info('XeroIngestionJob: Agent response received', [
                'response_length' => strlen($agentResponse),
            ]);

            // Parse the agent response and extract data
            $extractedData = $this->parseAgentResponse($agentResponse);

            // Store the data in database
            $stats = $this->storeFinancialData($extractedData);

            // Update ingestion log with stats
            $ingestionLog->update([
                'records_fetched' => $stats['fetched'],
                'records_saved' => $stats['saved'],
                'records_updated' => $stats['updated'],
                'records_skipped' => $stats['skipped'],
            ]);

            $ingestionLog->markAsCompleted();

            Log::info('XeroIngestionJob completed successfully', [
                'user_id' => $this->userId,
                'stats' => $stats,
            ]);

        } catch (\Exception $e) {
            Log::error('XeroIngestionJob failed', [
                'user_id' => $this->userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $ingestionLog->markAsFailed([
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Get human-readable integration name
     */
    protected function getIntegrationName(): string
    {
        $names = [
            'xero' => 'Xero',
            'xero_accounting_api' => 'Xero',
            'zoho_books' => 'Zoho Books',
            'quickbooks' => 'QuickBooks Online',
            'sevdesk' => 'Sevdesk',
            'expensify' => 'Expensify',
        ];

        return $names[$this->integrationType] ?? ucfirst($this->integrationType);
    }

    /**
     * Parse the agent response and extract financial data
     */
    protected function parseAgentResponse(string $response): array
    {
        // Try to extract JSON from the response
        if (preg_match('/```json\s*(\{.*?\})\s*```/s', $response, $matches)) {
            $jsonData = $matches[1];
        } elseif (preg_match('/(\{.*"records".*\})/s', $response, $matches)) {
            $jsonData = $matches[1];
        } else {
            // No JSON found, return empty
            Log::warning('XeroIngestionJob: No JSON data found in agent response');

            return ['records' => []];
        }

        $decoded = json_decode($jsonData, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('XeroIngestionJob: Failed to parse JSON', [
                'error' => json_last_error_msg(),
                'json' => substr($jsonData, 0, 500),
            ]);

            return ['records' => []];
        }

        return $decoded;
    }

    /**
     * Store financial data in the database
     */
    protected function storeFinancialData(array $data): array
    {
        $stats = [
            'fetched' => count($data['records'] ?? []),
            'saved' => 0,
            'updated' => 0,
            'skipped' => 0,
        ];

        foreach ($data['records'] ?? [] as $record) {
            try {
                // Check if record already exists
                $existing = FinancialRecord::where('integration_type', $this->integrationType)
                    ->where('integration_record_id', $record['integration_record_id'] ?? '')
                    ->first();

                // AI-categorize the record
                $categoryId = $this->categorizeRecord($record);

                $recordData = [
                    'user_id' => $this->userId,
                    'integration_type' => $this->integrationType,
                    'integration_record_id' => $record['integration_record_id'] ?? uniqid(),
                    'record_type' => $record['record_type'] ?? 'transaction',
                    'amount' => $record['amount'] ?? 0,
                    'currency' => $record['currency'] ?? 'USD',
                    'date' => $record['date'] ?? now(),
                    'description' => $record['description'] ?? '',
                    'category_id' => $categoryId,
                    'raw_data' => $record['raw_data'] ?? [],
                    'normalized_data' => $record['normalized_data'] ?? [],
                    'metadata' => [
                        'synced_at' => now()->toIso8601String(),
                        'sync_type' => $this->isInitialSync ? 'initial' : 'incremental',
                    ],
                ];

                if ($existing) {
                    $existing->update($recordData);
                    $stats['updated']++;
                } else {
                    FinancialRecord::create($recordData);
                    $stats['saved']++;
                }

            } catch (\Exception $e) {
                Log::error('XeroIngestionJob: Failed to store record', [
                    'record' => $record,
                    'error' => $e->getMessage(),
                ]);
                $stats['skipped']++;
            }
        }

        return $stats;
    }

    /**
     * Categorize a record using AI keywords
     */
    protected function categorizeRecord(array $record): ?int
    {
        $description = strtolower($record['description'] ?? '');
        $categorySuggestion = strtolower($record['category_suggestion'] ?? '');

        // Try to match with existing categories using AI keywords
        $categories = FinancialCategory::system()->get();

        foreach ($categories as $category) {
            $keywords = $category->ai_keywords ?? [];

            foreach ($keywords as $keyword) {
                if (str_contains($description, strtolower($keyword)) ||
                    str_contains($categorySuggestion, strtolower($keyword)) ||
                    str_contains($categorySuggestion, strtolower($category->name))) {
                    return $category->id;
                }
            }
        }

        // Default: return "Miscellaneous" category
        return FinancialCategory::where('name', 'Miscellaneous')->value('id');
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('XeroIngestionJob failed permanently', [
            'user_id' => $this->userId,
            'error' => $exception->getMessage(),
        ]);
    }
}
