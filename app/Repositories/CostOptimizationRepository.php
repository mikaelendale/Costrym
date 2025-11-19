<?php

namespace App\Repositories;

use App\Models\CompanyData;
use Illuminate\Support\Facades\Auth;

class CostOptimizationRepository
{
    public function updateCutCostOptimizer(array $data, ?int $userId = null)
    {
        $resolvedUserId = $userId ?? Auth::id();
        $record = CompanyData::where('name', 'cutCostOptimizer')->where('user_id', $resolvedUserId)->first();
        if (! $record) {
            $record = CompanyData::create([
                'name' => 'cutCostOptimizer',
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

    public function getCutCostOptimizer(?int $userId = null): array
    {
        $resolvedUserId = $userId ?? Auth::id();
        $record = CompanyData::where('name', 'cutCostOptimizer')->where('user_id', $resolvedUserId)->first();

        return is_array($record?->data) ? $record->data : [];
    }

    public function updateCostValueAlignment(array $data, ?int $userId = null)
    {
        $resolvedUserId = $userId ?? Auth::id();
        $record = CompanyData::where('name', 'costValueAlignment')->where('user_id', $resolvedUserId)->first();
        if (! $record) {
            $record = CompanyData::create([
                'name' => 'costValueAlignment',
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

    public function getCostValueAlignment(?int $userId = null): array
    {
        $resolvedUserId = $userId ?? Auth::id();
        $record = CompanyData::where('name', 'costValueAlignment')->where('user_id', $resolvedUserId)->first();

        return is_array($record?->data) ? $record->data : [];
    }
}
