<?php

namespace App\Repositories;

use App\Models\CompanyData;

class CostDecompositionRepository
{
    public function updateAssociatedCosts(array $data)
    {
        $associatedCosts = CompanyData::where('name', 'associatedCosts')->first();

        // If no record exists, create a new one with the incoming data
        if (! $associatedCosts) {
            $associatedCosts = CompanyData::create([
                'name' => 'associatedCosts',
                'data' => $data,
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

    public function getassociatedCosts()
    {

        $associatedCosts = CompanyData::where('name', 'associatedCosts')->first();

        $data = $associatedCosts->data;

        return $data;
    }

    public function updateCER(array $data)
    {
        $cer = CompanyData::where('name', 'cer')->first();

        // If no record exists, create a new one with the incoming data
        if (! $cer) {
            $cer = CompanyData::create([
                'name' => 'cer',
                'data' => $data,
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

    public function getCER()
    {

        $cer = CompanyData::where('name', 'cer')->first();

        $data = $cer->data;

        return $data;
    }
}
