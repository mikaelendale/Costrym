<?php

namespace App\Repositories;

use App\Models\CategoryModel;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

/**
 * Repository for CategoryModel
 * Provides data access helpers for categories and related expense lookup.
 */
class CategoryRepository
{
    public function createCategory(array $data)
    {
        // Only allow fillable attributes (model handles mass-assignment protection)
        Log::info('Creating category with data', ['data' => $data]);

        $category = CategoryModel::create($data);
        Log::info('Created category', ['category' => $category]);

        return $category;
    }

    public function getCategories(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        Log::info('getCategories called with filters', ['data' => $filters]);
        $query = CategoryModel::query()->orderBy('created_at', 'desc');

        if (! empty($filters['name'])) {
            $query->where('name', 'LIKE', '%'.$filters['name'].'%');
        }

        if (! empty($filters['description'])) {
            $query->where('description', $filters['description']);
        }

        return $query->paginate($perPage);
    }

    // get only name and description of all categories
    public function getCategoryNamesAndDescriptions()
    {
        Log::info('Fetching category names and descriptions', ['data' => []]);
        $categories = CategoryModel::select('name', 'description')->get();
        Log::info('Categories Repository fetched', ['categories' => $categories->count()]);

        return $categories;
    }
}
