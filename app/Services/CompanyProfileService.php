<?php

namespace App\Services;

class CompanyProfileService
{
    protected $companyProfileRepository;

    public function __construct($companyProfileRepository)
    {
        $this->companyProfileRepository = $companyProfileRepository;
    }

    public function createCompanyProfile(array $data)
    {
        return $this->companyProfileRepository->createCompanyProfile($data);
    }
}
