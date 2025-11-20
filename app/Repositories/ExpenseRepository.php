<?php

namespace App\Repositories;

use App\Models\CompanyData;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ExpenseRepository
{
    public function update(array $data, int $userId)
    {
        $responseUserId = $userId ?? Auth::id();
        Log::info('ExpenseRepository update called', ['user_id' => $responseUserId, 'data' => $data]);

        if (! $responseUserId) {
            Log::warning('ExpenseRepository: Missing user_id; aborting update to avoid orphan records.');

            return [];
        }

        $expense = CompanyData::where('name', 'expense')->where('user_id', $responseUserId)->first();

        // If no record exists, create a new one with the incoming data
        if (! $expense) {
            $expense = CompanyData::create([
                'name' => 'expense',
                'data' => $data,
                'user_id' => $responseUserId,
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

    public function getExpense(int $userId)
    {
        $responseUserId = $userId ?? Auth::id();
        Log::info('ExpenseRepository getExpense called', ['user_id' => $responseUserId, 'trace' => $this->shortTrace()]);
        if (! $responseUserId) {
            Log::warning('ExpenseRepository: Missing user_id in getExpense; returning empty.');

            return [];
        }

        $expense = CompanyData::where('name', 'expense')
            ->where('user_id', $responseUserId)
            ->first();

        return $expense?->data ?? [];
    }

    /**
     * Produce a short (first 5 frames) trace string for logging context.
     */
    protected function shortTrace(): string
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 6);
        // Drop the current frame (this method) to avoid noise
        array_shift($trace);
        $out = [];
        foreach ($trace as $frame) {
            $out[] = ($frame['class'] ?? '').($frame['type'] ?? '').($frame['function'] ?? '').':'.($frame['line'] ?? '');
        }

        return implode(' | ', $out);
    }
}
