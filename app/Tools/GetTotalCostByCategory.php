<?php

namespace App\Tools;

use Vizra\VizraADK\Contracts\ToolInterface;
use Vizra\VizraADK\Memory\AgentMemory;
use Vizra\VizraADK\System\AgentContext;

class GetTotalCostByCategory implements ToolInterface
{
    /**
     * Get the tool's definition for the LLM.
     * This structure should be JSON schema compatible.
     */
    public function definition(): array
    {
        return [
            'name' => 'get_total_cost_by_category',
            'description' => 'Get Total Cost By Category.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    // Define your parameters here
                    // 'example_param' => [
                    //     'type' => 'string',
                    //     'description' => 'An example parameter.'
                    // ],

                ],
                // 'required' => ['example_param'],
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
        // Access arguments: $location = $arguments['location'] ?? null;
        // Access context: $sessionId = $context->getSessionId();
        // Access state: $previousValue = $context->getState('some_key');

        // Implement tool logic here...
        $mock_catagory = [
            'Cloud & Infrastructure' => 12500,
            'Operations' => 8000,
            'Development' => 15000,
            'Marketing' => 6000,
        ];

        $result = [
            'status' => 'success',
            'message' => 'Tool get_total_cost_by_category executed with arguments: '.json_encode($arguments),
            'data' => $mock_catagory,
        ];

        // The result MUST be a JSON encoded string.
        return json_encode($result);
    }
}
