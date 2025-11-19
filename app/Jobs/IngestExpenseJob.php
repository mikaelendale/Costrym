<?php

namespace App\Jobs;

use App\Services\ExpenseIngestionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class IngestExpenseJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private array $payload,
        private ?string $sheetName = null,
        private ?int $batchNumber = null,
        private ?int $batchRows = null,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(ExpenseIngestionService $ingestionService): void
    {
        Log::info('IngestExpenseJob: Job started for AI ingestion', [
            'sheet' => $this->sheetName,
            'batch' => $this->batchNumber,
            'batch_rows' => $this->batchRows,
        ]);

        $result = $ingestionService->ingest($this->payload);

        $expensesCount = is_array($result['expenses'] ?? null) ? count($result['expenses']) : 0;
        $errors = is_array($result['errors'] ?? null) ? $result['errors'] : [];

        Log::info('IngestExpenseJob: AI ingestion completed', [
            'sheet' => $this->sheetName,
            'batch' => $this->batchNumber,
            'expenses_count' => $expensesCount,
            'errors' => $errors,
        ]);
    }
}
