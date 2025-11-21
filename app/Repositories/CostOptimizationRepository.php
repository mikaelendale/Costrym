<?php

namespace App\Repositories;

use App\Models\CompanyData;

class CostOptimizationRepository
{
    public function updateCutCostOptimizer(mixed $data, int $userId)
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

        $record->data = $this->normalizeForStorage($data);
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
    public function updateCutCostOptimizerByCategory(string $category, mixed $data, int $userId)
    {
        $resolvedUserId = $userId;
        $record = CompanyData::where('name', 'cutCostOptimizer')->where('user_id', $resolvedUserId)->first();
        if (! $record) {
            // create with the category containing the first element
            $payload = [$category => [$data]];
            $record = CompanyData::create([
                'name' => 'cutCostOptimizer',
                'data' => $payload,
                'user_id' => $resolvedUserId,
            ]);

            return $record->data;
        }

        $existing = $record->data ?? [];
        $existing = is_array($existing) ? $existing : [];

        // Ensure the category key is an array, preserve scalar if present
        if (! array_key_exists($category, $existing) || ! is_array($existing[$category])) {
            $existing[$category] = array_key_exists($category, $existing) ? [$existing[$category]] : [];
        }

        // Append the raw data (no decoding) as a list entry for this category
        $existing[$category][] = $data;

        $record->data = $existing;
        $record->save();

        return $record->data;
    }

    public function getCutCostOptimizer(int $userId): array
    {
        $resolvedUserId = $userId;
        // Support both the legacy 'cut_cost_optimizer' and the canonical 'cutCostOptimizer'
        $records = CompanyData::whereIn('name', ['cutCostOptimizer', 'cut_cost_optimizer'])
            ->where('user_id', $resolvedUserId)
            ->get();

        if ($records->isEmpty()) {
            return [];
        }

        $merged = [];
        foreach ($records as $rec) {
            $data = $rec->data ?? [];
            $data = is_array($data) ? $data : (is_string($data) ? json_decode($data, true) ?? [] : []);

            foreach ($data as $category => $entries) {
                // Normalize entries to an array
                if (! is_array($entries)) {
                    $entries = [$entries];
                }

                if (! array_key_exists($category, $merged) || ! is_array($merged[$category])) {
                    $merged[$category] = [];
                }

                // Append entries preserving original structure
                $merged[$category] = array_merge($merged[$category], $entries);
            }
        }

        return $merged;
    }

    public function updateCostValueAlignment(mixed $data, int $userId)
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

        $record->data = $this->normalizeForStorage($data);
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
    public function updateCostValueAlignmentByCategory(string $category, mixed $data, int $userId)
    {
        $resolvedUserId = $userId;
        $record = CompanyData::where('name', 'costValueAlignment')->where('user_id', $resolvedUserId)->first();
        if (! $record) {
            $payload = [$category => [$data]];
            $record = CompanyData::create([
                'name' => 'costValueAlignment',
                'data' => $payload,
                'user_id' => $resolvedUserId,
            ]);

            return $record->data;
        }

        $existing = $record->data ?? [];
        $existing = is_array($existing) ? $existing : [];

        if (! array_key_exists($category, $existing) || ! is_array($existing[$category])) {
            $existing[$category] = array_key_exists($category, $existing) ? [$existing[$category]] : [];
        }

        $existing[$category][] = $data;

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

    /**
     * Normalize a payload before storing in CompanyData->data.
     * - If input is a JSON string, decode to array/object (assoc array)
     * - If input is an object, convert to associative array
     * - Otherwise return as-is (array or scalar/string)
     *
     * @return mixed
     */
    private function normalizeForStorage(mixed $data)
    {
        // If it's a string, try to decode JSON and fall back to raw string
        if (is_string($data)) {
            $decoded = json_decode($data, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }

            // not valid JSON: store raw string
            return $data;
        }

        // If it's an object, convert to array
        if (is_object($data)) {
            return json_decode(json_encode($data), true);
        }

        // arrays and scalars pass through
        return $data;
    }
}
