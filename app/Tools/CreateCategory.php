<?php

namespace App\Tools;

use App\Repositories\CategoryRepository;
use Illuminate\Support\Facades\Log;
use Vizra\VizraADK\Contracts\ToolInterface;
use Vizra\VizraADK\Memory\AgentMemory;
use Vizra\VizraADK\System\AgentContext;

class CreateCategory implements ToolInterface
{
    /**
     * Get the tool's definition for the LLM.
     * This structure should be JSON schema compatible.
     */
    public function definition(): array
    {
        return [
            'name' => 'create_category',
            'description' => 'Create Category.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    // Define your parameters here
                    'name' => [
                        'type' => 'string',
                        'description' => 'The name of the category.',
                    ],
                    'description' => [
                        'type' => 'string',
                        'description' => 'The description of the category.',
                    ],
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
        Log::info('Executing CreateCategory tool with arguments', ['arguments' => $arguments]);

        // Implement tool logic here...
        $categoryRepository = new CategoryRepository;
        $categories = $categoryRepository->createCategory($arguments);

        $result = [
            'status' => 'success',
            'message' => 'Tool create_category executed with arguments: '.json_encode(value: $categories),
            // Add relevant data to the result
        ];

        // The result MUST be a JSON encoded string.
        return json_encode($result);
    }
}
