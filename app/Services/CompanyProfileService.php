<?php

namespace App\Services;

use App\Repositories\CompanyProfileRepository;
use Illuminate\Support\Facades\Log;

class CompanyProfileService
{
    public function __construct(private CompanyProfileRepository $companyProfileRepository,
        private ExpenseIngestionService $expenseIngestionService) {}

    public function createCompanyProfile(array $data)
    {
        // $this->companyProfileRepository->createCompanyProfile($data);
        $input = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        Log::info('ingesting expenses for company profile', ['input' => $input]);

        $this->expenseIngestionService->ingest($input);

    }
}
