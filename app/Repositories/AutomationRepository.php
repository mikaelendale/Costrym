<?php

namespace App\Repositories;

use App\Models\CompanyData;

class AutomationRepository
{
    public function updateAutomations(array $data)
    {
        $record = CompanyData::where('name', 'automations')->first();
        if (! $record) {
            $record = CompanyData::create([
                'name' => 'automations',
                'data' => $data,
            ]);

            return $record->data;
        }

        $existing = $record->data;
        $existingArray = is_array($existing) ? $existing : [];
        $merged = array_merge($existingArray, $data);
        $record->data = $merged;
        $record->save();

        return $record->data;
    }

    public function getAutomations(): array
    {
        $record = CompanyData::where('name', 'automations')->first();

        return is_array($record?->data) ? $record->data : [];
    }

    public function updateApprovalLayer(array $data)
    {
        $record = CompanyData::where('name', 'approvalLayer')->first();
        if (! $record) {
            $record = CompanyData::create([
                'name' => 'approvalLayer',
                'data' => $data,
            ]);

            return $record->data;
        }

        $existing = $record->data;
        $existingArray = is_array($existing) ? $existing : [];
        $merged = array_merge($existingArray, $data);
        $record->data = $merged;
        $record->save();

        return $record->data;
    }

    public function getApprovalLayer(): array
    {
        $record = CompanyData::where('name', 'approvalLayer')->first();

        return is_array($record?->data) ? $record->data : [];
    }
}
