<?php

namespace App\Services;

use App\Agents\CategorizerAgent;
use App\Repositories\CategoryRepository;
use App\Repositories\ExpenseRepository;
use Illuminate\Support\Facades\Log;

class CategorizeService
{
    public function __construct(
        private ExpenseRepository $expenseRepository,
        private CategoryRepository $categoryRepository
    ) {}

    public function categorize(string $input, int $userId)
    {
        Log::info('CategorizeService categorize', ['input' => $input]);
        $categorizer_response = CategorizerAgent::run($input)->go();

        try {
            Log::info('CategorizeService received response', ['response' => $categorizer_response]);
            $parsed = CleanUpResponse::extractJsonPayload($categorizer_response);
        } catch (\Throwable $e) {
            Log::error('unable to parser the response', ['error' => $e->getMessage(), 'response' => $categorizer_response]);
            $this->expenseRepository->update([], $userId);

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
        $this->expenseRepository->update($expenses, $userId);

        $this->addExpenseToCategory($expenses, $userId);

        // Filter and persist direct costs (tag contains "Direct")
        $directCosts = $this->filterDirectCosts($expenses);
        if (! empty($directCosts)) {
            Log::info('CategorizeService direct costs extracted', ['count' => count($directCosts)]);
            $this->expenseRepository->updateDirectCosts($directCosts, $userId);
        } else {
            Log::info('CategorizeService no direct costs found');
        }

        return $expenses;
    }

    public function addExpenseToCategory($expenseData, $userId)
    {
        foreach ($expenseData as $expense) {
            if (is_array($expense) && ! empty($expense['category'])) {
                try {

                    $this->categoryRepository->addExpenseToCategory($expense['category'], $expense, $userId);
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

    public function updateDirectCosts(array $directCosts, int $userId): void
    {

        $this->expenseRepository->updateDirectCosts($directCosts, $userId);
    }

    protected function filterDirectCosts(array $expenses): array
    {
        $direct = [];
        Log::info('filterDirectCosts invoked', ['expenses' => $expenses]);
        foreach ($expenses as $expense['expenses']) {
            if (! is_array($expense)) {
                continue;
            }
            $tags = $expense['tags'] ?? [];
            if (is_string($tags)) {
                $tags = [$tags];
            }
            if (! is_array($tags)) {
                continue;
            }
            // Case-insensitive match in case upstream changes capitalization
            $lower = array_map(fn ($t) => is_string($t) ? strtolower($t) : $t, $tags);
            if (in_array('direct', $lower, true)) {
                $direct[] = $expense;
            }
        }

        return $direct;
    }
}
