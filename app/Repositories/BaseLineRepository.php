<?php

namespace App\Repositories;

use App\Models\CompanyData;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class BaseLineRepository
{
    public function update(array $data, ?int $userId)
    {
        Log::info('BaseLineRepository update called', ['user_id' => $userId]);
        $baseline = CompanyData::where('name', 'baseline')->where('user_id', $userId)->first();

        // If no record exists, create a new one with the incoming data
        if (! $baseline) {
            $baseline = CompanyData::create([
                'name' => 'baseline',
                'data' => $data,
                'user_id' => $userId,
            ]);

            return $baseline->data;
        }

        // Merge existing data with new batch (prefer new values on key conflicts)
        $existing = $baseline->data;
        $existingArray = is_array($existing) ? $existing : [];
        $mergedData = array_merge($existingArray, $data);

        $baseline->data = $mergedData;
        $baseline->save();

        return $baseline->data;

    }

    public function getBaseline(?int $userId = null)
    {
        $resolvedUserId = $userId ?? Auth::id();
        $baseline = CompanyData::where('name', 'baseline')->where('user_id', $resolvedUserId)->first();

        $data = $baseline?->data;

        return $data;
    }
}
