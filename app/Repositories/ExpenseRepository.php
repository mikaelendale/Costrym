<?php

namespace App\Repositories;

use App\Models\CompanyData;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ExpenseRepository
{
    public function update(array $data, ?int $userId = null)
    {
        $resolvedUserId = $userId ?? Auth::id();
        Log::info('ExpenseRepository update called', ['user_id' => $resolvedUserId, 'data' => $data]);

        if (! $resolvedUserId) {
            Log::warning('ExpenseRepository: Missing user_id; aborting update to avoid orphan records.');

            return [];
        }

        $expense = CompanyData::where('name', 'expense')->where('user_id', $resolvedUserId)->first();

        // If no record exists, create a new one with the incoming data
        if (! $expense) {
            $expense = CompanyData::create([
                'name' => 'expense',
                'data' => $data,
                'user_id' => $resolvedUserId,
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

    public function getExpense(?int $userId = null)
    {
        $resolvedUserId = $userId ?? Auth::id();

        if (! $resolvedUserId) {
            Log::warning('ExpenseRepository: Missing user_id in getExpense; returning empty.');

            return [];
        }

        $expense = CompanyData::where('name', 'expense')
            ->where('user_id', $resolvedUserId)
            ->first();

        return $expense?->data ?? [];
    }
}
