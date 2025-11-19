<?php

namespace App\Services;

use App\Agents\CategorizerAgent;
use App\Repositories\CategoryRepository;
use App\Repositories\ExpenseRepository;
use Illuminate\Support\Facades\Log;

class CategorizeService
{
    public function __construct(
        private CategorizerAgent $categorizerAgent,
        private ExpenseRepository $expenseRepository,
        private CategoryRepository $categoryRepository
    ) {}

    public function categorize(string $input)
    {
        // Run categorizer and parse JSON safely
        Log::info('CategorizeService', ['' => $input]);
        $categorizer_response = CategorizerAgent::run($input)->go();

        try {
            $parsed = CleanUpResponse::extractJsonPayload($categorizer_response);
        } catch (\Throwable $e) {
            // If parsing fails, persist empty expenses and exit early
            $this->expenseRepository->update([]);

            return [];
        }

        Log::info('CategorizeService parsed response', ['parsed' => $parsed]);

        $category = [];
        if (isset($parsed['response']['category']) && is_array($parsed['response']['category'])) {
            $category = $parsed['response']['category'];
        } elseif (isset($parsed['category']) && is_array($parsed['category'])) {
            $category = $parsed['category'];
        }

        $expenses = [];
        if (isset($category['expenses']) && is_array($category['expenses'])) {
            $expenses = $category['expenses'];
        } elseif (isset($parsed['expenses']) && is_array($parsed['expenses'])) {
            $expenses = $parsed['expenses'];
        }

        // Normalize possible map/object-of-expenses to a flat list
        if (is_array($expenses)) {
            $keys = array_keys($expenses);
            $isList = $keys === range(0, count($keys) - 1);
            if (! $isList) {
                $expenses = array_values($expenses);
            }
        } else {
            $expenses = [];
        }

        Log::info('CategorizeService expenses normalized', ['count' => count($expenses)]);

        // Persist only the expenses and return them
        $this->expenseRepository->update($expenses);

        // Also persist each expense grouped under its category without overwriting existing JSON.

        $this->addExpenseToCategory($expenses);

        return $expenses;
    }

    public function addExpenseToCategory($expenseData)
    {
        foreach ($expenseData as $expense) {
            if (is_array($expense) && ! empty($expense['category'])) {
                try {

                    $this->categoryRepository->addExpenseToCategory($expense['category'], $expense);
                } catch (\Throwable $e) {
                    Log::warning('Failed adding expense to category', [
                        'category' => $expense['category'] ?? null,
                        'error' => $e->getMessage(),
                    ]);
                }
            } else {
                Log::info('Skipping expense without category', ['expense' => $expense]);
            }
        }

    }
}
