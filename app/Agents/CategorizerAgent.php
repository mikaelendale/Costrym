<?php

namespace App\Agents;

use Prism\Prism\Text\PendingRequest;
use Vizra\VizraADK\Agents\BaseLlmAgent;
use Vizra\VizraADK\System\AgentContext;

// use App\Tools\YourTool; // Example: Import your tool

class CategorizerAgent extends BaseLlmAgent
{
    protected string $name = 'categorizer_agent';

    protected string $description = 'Maps and normalizes raw category names to a canonical master list.';

    // protected function getPromptData(?AgentContext $context): array
    // {
    //     return [
    //         'company_name' => config('app.company_name'),
    //         'promotions' => $this->getActivePromotions(),
    //         'business_hours' => '9 AM - 5 PM EST',
    //     ];
    // }
    /**
     * Agent instructions hierarchy (first found wins):
     * 1. Runtime: $agent->setPromptOverride('...')
     * 2. Database: agent_prompt_versions table (if enabled)
     * 3. File: resources/prompts/categorizer_agent/default.blade.php
     * 4. Fallback: This property
     *
     * The prompt file has been created for you at:
     * resources/prompts/categorizer_agent/default.blade.php
     */
    protected string $instructions = 'Normalize and map incoming category/name variants to the master category list and return a clean JSON mapping. Keep it precise; see prompt file for full details.';

    protected string $model = '';

    protected array $tools = [
        // Example: YourTool::class,
    ];

    /*

    Optional hook methods to override:

    public function beforeLlmCall(array $inputMessages, AgentContext $context): array
    {
        // $context->setState('custom_data_for_llm', 'some_value');
        // $inputMessages[] = ['role' => 'system', 'content' => 'Additional system note for this call.'];
        return parent::beforeLlmCall($inputMessages, $context);
    }

    public function afterLlmResponse(mixed $response, AgentContext $context, ?PendingRequest $request = null): mixed {

         return parent::afterLlmResponse($response, $context, $request);

    }

    public function beforeToolCall(string $toolName, array $arguments, AgentContext $context): array {

        return parent::beforeToolCall($toolName, $arguments, $context);

    }

    public function afterToolResult(string $toolName, string $result, AgentContext $context): string {

        return parent::afterToolResult($toolName, $result, $context);

    } */
}
