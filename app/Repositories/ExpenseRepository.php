<?php

namespace App\Repositories;

use App\Models\CompanyData;

class ExpenseRepository
{
    public function update(array $data)
    {
        $expense = CompanyData::where('name', 'expense')->first();

        // If no record exists, create a new one with the incoming data
        if (! $expense) {
            $expense = CompanyData::create([
                'name' => 'expense',
                'data' => $data,
            ]);

            return $expense->data;
        }

        // Merge existing data with new batch (prefer new values on key conflicts)
        $existing = $expense->data;
        $existingArray = is_array($existing) ? $existing : [];
        $mergedData = array_merge($existingArray, $data);

        $expense->data = $mergedData;
        $expense->save();

        return $expense->data;

    }

    public function getExpense()
    {

        $expense = CompanyData::where('name', 'expense')->first();

        $data = $expense->data;

        return $data;
    }
}
