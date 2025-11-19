<?php

namespace App\Repositories;

use App\Models\CompanyData;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CostDecompositionRepository
{
    public function updateAssociatedCosts(array $data, ?int $userId = null)
    {
        $resolvedUserId = $userId ?? Auth::id();
        Log::info('CostDecompositionRepository updateAssociatedCosts called', ['user_id' => $resolvedUserId]);

        $associatedCosts = CompanyData::where('name', 'associatedCosts')->where('user_id', $resolvedUserId)->first();

        // If no record exists, create a new one with the incoming data
        if (! $associatedCosts) {
            $associatedCosts = CompanyData::create([
                'name' => 'associatedCosts',
                'data' => $data,
                'user_id' => $resolvedUserId,
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

    public function getassociatedCosts(?int $userId = null)
    {
        $resolvedUserId = $userId ?? Auth::id();
        $associatedCosts = CompanyData::where('name', 'associatedCosts')->where('user_id', $resolvedUserId)->first();

        $data = $associatedCosts ? $associatedCosts->data : null;

        return $data;
    }

    public function updateCER(array $data, ?int $userId = null)
    {
        $resolvedUserId = $userId ?? Auth::id();
        $cer = CompanyData::where('name', 'cer')->where('user_id', $resolvedUserId)->first();

        // If no record exists, create a new one with the incoming data
        if (! $cer) {
            $cer = CompanyData::create([
                'name' => 'cer',
                'data' => $data,
                'user_id' => $resolvedUserId,
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

    public function getCER(?int $userId = null)
    {
        $resolvedUserId = $userId ?? Auth::id();
        $cer = CompanyData::where('name', 'cer')->where('user_id', $resolvedUserId)->first();

        $data = $cer ? $cer->data : null;

        return $data;
    }
}
