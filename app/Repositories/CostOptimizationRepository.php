<?php

namespace App\Repositories;

use App\Models\CompanyData;

class CostOptimizationRepository
{
    public function updateCutCostOptimizer(array $data, int $userId)
    {
        $resolvedUserId = $userId;
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

    /**
     * Merge or set optimization results for a specific category. Stores under an associative
     * map keyed by category name while preserving overall record shape.
     *
     * @param  array<mixed>  $data  the optimizer output for this category
     */
    public function updateCutCostOptimizerByCategory(string $category, array $data, int $userId)
    {
        $resolvedUserId = $userId;
        $record = CompanyData::where('name', 'cutCostOptimizer')->where('user_id', $resolvedUserId)->first();
        if (! $record) {
            $payload = [$category => $data];
            $record = CompanyData::create([
                'name' => 'cutCostOptimizer',
                'data' => $payload,
                'user_id' => $resolvedUserId,
            ]);

            return $record->data;
        }

        $existing = $record->data ?? [];
        $existing = is_array($existing) ? $existing : [];

        // store/overwrite category-specific entry
        $existing[$category] = $data;

        $record->data = $existing;
        $record->save();

        return $record->data;
    }

    public function getCutCostOptimizer(int $userId): array
    {
        $resolvedUserId = $userId;
        $record = CompanyData::where('name', 'cutCostOptimizer')->where('user_id', $resolvedUserId)->first();

        return is_array($record?->data) ? $record->data : [];
    }

    public function updateCostValueAlignment(array $data, int $userId)
    {
        $resolvedUserId = $userId;
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

    /**
     * Merge or set alignment results for a specific category. Stores under an associative
     * map keyed by category name while preserving overall record shape.
     */
    public function updateCostValueAlignmentByCategory(string $category, array $data, int $userId)
    {
        $resolvedUserId = $userId;
        $record = CompanyData::where('name', 'costValueAlignment')->where('user_id', $resolvedUserId)->first();
        if (! $record) {
            $payload = [$category => $data];
            $record = CompanyData::create([
                'name' => 'costValueAlignment',
                'data' => $payload,
                'user_id' => $resolvedUserId,
            ]);

            return $record->data;
        }

        $existing = $record->data ?? [];
        $existing = is_array($existing) ? $existing : [];

        // store/overwrite category-specific entry
        $existing[$category] = $data;

        $record->data = $existing;
        $record->save();

        return $record->data;
    }

    public function getCostValueAlignment(int $userId): array
    {
        $resolvedUserId = $userId;
        $record = CompanyData::where('name', 'costValueAlignment')->where('user_id', $resolvedUserId)->first();

        return is_array($record?->data) ? $record->data : [];
    }
}
