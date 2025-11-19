<?php

namespace App\Services;

use App\Agents\FilterAgent;
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

    public function runWorkflow($data, $title = '')
    {

        // Request relevant sheet title via FilterAgent

        // Fetch full context array for the selected company profile and sheet title

        // Extract the rows for the selected sheet title.
        $rows = $data; // [sheetTitle => rows]

        // Always include the first (header) row in every chunk.
        // Data window: 11 data rows per chunk with step of 10 (overlap of last data row), header duplicated each chunk.
        // Yields: header + data 1-11, header + data 11-21, header + data 21-31, ...
        $windowData = 11; // number of data rows (excluding header) per chunk
        $stepData = 10;   // overlap of one data row
        $header = $rows[0];
        $dataRows = array_slice($rows, 1);
        $totalData = count($dataRows);
        $chunkIndex = 0;
        $delaySeconds = 0;

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

        // Stagger follow-up heavy jobs to reduce concurrent token usage.
        // Start after the last CategorizeChunkJob's scheduled delay + a safety buffer,
        // then space each job by an additional fixed interval.
        $bufferAfterChunksSeconds = 1; // 1 minute buffer after last chunk is scheduled
        $spacingBetweenJobsSeconds = 1; // 1 minute between each heavy job

        // Note: $delaySeconds holds the next delay after the loop finished.
        // The last chunk was scheduled with ($delaySeconds - 20) seconds.
        // Using $delaySeconds here intentionally adds at least 20s extra margin.
        $startAfterSeconds = $delaySeconds + $bufferAfterChunksSeconds;

        // BaseLineJob::dispatch()->delay(now()->addSeconds($startAfterSeconds));

        // CostDecomposerJob::dispatch()->delay(now()->addSeconds($startAfterSeconds + $spacingBetweenJobsSeconds));

        // CostOptimizationJob::dispatch()->delay(now()->addSeconds($startAfterSeconds + 2 * $spacingBetweenJobsSeconds));

        // AutomationJob::dispatch()->delay(now()->addSeconds($startAfterSeconds + 3 * $spacingBetweenJobsSeconds));

        // $this->expenseIngestionService->ingest($input); // Optional future step

    }
}
