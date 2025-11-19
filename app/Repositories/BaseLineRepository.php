<?php

namespace App\Repositories;

use App\Models\CompanyData;

class BaseLineRepository
{
    public function update(array $data)
    {
        $baseline = CompanyData::where('name', 'baseline')->first();

        // If no record exists, create a new one with the incoming data
        if (! $baseline) {
            $baseline = CompanyData::create([
                'name' => 'baseline',
                'data' => $data,
            ]);

            return $baseline->data;
        }

        // Merge existing data with new batch (prefer new values on key conflicts)

        $baseline->data = $data;
        $baseline->save();

        return $baseline->data;
        // $existing = $baseline->data;
        // $existingArray = is_array($existing) ? $existing : [];
        // $mergedData = array_merge($existingArray, $data);

        // $baseline->data = $mergedData;
        // $baseline->save();

        // return $baseline->data;

    }

    public function getBaseline()
    {

        $baseline = CompanyData::where('name', 'baseline')->first();

        $data = $baseline->data;

        return $data;
    }
}
