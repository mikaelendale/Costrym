<?php

namespace App\Repositories;

use App\Models\CompanyData;
use Illuminate\Support\Facades\Log;

use function is_array;

class AutomationRepository
{
    /**
     * Store or update automations as a whole structure (canonical record).
     */
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

    /**
     * Append automations for a specific category while preserving a map structure.
     * Example stored shape: { "CategoryA": [ ...plans ], "CategoryB": [ ... ] }
     */
    public function updateAutomationsByCategory(string $category, mixed $data, int $userId): array
    {
        $resolvedUserId = $userId;
        $record = CompanyData::where('name', 'automations')->where('user_id', $resolvedUserId)->first();
        if (! $record) {
            $payload = [$category => [
                // store as-is (plan objects or raw strings) to keep flexibility
                $data,
            ]];
            $record = CompanyData::create([
                'name' => 'automations',
                'data' => $payload,
                'user_id' => $resolvedUserId,
            ]);

            return is_array($record->data) ? $record->data : [];
        }

        $existing = $record->data ?? [];
        $existing = is_array($existing) ? $existing : [];

        if (! array_key_exists($category, $existing) || ! is_array($existing[$category])) {
            $existing[$category] = array_key_exists($category, $existing) ? [$existing[$category]] : [];
        }

        $existing[$category][] = $data;

        $record->data = $existing;
        $record->save();

        return is_array($record->data) ? $record->data : [];
    }

    public function getAutomations(int $userId): array
    {
        $resolvedUserId = $userId;
        $record = CompanyData::where('name', 'automations')->where('user_id', $resolvedUserId)->first();

        return is_array($record?->data) ? $record->data : [];
    }

    /**
     * Store or update approval layer as a whole structure (canonical record).
     */
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

    /**
     * Append approval requests for a specific category while preserving a map structure.
     * Example stored shape: { "CategoryA": [ ...approval_requests ], ... }
     */
    public function updateApprovalLayerByCategory(string $category, mixed $data, int $userId): array
    {
        $resolvedUserId = $userId;
        $record = CompanyData::where('name', 'approvalLayer')->where('user_id', $resolvedUserId)->first();
        if (! $record) {
            $payload = [$category => [
                $data,
            ]];
            $record = CompanyData::create([
                'name' => 'approvalLayer',
                'data' => $payload,
                'user_id' => $resolvedUserId,
            ]);

            return is_array($record->data) ? $record->data : [];
        }

        $existing = $record->data ?? [];
        $existing = is_array($existing) ? $existing : [];

        if (! array_key_exists($category, $existing) || ! is_array($existing[$category])) {
            $existing[$category] = array_key_exists($category, $existing) ? [$existing[$category]] : [];
        }

        $existing[$category][] = $data;

        $record->data = $existing;
        $record->save();

        return is_array($record->data) ? $record->data : [];
    }

    public function getApprovalLayer(int $userId): array
    {
        $resolvedUserId = $userId;
        $record = CompanyData::where('name', 'approvalLayer')->where('user_id', $resolvedUserId)->first();

        return is_array($record?->data) ? $record->data : [];
    }
}
