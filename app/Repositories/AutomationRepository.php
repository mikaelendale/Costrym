<?php

namespace App\Repositories;

use App\Models\CompanyData;
use Illuminate\Support\Facades\Log;

use function is_array;

class AutomationRepository
{
    public function updateAutomations(array $data, int $userId)
    {
        $resolvedUserId = $userId;
        Log::info('AutomationRepository updateAutomations called', ['user_id' => $resolvedUserId]);
        $record = CompanyData::where('name', 'automations')->where('user_id', $resolvedUserId)->first();

        if (! $record) {
            $record = CompanyData::create([
                'name' => 'automations',
                'data' => $data,
                'user_id' => $resolvedUserId,
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

    public function getAutomations(int $userId): array
    {
        $resolvedUserId = $userId;
        $record = CompanyData::where('name', 'automations')->where('user_id', $resolvedUserId)->first();

        return is_array($record?->data) ? $record->data : [];
    }

    public function updateApprovalLayer(array $data, int $userId)
    {
        $resolvedUserId = $userId;
        $record = CompanyData::where('name', 'approvalLayer')->where('user_id', $resolvedUserId)->first();

        if (! $record) {
            $record = CompanyData::create([
                'name' => 'approvalLayer',
                'data' => $data,
                'user_id' => $resolvedUserId,
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
    }

    public function getApprovalLayer(int $userId): array
    {
        $resolvedUserId = $userId;
        $record = CompanyData::where('name', 'approvalLayer')->where('user_id', $resolvedUserId)->first();

        return is_array($record?->data) ? $record->data : [];
    }
}
