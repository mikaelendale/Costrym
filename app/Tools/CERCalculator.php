<?php

namespace App\Tools;

use Vizra\VizraADK\Contracts\ToolInterface;
use Vizra\VizraADK\Memory\AgentMemory;
use Vizra\VizraADK\System\AgentContext;

class CERCalculator implements ToolInterface
{
    /**
     * Get the tool's definition for the LLM.
     * This structure should be JSON schema compatible.
     */
    public function definition(): array
    {
        return [
            'name' => 'c_e_r_calculator',
            'description' => 'Looks up actual OPEX percent per category (mock DB for now) and returns a normalized ratio = actual% / benchmark% for each provided benchmark category. If a category is new/unknown or the benchmark is 0, the normalized value is 0.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'should_cost_opex' => [
                        'type' => 'object',
                        'description' => 'Map of category name => numeric benchmark (should-cost) as OPEX percent values (0-100). The tool will fetch actual OPEX% and compute actual%/benchmark%. Example: {"Marketing": 10, "Sales": 15}',
                        'additionalProperties' => ['type' => ['number', 'string']],
                    ],
                    'categories' => [
                        'type' => 'array',
                        'description' => 'Optional: list of category names to limit computation. Defaults to the keys of should_cost_opex.',
                        'items' => [
                            'type' => 'string',
                        ],
                    ],
                ],
                'required' => ['should_cost_opex'],
            ],
        ];
    }

    /**
     * Execute the tool's logic.
     *
     * @param  array  $arguments  Arguments provided by the LLM, matching the parameters defined above.
     * @param  AgentContext  $context  The current agent context, providing access to session state etc.
     * @return string JSON string representation of the tool's result.
     */
    public function execute(array $arguments, AgentContext $context, AgentMemory $memory): string
    {
        // Validate should_cost_opex (accept array or JSON string)
        $shouldCost = $arguments['should_cost_opex'] ?? null;
        if (is_string($shouldCost)) {
            $decoded = json_decode($shouldCost, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $shouldCost = $decoded;
            }
        }
        if (! is_array($shouldCost)) {
            return json_encode([
                'status' => 'error',
                'message' => 'Invalid or missing should_cost_opex. Expected an object/map of category => numeric value.',
                'received_type' => gettype($arguments['should_cost_opex'] ?? null),
                'hint' => 'Provide should_cost_opex as an object or a JSON string of an object.',
            ]);
        }

        // Normalize shouldCost values to float
        $normalizedShouldCost = [];
        foreach ($shouldCost as $cat => $val) {
            if ($val === null || $val === '') {
                $normalizedShouldCost[(string) $cat] = 0.0;
            } else {
                $normalizedShouldCost[(string) $cat] = is_numeric($val) ? (float) $val : 0.0;
            }
        }

        // Determine categories to compute: either provided explicitly or default to benchmark keys
        $categories = [];
        if (isset($arguments['categories']) && is_array($arguments['categories'])) {
            $categories = array_values(array_unique(array_map('strval', $arguments['categories'])));
        }
        if (empty($categories)) {
            $categories = array_keys($normalizedShouldCost);
        }

        // Fetch actual costs per category (mock DB for now)
        $actuals = $this->getActualCategoryCosts($categories, $context);

        // Build normalized result: normalized = actual% / benchmark%; if unknown/new or benchmark<=0 -> 0
        $normalized = [];
        $unknown = [];
        $details = [];
        foreach ($categories as $cat) {
            $catKey = (string) $cat;
            $benchmark = $normalizedShouldCost[$catKey] ?? null;
            $actual = $actuals[$catKey] ?? null;

            if ($benchmark === null || $benchmark <= 0 || $actual === null) {
                $normalized[$catKey] = 0.0;
                if ($actual === null) {
                    $unknown[] = $catKey; // category not present in DB (new/unknown)
                }
                $details[$catKey] = [
                    'actual' => $actual ?? 0.0,
                    'benchmark' => $benchmark ?? 0.0,
                    'normalized' => 0.0,
                ];

                continue;
            }

            $ratio = $benchmark > 0 ? ($actual / $benchmark) : 0.0;
            $normalized[$catKey] = $ratio;
            $details[$catKey] = [
                'actual' => $actual,
                'benchmark' => $benchmark,
                'normalized' => $ratio,
            ];
        }

        $result = [
            'status' => 'success',
            'normalized' => $normalized,
            'details' => $details,
            'unknown_categories' => $unknown,
            'note' => 'Normalized values are actual OPEX% / benchmark OPEX% using mock DB actuals. Unknown categories or zero benchmarks result in 0.',
        ];

        return json_encode($result);
    }

    /**
     * Mock database lookup for actual category costs.
     * In the future, replace with real DB queries using your models/services and AgentContext.
     *
     * @param  array  $categories  List of category names to fetch
     * @param  AgentContext  $context  Agent context (unused for now)
     * @return array<string,float> Map of category => actual numeric cost
     */
    private function getActualCategoryCosts(array $categories, AgentContext $context): array
    {
        // Example mock data representing actual OPEX percent (0-100) in your database
        // The values are percentages of total OPEX and sum to ~100.
        $mock = [
            'Payroll & Compensation' => 54.0,
            'Marketing' => 10.0,
            'Sales' => 11.0,
            'Cloud & Infrastructure' => 9.0,
            'Software & Subscriptions (SaaS)' => 5.0,
            'Software & Subscriptions' => 5.0, // alias
            'Contractors & Freelancers' => 3.0,
            'Office & Facilities' => 4.0,
            'Financial / Payment Fees' => 1.0,
            'Legal & Professional' => 2.0,
            'Hardware & Equipment' => 0.0,
            'Travel & Entertainment' => 1.0,
            // Intentionally omitting 'Miscellaneous / Other' to simulate an unknown/new category
        ];

        // Filter to requested categories only
        $result = [];
        foreach ($categories as $cat) {
            $key = (string) $cat;
            if (array_key_exists($key, $mock)) {
                $result[$key] = (float) $mock[$key];
            }
        }

        return $result;
    }
}
