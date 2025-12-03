<?php

namespace App\Jobs;

use App\AiAgents\CategorizerAgent;
use App\Events\DataIngestionStatusUpdated;
use App\Models\FinancialRecord;
use App\Models\User;
use App\Models\FinancialCategory;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Batch Categorizer for Financial Transactions
 *
 * Processes uncategorized financial records in batches of 20,
 * using AI to intelligently classify them into predefined categories.
 */
class FinancialCategorizerJob implements ShouldQueue
{
    use Batchable, Queueable;

    public int $tries = 3;

    public array $backoff = [60, 300]; // 1min, 5min

    public int $timeout = 300; // 5 minutes

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $userId,
        public int $batchSize = 20,
        public bool $triggerAnalysis = false
    ) {
        $this->onQueue('categorization');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Check if this job is part of a batch and if the batch has been cancelled
        if ($this->batch()?->cancelled()) {
            Log::info('FinancialCategorizerJob: Batch was cancelled', [
                'user_id' => $this->userId,
            ]);

            return;
        }

        $user = User::find($this->userId);

        if (! $user) {
            Log::error('FinancialCategorizerJob: User not found', ['user_id' => $this->userId]);

            return;
        }

        Log::info('FinancialCategorizerJob started', [
            'user_id' => $this->userId,
            'batch_size' => $this->batchSize,
            'trigger_analysis' => $this->triggerAnalysis,
        ]);

        // Get uncategorized records (where category_id is null)
        $uncategorizedRecords = FinancialRecord::where('user_id', $this->userId)
            ->whereNull('category_id')
            ->limit($this->batchSize)
            ->get();

        if ($uncategorizedRecords->isEmpty()) {
            Log::info('FinancialCategorizerJob: No uncategorized records found', [
                'user_id' => $this->userId,
            ]);

            // If triggered to run analysis and no records left, run it now
            // But only if analysis doesn't already exist AND not already running
            if ($this->triggerAnalysis) {
                $analysisExists = \App\Models\Automation::where('user_id', $this->userId)
                    ->where('type', 'first_time_cost_analysis')
                    ->exists();

                // Also check if analysis is already running (in cache)
                $analysisStatus = Cache::get("analysis_status_{$this->userId}");
                $isAnalysisRunning = $analysisStatus && in_array($analysisStatus['status'] ?? '', ['started', 'analyzing']);

                // Use a lock to prevent duplicate dispatches
                $analysisLockKey = "analysis_dispatch_{$this->userId}";
                $isAnalysisLocked = Cache::has($analysisLockKey);

                if (! $analysisExists && ! $isAnalysisRunning && ! $isAnalysisLocked) {
                    // Set lock for 30 seconds to prevent duplicate dispatches
                    Cache::put($analysisLockKey, true, 30);
                    Log::info('FinancialCategorizerJob: Triggering FirstTimeCostAnalysisJob (no records left)', ['user_id' => $this->userId]);
                    FirstTimeCostAnalysisJob::dispatch($this->userId);
                } else {
                    if ($analysisExists) {
                        Log::info('FinancialCategorizerJob: FirstTimeCostAnalysisJob already exists, skipping', ['user_id' => $this->userId]);
                    }
                    if ($isAnalysisRunning) {
                        Log::info('FinancialCategorizerJob: FirstTimeCostAnalysisJob already running, skipping', ['user_id' => $this->userId]);
                    }
                    if ($isAnalysisLocked) {
                        Log::info('FinancialCategorizerJob: FirstTimeCostAnalysisJob dispatch already locked, skipping', ['user_id' => $this->userId]);
                    }
                }
            }

            return;
        }

        Log::info('FinancialCategorizerJob: Processing batch', [
            'user_id' => $this->userId,
            'record_count' => $uncategorizedRecords->count(),
        ]);

        // Prepare batch data for the agent
        $transactionsData = $uncategorizedRecords->map(function ($record) {
            return [
                'id' => $record->id,
                'description' => $record->description ?? 'No description',
                'amount' => $record->amount,
                'currency' => $record->currency,
                'date' => $record->date?->format('Y-m-d'),
                'record_type' => $record->record_type,
                'integration_type' => $record->integration_type,
                'raw_data' => $record->raw_data,
            ];
        })->toArray();

        // Build prompt for CategorizerAgent
        $prompt = $this->buildCategorizationPrompt($transactionsData);

        try {
            // Run CategorizerAgent
            $agentResponse = CategorizerAgent::for('categorization_'.$this->userId.'_'.time())->respond($prompt);

            $response = $agentResponse;

            Log::info('FinancialCategorizerJob: Agent response received', [
                'type' => gettype($response),
                'is_array' => is_array($response),
                'content_preview' => is_string($response) ? substr($response, 0, 100) : (is_array($response) ? 'Array keys: '.implode(', ', array_keys($response)) : 'Object/Other'),
            ]);

            // Parse the agent's response
            if (is_array($response)) {
                $categorizations = $response['categorizations'] ?? $response;
            } else {
                $categorizations = $this->parseCategorizationResponse($response);
            }

            if (empty($categorizations)) {
                Log::warning('FinancialCategorizerJob: No categorizations found in agent response');

                // Even if agent failed this batch, if we are supposed to trigger analysis, we might want to?
                // But probably better to retry or let the next batch handle it.
                // For now, if we can't categorize, we might get stuck in a loop if we don't handle it.
                // But let's assume standard retry logic handles transient failures.
                return;
            }

            // Apply categorizations to records
            $categorized = 0;
            $skipped = 0;

            foreach ($categorizations as $cat) {
                if (! isset($cat['id'], $cat['category_id'])) {
                    $skipped++;

                    continue;
                }

                $record = $uncategorizedRecords->firstWhere('id', $cat['id']);

                if (! $record) {
                    $skipped++;

                    continue;
                }

                // Update the record's category
                $record->update([
                    'category_id' => $cat['category_id'],
                ]);

                $categorized++;
            }

            Log::info('FinancialCategorizerJob completed successfully', [
                'user_id' => $this->userId,
                'categorized' => $categorized,
                'skipped' => $skipped,
                'total' => $uncategorizedRecords->count(),
            ]);

            // Check if there are more uncategorized records
            $remainingCount = FinancialRecord::where('user_id', $this->userId)
                ->whereNull('category_id')
                ->count();

            if ($remainingCount > 0) {
                Log::info('FinancialCategorizerJob: More records to categorize', [
                    'user_id' => $this->userId,
                    'remaining' => $remainingCount,
                    'dispatching_next_batch' => true,
                ]);

                // Dispatch another job to continue categorizing
                // Add a small delay to avoid overwhelming the system
                // Use a lock to prevent duplicate dispatches
                $lockKey = "categorizer_dispatch_{$this->userId}";
                if (! Cache::has($lockKey)) {
                    Cache::put($lockKey, true, 5); // 5 second lock
                    dispatch(new self($this->userId, $this->batchSize, $this->triggerAnalysis))
                        ->delay(now()->addSeconds(2));
                } else {
                    Log::info('FinancialCategorizerJob: Dispatch already in progress, skipping', ['user_id' => $this->userId]);
                }
            } else {
                // No more records, trigger analysis if requested
                // But only if analysis doesn't already exist
                if ($this->triggerAnalysis) {
                    $analysisExists = \App\Models\Automation::where('user_id', $this->userId)
                        ->where('type', 'first_time_cost_analysis')
                        ->exists();

                    // Also check if analysis is already running (in cache)
                    $analysisStatus = Cache::get("analysis_status_{$this->userId}");
                    $isAnalysisRunning = $analysisStatus && in_array($analysisStatus['status'] ?? '', ['started', 'analyzing']);

                    // Use a lock to prevent duplicate dispatches
                    $analysisLockKey = "analysis_dispatch_{$this->userId}";
                    $isAnalysisLocked = Cache::has($analysisLockKey);

                    if (! $analysisExists && ! $isAnalysisRunning && ! $isAnalysisLocked) {
                        // Set lock for 30 seconds to prevent duplicate dispatches
                        Cache::put($analysisLockKey, true, 30);
                        Log::info('FinancialCategorizerJob: All records categorized. Triggering FirstTimeCostAnalysisJob.', ['user_id' => $this->userId]);

                        $totalRecords = FinancialRecord::where('user_id', $this->userId)->count();
                        $categorizedRecords = FinancialRecord::where('user_id', $this->userId)->whereNotNull('category_id')->count();

                        // Store completion status in cache
                        Cache::put("ingestion_status_{$this->userId}", [
                            'status' => 'completed',
                            'message' => 'Data ingestion and categorization complete!',
                            'data' => [
                                'total_records' => $totalRecords,
                                'categorized_records' => $categorizedRecords,
                            ],
                            'updated_at' => now()->toDateTimeString(),
                        ], 3600);

                        // Broadcast completion (optional)
                        Log::info('✅ Broadcasting ingestion COMPLETED', [
                            'user_id' => $this->userId,
                            'total_records' => $totalRecords,
                            'categorized_records' => $categorizedRecords,
                        ]);
                        try {
                            broadcast(new DataIngestionStatusUpdated(
                                $this->userId,
                                'completed',
                                [
                                    'message' => 'Data ingestion and categorization complete!',
                                    'total_records' => $totalRecords,
                                    'categorized_records' => $categorizedRecords,
                                ]
                            ));
                            Log::info('✅ Broadcast sent: ingestion COMPLETED', ['user_id' => $this->userId]);
                        } catch (\Exception $e) {
                            Log::warning('Broadcast failed, using polling fallback', ['error' => $e->getMessage()]);
                        }

                        FirstTimeCostAnalysisJob::dispatch($this->userId);
                    } else {
                        if ($analysisExists) {
                            Log::info('FinancialCategorizerJob: FirstTimeCostAnalysisJob already exists, skipping', ['user_id' => $this->userId]);
                        }
                        if ($isAnalysisRunning) {
                            Log::info('FinancialCategorizerJob: FirstTimeCostAnalysisJob already running, skipping', ['user_id' => $this->userId]);
                        }
                        if ($isAnalysisLocked) {
                            Log::info('FinancialCategorizerJob: FirstTimeCostAnalysisJob dispatch already locked, skipping', ['user_id' => $this->userId]);
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('FinancialCategorizerJob: Agent execution failed', [
                'user_id' => $this->userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Build the categorization prompt for the agent
     */
    protected function buildCategorizationPrompt(array $transactions): string
    {
        $transactionsJson = json_encode($transactions, JSON_PRETTY_PRINT);
        $count = count($transactions);
        $categories = FinancialCategory::all();

    $categoriesList = '';
    foreach ($categories as $category) {
        $categoriesList .= "- {$category->name} (ID: {$category->id})\n";
    }
   Log::info('categories fetched', [
    'list' => $transactionsJson,
]);

        return <<<PROMPT
# Batch Transaction Categorization

You need to categorize {$count} financial transactions into the appropriate expense categories.

## Instructions:
1. First, use the `list_financial_categories` tool to see all available categories
2. For each transaction below, analyze the description, amount, and context
3. Assign the most appropriate category_id
4. Return a JSON array with format: [{"id": record_id, "category_id": category_id, "confidence": "high|medium|low"}]

## Transactions to Categorize:
```json
{$transactionsJson}
```

# Avaliable categories and their respectiveid 
    {$categoriesList}

## Important Notes:
- Use the transaction description as the primary indicator
- Consider the amount and date as secondary factors
- If unsure, use "Miscellaneous / Other" category
- Be consistent with similar transaction patterns
- Always return valid JSON array format

Please categorize these transactions now.
PROMPT;
    }

    /**
     * Parse the agent's categorization response
     */
    protected function parseCategorizationResponse(string $response): array
    {
        // Try to extract JSON from the response
        if (preg_match('/\[[\s\S]*\]/', $response, $matches)) {
            $jsonString = $matches[0];

            try {
                $data = json_decode($jsonString, true);

                if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
                    Log::info('FinancialCategorizerJob: Parsed categorizations', [
                        'count' => count($data),
                    ]);

                    return $data;
                }
            } catch (\Exception $e) {
                Log::error('FinancialCategorizerJob: JSON parse error', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [];
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('FinancialCategorizerJob failed', [
            'user_id' => $this->userId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
