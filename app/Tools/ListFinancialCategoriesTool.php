<?php

namespace App\Tools;

use App\Models\FinancialCategory;
use Vizra\VizraADK\Contracts\ToolInterface;
use Vizra\VizraADK\Memory\AgentMemory;
use Vizra\VizraADK\System\AgentContext;

class ListFinancialCategoriesTool implements ToolInterface
{
    public function definition(): array
    {
        return [
            'name' => 'list_financial_categories',
            'description' => 'Lists all available financial expense categories for classification. Use this to see all valid category IDs and their descriptions before categorizing transactions.',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
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
                ],
                'required' => [],
            ],
        ];
    }

    public function execute(array $arguments, AgentContext $context, AgentMemory $memory): string
    {
        $activeOnly = $arguments['active_only'] ?? true;
        $includeDescriptions = $arguments['include_descriptions'] ?? true;

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
