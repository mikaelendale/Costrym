<?php

namespace App\Tools;

use LarAgent\Tool;

class GetTotalCostByCategory extends Tool
{
    public string $name = 'get_total_cost_by_category';
    public string $description = 'Get Total Cost By Category.';
    public array $schema = [
        'type' => 'object',
        'properties' => [],
    ];

    public function execute(array $arguments): string
    {
        $mock_catagory = [
            'Cloud & Infrastructure' => 12500,
            'Operations' => 8000,
            'Development' => 15000,
            'Marketing' => 6000,
        ];

        $result = [
            'status' => 'success',
            'message' => 'Tool get_total_cost_by_category executed',
            'data' => $mock_catagory,
        ];

        return json_encode($result);
    }
}
