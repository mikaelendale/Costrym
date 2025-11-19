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

    public function getCompanyContextByName($name)
    {
        Log::info('Fetching company context for company: '.$name);

        return CompanyProfile::where('name', $name)->pluck('company_context');
    }

    public function StorePipedream($from, $data)
    {
        $pipedreamstore = CompanyProfile::where('name', $from)->first();

        // If no record exists, create a new one with the incoming data
        if (! $pipedreamstore) {
            $pipedreamstore = CompanyProfile::create([
                'name' => $from,
                'data' => $data,
            ]);

            return $pipedreamstore->data;
        }

        // Merge existing data with new batch (prefer new values on key conflicts)
        $existing = $pipedreamstore->data;
        $existingArray = is_array($existing) ? $existing : [];
        $mergedData = array_merge($existingArray, $data);

        $pipedreamstore->data = $mergedData;
        $pipedreamstore->save();

        return $pipedreamstore->data;
    }
}
