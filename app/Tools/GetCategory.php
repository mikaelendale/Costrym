<?php

namespace App\Tools;

use App\Repositories\CategoryRepository;
use Illuminate\Support\Facades\Log;
use Vizra\VizraADK\Contracts\ToolInterface;
use Vizra\VizraADK\Memory\AgentMemory;
use Vizra\VizraADK\System\AgentContext;

class GetCategory implements ToolInterface
{
    /**
     * Get the tool's definition for the LLM.
     * This structure should be JSON schema compatible.
     */
    public function definition(): array
    {
        return [
            'name' => 'get_category',
            'description' => 'Get list of Categories name and descriptions.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'use_this' => [
                        'type' => 'true',
                        'description' => 'use this tool',
                    ],
                ],
                'required' => ['use_this'],
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
        Log::info('Executing GetCategory tool with arguments', ['arguments' => $arguments]);

        $categoryRepository = new CategoryRepository;
        $categories = $categoryRepository->getCategoryNamesAndDescriptions();
        $result = [
            'status' => 'success',
            'message' => 'Tool get_category executed with arguments: '.json_encode($arguments),
            'data' => $categories,
        ];
        Log::info('Category count', ['count' => count($categories)]);

        // The result MUST be a JSON encoded string.
        return json_encode($result);
    }
}
