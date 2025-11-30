<?php

namespace App\Jobs;

use App\AiAgents\JsonIngestionAgent;
use App\Models\FinancialRecord;
use App\Models\User;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class JsonFileIngestionJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600; // 10 minutes

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $userId,
        public string $filePath
    ) {
        $this->onQueue('data_ingestion');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Check if this job is part of a batch and if the batch has been cancelled
        if ($this->batch()?->cancelled()) {
            Log::info('JsonFileIngestionJob: Batch was cancelled', [
                'user_id' => $this->userId,
            ]);
            return;
        }

        Log::info('JsonFileIngestionJob started', [
            'user_id' => $this->userId,
            'file_path' => $this->filePath,
        ]);

        try {
            // 1. Load JSON Data from S3
            if (! Storage::exists($this->filePath)) {
                throw new \Exception("File not found: {$this->filePath}");
            }

            $jsonContent = Storage::get($this->filePath);
            $data = json_decode($jsonContent, true);

            if (! is_array($data)) {
                throw new \Exception('Invalid JSON format');
            }

            // 2. Prepare Sample for AI
            // Take the first 3 rows from each "sheet" (top-level key) to keep context small
            $sample = [];
            foreach ($data as $key => $value) {
                if (is_array($value)) {
                    // If it's a list of objects (rows), take first 3
                    if (isset($value[0]) && is_array($value[0])) {
                        $sample[$key] = array_slice($value, 0, 3);
                    } else {
                        // If it's just an object, include it as is (might be metadata)
                        $sample[$key] = $value;
                    }
                }
            }

            // 3. Get Mapping from AI Agent
            $sessionId = "ingest_map_{$this->userId}_".time();
            $agent = JsonIngestionAgent::for($sessionId);
            
            $prompt = "Here is a sample of the JSON file structure. Please identify the transaction list and map the columns.\n\n" . 
                      json_encode($sample, JSON_PRETTY_PRINT);

            $mapping = $agent->respond($prompt);

            Log::info('AI Mapping received', ['mapping' => $mapping]);

            if (empty($mapping['is_valid_financial_file']) || ! $mapping['is_valid_financial_file']) {
                Log::warning('AI determined file is not a valid financial record', ['user_id' => $this->userId]);
                return;
            }

            // 4. Process Data using Mapping
            $targetKey = $mapping['main_data_key'];
            $colMap = $mapping['column_mapping'];

            $transactions = ($targetKey === 'ROOT') ? $data : ($data[$targetKey] ?? []);

            if (! is_array($transactions) || empty($transactions)) {
                throw new \Exception("Target key '{$targetKey}' not found or empty");
            }

            // Check if we need to handle indexed arrays (headers in first row)
            $firstRow = $transactions[0] ?? [];
            $isIndexed = isset($firstRow[0]);
            $headerMap = [];
            $startIndex = 0;

            if ($isIndexed) {
                // Assume first row is header
                foreach ($firstRow as $index => $colName) {
                    $headerMap[(string)$colName] = $index;
                }
                $startIndex = 1; // Skip header row
            }

            $count = 0;
            $totalRows = count($transactions);
            
            for ($i = $startIndex; $i < $totalRows; $i++) {
                $row = $transactions[$i];
                
                // Helper to get value by mapped column name
                $getValue = function($mappedName) use ($row, $isIndexed, $headerMap) {
                    if ($mappedName === null) return null;
                    
                    if ($isIndexed) {
                        // Look up index from header map
                        $index = $headerMap[$mappedName] ?? null;
                        return $index !== null ? ($row[$index] ?? null) : null;
                    } else {
                        // Associative array
                        return $row[$mappedName] ?? null;
                    }
                };

                // Extract values using the helper
                $dateVal = $getValue($colMap['date']);
                $amountVal = $getValue($colMap['amount']);
                $descVal = $getValue($colMap['description']);
                $payeeVal = isset($colMap['payee']) ? $getValue($colMap['payee']) : null;
                $currencyVal = isset($colMap['currency']) ? $getValue($colMap['currency']) : 'USD';
                
                // If currency is not found in row, use default 'USD'
                if ($currencyVal === null) $currencyVal = 'USD';

                if (! $dateVal || ! $amountVal) {
                    continue; // Skip invalid rows
                }

                // Clean Amount (remove currency symbols, commas)
                $amount = preg_replace('/[^0-9.\-]/', '', (string)$amountVal);
                
                // Parse Date (handle Excel serial dates if needed)
                try {
                    if (is_numeric($dateVal)) {
                        // Excel serial date
                        $date = \Carbon\Carbon::createFromDate(1900, 1, 1)->addDays($dateVal - 2);
                    } else {
                        $date = \Carbon\Carbon::parse($dateVal);
                    }
                } catch (\Exception $e) {
                    continue; // Skip invalid dates
                }

                // Create Record
                FinancialRecord::create([
                    'user_id' => $this->userId,
                    'integration_type' => 'manual_upload',
                    'integration_record_id' => 'json_' . md5(json_encode($row) . time() . $i), // Unique ID
                    'record_type' => 'expense',
                    'amount' => (float)$amount,
                    'currency' => $currencyVal,
                    'date' => $date,
                    'description' => $descVal . ($payeeVal ? " - {$payeeVal}" : ""),
                    'raw_data' => $row,
                    'metadata' => ['source_file' => $this->filePath],
                ]);

                $count++;
            }

            Log::info('JsonFileIngestionJob completed', [
                'user_id' => $this->userId,
                'records_created' => $count,
            ]);

            // 5. Trigger Categorization
            if ($count > 0) {
                Log::info('JsonFileIngestionJob: Dispatching FinancialCategorizerJob', [
                    'user_id' => $this->userId,
                    'records_created' => $count,
                ]);
                FinancialCategorizerJob::dispatch($this->userId, batchSize: 20, triggerAnalysis: true);
            } else {
                Log::warning('JsonFileIngestionJob: No records created, skipping categorization', [
                    'user_id' => $this->userId,
                ]);
            }

        } catch (\Exception $e) {
            Log::error('JsonFileIngestionJob failed', [
                'user_id' => $this->userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
