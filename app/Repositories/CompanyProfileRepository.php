<?php

namespace App\Repositories;

use App\Models\CompanyProfile;

class CompanyProfileRepository
{
    // Repository methods for CompanyProfile model
    public function createCompanyProfile(array $data)
    {
        // Only allow fillable attributes (model handles mass-assignment protection)

        $companyProfile = CompanyProfile::create($data);

        return $companyProfile;
    }
}
