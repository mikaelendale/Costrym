<?php

namespace App\Services;

use App\Agents\FilterAgent;
use App\Repositories\CompanyProfileRepository;
use Illuminate\Support\Facades\Log;

class CompanyProfileService
{
    public function __construct(
        private CompanyProfileRepository $companyProfileRepository,
        private WorkflowService $workflowService,
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

        $this->workflowService->runWorkflow($rows, $selectedTitle);

    }
}
