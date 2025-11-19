<?php

namespace App\Repositories;

use App\Models\CompanyData;

class CostOptimizationRepository
{
    public function updateCutCostOptimizer(array $data)
    {
        $record = CompanyData::where('name', 'cutCostOptimizer')->first();
        if (! $record) {
            $record = CompanyData::create([
                'name' => 'cutCostOptimizer',
                'data' => $data,
            ]);

            return $record->data;
        }

        $record->data = $data;
        $record->save();

        return $record->data;

        // $existing = $record->data;
        // $existingArray = is_array($existing) ? $existing : [];
        // $merged = array_merge($existingArray, $data);
        // $record->data = $merged;
        // $record->save();

        // return $record->data;
    }

    public function getCutCostOptimizer(): array
    {
        $record = CompanyData::where('name', 'cutCostOptimizer')->first();

        return is_array($record?->data) ? $record->data : [];
    }

    public function updateCostValueAlignment(array $data)
    {
        $record = CompanyData::where('name', 'costValueAlignment')->first();
        if (! $record) {
            $record = CompanyData::create([
                'name' => 'costValueAlignment',
                'data' => $data,
            ]);

            return $record->data;
        }

        $record->data = $data;
        $record->save();

        return $record->data;

        // $existing = $record->data;
        // $existingArray = is_array($existing) ? $existing : [];
        // $merged = array_merge($existingArray, $data);
        // $record->data = $merged;
        // $record->save();

        // return $record->data;
    }

    public function getCostValueAlignment(): array
    {
        $record = CompanyData::where('name', 'costValueAlignment')->first();

        return is_array($record?->data) ? $record->data : [];
    }
}
