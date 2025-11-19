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

    public function runWorkflow($data, $title = '', ?int $userId = null)
    {
        // Data window: 11 data rows per chunk with step of 10 (overlap of last data row), header duplicated each chunk.
        // Yields: header + data 1-11, header + data 11-21, header + data 21-31, ...
        $windowData = 20; // number of data rows (excluding header) per chunk
        $stepData = 10;   // overlap of one data row
        $header = $rows[0];
        $dataRows = array_slice($rows, 1);
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
            $delaySeconds += 20; // space jobs by 20 seconds
        }

        $bufferAfterChunksSeconds = 60; // 1 minute buffer after last chunk is scheduled
        $spacingBetweenJobsSeconds = 60; // 1 minute between each heavy job
        $startAfterSeconds = $delaySeconds + $bufferAfterChunksSeconds;

        BaseLineJob::dispatch(userId: $userId)->delay(now()->addSeconds($startAfterSeconds));

        CostDecomposerJob::dispatch(userId: $userId)->delay(now()->addSeconds($startAfterSeconds + $spacingBetweenJobsSeconds));

        CostOptimizationJob::dispatch(userId: $userId)->delay(now()->addSeconds($startAfterSeconds + 2 * $spacingBetweenJobsSeconds));

        AutomationJob::dispatch(userId: $userId)->delay(now()->addSeconds($startAfterSeconds + 3 * $spacingBetweenJobsSeconds));

        // $this->expenseIngestionService->ingest($input); // Optional future step

    }
}
