<?php

namespace App\Tools;

use App\Repositories\CompanyProfileRepository;
use Illuminate\Support\Facades\Log;
use Vizra\VizraADK\Contracts\ToolInterface;
use Vizra\VizraADK\Memory\AgentMemory;
use Vizra\VizraADK\System\AgentContext;

class GetCompanyContext implements ToolInterface
{
    /**
     * Get the tool's definition for the LLM.
     * This structure should be JSON schema compatible.
     */
    public function definition(): array
    {
        return [
            'name' => 'get_company_context',
            'description' => 'Get Company Context.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    // Define your parameters here
                    // 'example_param' => [
                    //     'type' => 'string',
                    //     'description' => 'An example parameter.'
                    // ],
                    'title' => [
                        'type' => 'string',
                        'description' => 'The title to filter company context by.',
                    ],
                ],
                'required' => ['title'],
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
        $categoryRepository = new CompanyProfileRepository;
        try {
            $categories = $categoryRepository->getCompanyContextByTitle($arguments['title'] ?? null);
        } catch (\Exception $e) {
            Log::error('Error fetching company context', ['error' => $e->getMessage()]);
            $categories = [];
        }
        $result = [
            'status' => 'success',
            'message' => 'Tool get_company_context executed with arguments: '.json_encode($arguments),
            'data' => $categories,
        ];
        Log::info('Executing GetCompanyContext tool', ['result' => $result]);

        // The result MUST be a JSON encoded string.
        return json_encode($result);
    }
}
