<?php

namespace App\Agents;

use App\Tools\GetCompanyTitle;
use Prism\Prism\Text\PendingRequest;
use Vizra\VizraADK\Agents\BaseLlmAgent;
use Vizra\VizraADK\System\AgentContext;

// use App\Tools\YourTool; // Example: Import your tool

class FilterAgent extends BaseLlmAgent
{
    protected string $name = 'filter_agent';

    protected string $description = 'Describe what this agent does.';

    /**
     * Agent instructions hierarchy (first found wins):
     * 1. Runtime: $agent->setPromptOverride('...')
     * 2. Database: agent_prompt_versions table (if enabled)
     * 3. File: resources/prompts/filter_agent/default.blade.php
     * 4. Fallback: This property
     *
     * The prompt file has been created for you at:
     * resources/prompts/filter_agent/default.blade.php
     */
    protected string $instructions = 'You are Filter Agent. your job to get company title using GetCompanyTitle tool. and ur job is to check which title is relevant to a place where expenses and cost is found for example income statement, profit and loss, journal entries. 
    you must only select one title that is most relevant to expenses and cost but you must always select one even if none is clearly relevant. Only respond with this json format 
    { "title": "the selected company title" } 
     ';

    protected string $model = '';

    protected array $tools = [
        GetCompanyTitle::class,
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
