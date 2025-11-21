<?php

namespace App\Services;

use App\Jobs\AutomationJob;
use App\Jobs\BaseLineJob;
use App\Jobs\CategorizeChunkJob;
use App\Jobs\CostDecomposerJob;
use App\Jobs\CostOptimizationJob;
use Illuminate\Support\Facades\Log;

class WorkflowService
{
    public function __construct(
    ) {}

    public function runWorkflow($data, $title, int $userId)
    {

        $windowData = 15; // number of data rows (excluding header) included per chunk
        $stepData = 15;   // iteration step matches window size for non-overlapping chunks
        $header = $data[0];
        $dataRows = array_slice($data, 1);
        $totalData = count($dataRows);
        $chunkIndex = 0;
        $delaySeconds = 0;
        Log::info('Total data rows', ['totalData' => $totalData, 'dataRowsCount' => $dataRows]);

        for ($start = 0; $start < $totalData; $start += $stepData) {
            $sliceData = array_slice($dataRows, $start, $windowData);
            if (empty($sliceData)) {
                break;
            }

            // Combine header with this slice of data rows
            $chunkRows = array_merge([$header], $sliceData);

            // Human-readable data row numbers (1-based, excluding header)
            $rangeStart = $start + 1;
            $rangeEnd = $start + count($sliceData);

            CategorizeChunkJob::dispatch(
                title: $title,
                rows: $chunkRows,
                chunkIndex: $chunkIndex,
                startRowNumber: $rangeStart,
                endRowNumber: $rangeEnd,
                userId: $userId,
            )->delay(now()->addSeconds($delaySeconds));

            Log::info('Queued CategorizeChunkJob', [
                'title' => $title,
                'chunk_index' => $chunkIndex,
                'range' => $rangeStart.'-'.$rangeEnd,
                'rows_in_chunk' => count($chunkRows),
                'delay' => $delaySeconds.'s',
            ]);

            $chunkIndex++;
            $delaySeconds += 30; // space jobs by 30 seconds to respect token-per-minute limits
        }

        $bufferAfterChunksSeconds = 30;
        $spacingBetweenJobsSeconds = 30;
        $startAfterSeconds = $delaySeconds + $bufferAfterChunksSeconds;

        BaseLineJob::dispatch(userId: $userId)->delay(now()->addSeconds($startAfterSeconds));

        // Run cost decomposition after baseline finishes. We schedule it with a buffer so all categorize chunk jobs have completed.

        CostDecomposerJob::dispatch(userId: $userId)->delay(now()->addSeconds($startAfterSeconds + $spacingBetweenJobsSeconds));

        // CostOptimizationJob::dispatch(userId: $userId)->delay(now()->addSeconds($startAfterSeconds + 2 * $spacingBetweenJobsSeconds));

        // AutomationJob::dispatch(userId: $userId)->delay(now()->addSeconds($startAfterSeconds + 3 * $spacingBetweenJobsSeconds));

        // $this->expenseIngestionService->ingest($input); // Optional future step

    }
}
