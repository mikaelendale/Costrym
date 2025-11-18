<?php

namespace App\Repositories;

use App\Models\CompanyProfile;
use Illuminate\Support\Facades\Log;

class CompanyProfileRepository
{
    // Repository methods for CompanyProfile model
    public function createCompanyProfile(array $data)
    {
        // Only allow fillable attributes (model handles mass-assignment protection)

        $companyProfile = CompanyProfile::create($data);
        Log::info('Created CompanyProfile with ID: '.$companyProfile->id);

        return $companyProfile;
    }

    // get company titles
    public function getCompanyProfileTitles()
    {
        Log::info('Fetching company profile titles');

        return CompanyProfile::pluck('title');
    }

    // get company context and filter by title
    public function getCompanyContextByTitle($title)
    {
        Log::info('Fetching company context for title: '.$title);

        return CompanyProfile::whereJsonContains('title', $title)->pluck('company_context');
    }
}
