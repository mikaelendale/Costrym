<?php

namespace App\Services;

use App\Agents\FilterAgent;
use App\Repositories\CompanyProfileRepository;
use Illuminate\Support\Facades\Log;

class PipedreamIngestionService
{
    public function __construct(
        private CompanyProfileRepository $companyProfileRepository,
        private WorkflowService $workflowService,
    ) {}

    public function createCompanyProfilePipedream($from, $data)
    {
        $this->companyProfileRepository->StorePipedream($from, $data);

        Log::info('ingesting expenses for company profile');
        // Request relevant sheet title via FilterAgent

        $this->workflowService->runWorkflow($data);
        // Fetch full context array for the selected company profile and sheet title

    }
}
