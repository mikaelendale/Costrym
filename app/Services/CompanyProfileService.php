<?php

namespace App\Services;

use App\Agents\FilterAgent;
use App\Jobs\BaseLineJob;
use App\Jobs\CategorizeChunkJob;
use App\Repositories\CompanyProfileRepository;
use Illuminate\Support\Facades\Log;

class CompanyProfileService
{
    public function __construct(
        private CompanyProfileRepository $companyProfileRepository,
        private ExpenseIngestionService $expenseIngestionService,
        private CategorizeService $categorizeService,
    ) {}

    public function createCompanyProfile(array $data)
    {
        $this->companyProfileRepository->createCompanyProfile($data);

        $input = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        Log::info('ingesting expenses for company profile');
        // Request relevant sheet title via FilterAgent
        $rawFilterResponse = FilterAgent::run('give me the title')->go();
        Log::info('FilterAgent raw response', ['response' => $rawFilterResponse]);

        $decoded = null;
        if (is_string($rawFilterResponse)) {
            $decoded = json_decode($rawFilterResponse, true);
        } elseif (is_array($rawFilterResponse)) {
            $decoded = $rawFilterResponse;
        }

        $selectedTitle = null;
        if (is_array($decoded) && array_key_exists('title', $decoded)) {
            $selectedTitle = $decoded['title'];
        }

        // Salvage simple plain-text response
        if ($selectedTitle === null && is_string($rawFilterResponse)) {
            $trimmed = trim($rawFilterResponse);
            if (preg_match('/"title"\s*:\s*"([^"]+)"/', $trimmed, $m)) {
                $selectedTitle = $m[1];
            } elseif (preg_match('/^[\w\- ]+$/', $trimmed)) {
                $selectedTitle = $trimmed;
            }
        }

        // Fallback to first imported sheet if agent failed
        if ($selectedTitle === null) {
            $importTitles = $data['title'] ?? [];
            $selectedTitle = is_array($importTitles) && count($importTitles) ? $importTitles[0] : null;
            Log::warning('FilterAgent did not return a valid title; using fallback.', ['fallback_title' => $selectedTitle]);
        }

        if ($selectedTitle === null) {
            Log::error('No title available for company context; aborting categorize step.');

            return; // Cannot proceed
        }

        // Fetch full context array for the selected company profile and sheet title
        $contextCollection = $this->companyProfileRepository->getCompanyContextByTitle($selectedTitle);
        $contextArray = $contextCollection->first(); // first matching company_context (array keyed by sheet title)

        if (! is_array($contextArray)) {
            Log::warning('Company context not found or invalid; skipping categorize.', ['title' => $selectedTitle]);

            return;
        }

        // Extract the rows for the selected sheet title.
        $allSheets = $contextArray; // [sheetTitle => rows]
        $rows = $allSheets[$selectedTitle] ?? null;

        if (! is_array($rows)) {
            Log::warning('Selected sheet rows not found in company context; skipping categorize.', [
                'title' => $selectedTitle,
                'available_sheets' => array_keys($allSheets),
            ]);

            return;
        }

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

        if ($totalData === 0) {
            Log::warning('No data rows found beneath header; skipping chunking.', ['title' => $selectedTitle]);

            return; // nothing to process
        }

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
                title: $selectedTitle,
                rows: $chunkRows,
                chunkIndex: $chunkIndex,
                startRowNumber: $rangeStart,
                endRowNumber: $rangeEnd,
            )->delay(now()->addSeconds($delaySeconds));

            Log::info('Queued CategorizeChunkJob', [
                'title' => $selectedTitle,
                'chunk_index' => $chunkIndex,
                'range' => $rangeStart.'-'.$rangeEnd,
                'rows_in_chunk' => count($chunkRows),
                'delay' => $delaySeconds.'s',
            ]);

            $chunkIndex++;
            $delaySeconds += 20; // space jobs by 20 seconds
        }

        BaseLineJob::dispatch();
        // $this->expenseIngestionService->ingest($input); // Optional future step

    }
}
