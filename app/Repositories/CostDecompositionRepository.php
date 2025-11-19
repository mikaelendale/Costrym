<?php

namespace App\Repositories;

use App\Models\CompanyData;

class CostDecompositionRepository
{
    public function updateAssociatedCosts(array $data)
    {
        $associatedCosts = CompanyData::where('name', 'associatedCosts')->first();

        // If no record exists, create a new one with the incoming data
        if (! $associatedCosts) {
            $associatedCosts = CompanyData::create([
                'name' => 'associatedCosts',
                'data' => $data,
            ]);

            return $associatedCosts->data;
        }

        // Merge existing data with new batch (prefer new values on key conflicts)
        $existing = $associatedCosts->data;
        $existingArray = is_array($existing) ? $existing : [];
        $mergedData = array_merge($existingArray, $data);

        $associatedCosts->data = $mergedData;
        $associatedCosts->save();

        return $associatedCosts->data;

    }

    public function getassociatedCosts()
    {

        $associatedCosts = CompanyData::where('name', 'associatedCosts')->first();

        $data = $associatedCosts->data;

        return $data;
    }

    public function updateCER(array $data)
    {
        $cer = CompanyData::where('name', 'cer')->first();

        // If no record exists, create a new one with the incoming data
        if (! $cer) {
            $cer = CompanyData::create([
                'name' => 'cer',
                'data' => $data,
            ]);

            return $cer->data;
        }

        // Merge existing data with new batch (prefer new values on key conflicts)
        $existing = $cer->data;
        $existingArray = is_array($existing) ? $existing : [];
        $mergedData = array_merge($existingArray, $data);

        $cer->data = $mergedData;
        $cer->save();

        return $cer->data;

    }

    public function getCER()
    {

        $cer = CompanyData::where('name', 'cer')->first();

        $data = $cer->data;

        return $data;
    }
}
