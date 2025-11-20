<?php

namespace App\Repositories;

use App\Models\CompanyData;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CostDecompositionRepository
{
    public function updateAssociatedCosts(array $data, int $userId)
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

        // Existing stored products
        $existing = $associatedCosts->data;
        $existingProducts = is_array($existing) ? $existing : [];

        // Index existing products by product_name for quick lookup
        $productIndex = [];
        foreach ($existingProducts as $i => $product) {
            if (isset($product['product_name'])) {
                $productIndex[$product['product_name']] = $i;
            }
        }

        foreach ($data as $incomingProduct) {
            // Skip if malformed
            if (! isset($incomingProduct['product_name'])) {
                continue;
            }

            $name = $incomingProduct['product_name'];
            $incomingCosts = isset($incomingProduct['associated_direct_costs']) && is_array($incomingProduct['associated_direct_costs'])
                ? $incomingProduct['associated_direct_costs']
                : [];

            if (array_key_exists($name, $productIndex)) {
                // Merge costs into existing product
                $existingIdx = $productIndex[$name];
                $existingCosts = isset($existingProducts[$existingIdx]['associated_direct_costs']) && is_array($existingProducts[$existingIdx]['associated_direct_costs'])
                    ? $existingProducts[$existingIdx]['associated_direct_costs']
                    : [];

                // Build map keyed by cost item name, prefer incoming on conflicts
                $costMap = [];
                foreach ($existingCosts as $c) {
                    if (isset($c['name'])) {
                        $costMap[$c['name']] = $c;
                    }
                }
                foreach ($incomingCosts as $c) {
                    if (isset($c['name'])) {
                        $costMap[$c['name']] = $c; // overwrite or add
                    }
                }

                // Reassign merged costs
                $existingProducts[$existingIdx]['associated_direct_costs'] = array_values($costMap);
            } else {
                // New product, just append
                $existingProducts[] = [
                    'product_name' => $name,
                    'associated_direct_costs' => $incomingCosts,
                ];
                $productIndex[$name] = count($existingProducts) - 1;
            }
        }

        $associatedCosts->data = $existingProducts;
        $associatedCosts->save();

        return $associatedCosts->data;
    }

    public function getassociatedCosts(int $userId)
    {
        $resolvedUserId = $userId;
        $associatedCosts = CompanyData::where('name', 'associatedCosts')->where('user_id', $resolvedUserId)->first();

        $data = $associatedCosts ? $associatedCosts->data : null;

        return $data;
    }

    public function updateCER(array $data, int $userId)
    {
        $resolvedUserId = $userId;
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

    public function getCER(int $userId)
    {
        $resolvedUserId = $userId;
        $cer = CompanyData::where('name', 'cer')->where('user_id', $resolvedUserId)->first();

        $data = $cer ? $cer->data : null;

        return $data;
    }
}
