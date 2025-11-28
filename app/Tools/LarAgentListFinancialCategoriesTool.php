<?php

namespace App\Tools;

use App\Models\FinancialCategory;
use LarAgent\Tool;

class LarAgentListFinancialCategoriesTool extends Tool
{
    protected string $name = 'list_financial_categories';

    protected string $description = 'Lists all available financial expense categories for classification. Use this to see all valid category IDs and their descriptions before categorizing transactions.';

    protected array $properties = [
        'active_only' => [
            'type' => 'boolean',
            'description' => 'If true, only return active categories. Defaults to true.',
            'default' => true,
        ],
        'include_descriptions' => [
            'type' => 'boolean',
            'description' => 'If true, include full category descriptions. Defaults to true.',
            'default' => true,
        ],
    ];

    protected array $required = [];

    public function execute(array $input): mixed
    {
        $activeOnly = $input['active_only'] ?? true;
        $includeDescriptions = $input['include_descriptions'] ?? true;

        $query = FinancialCategory::query();

        if ($activeOnly) {
            $query->active();
        }

        $categories = $query->ordered()->get();

        $result = $categories->map(function ($category) use ($includeDescriptions) {
            $data = [
                'id' => $category->id,
                'name' => $category->name,
            ];

            if ($includeDescriptions) {
                $data['description'] = $category->description;
            }

            return $data;
        })->toArray();

        return json_encode([
            'success' => true,
            'categories' => $result,
            'total' => count($result),
        ], JSON_PRETTY_PRINT);
    }
}
