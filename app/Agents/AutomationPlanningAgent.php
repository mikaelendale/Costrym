<?php

namespace App\Agents;

use Illuminate\Support\Facades\Log;
use Prism\Prism\Text\PendingRequest;
use Vizra\VizraADK\Agents\BaseLlmAgent;
use Vizra\VizraADK\System\AgentContext;

// use App\Tools\YourTool; // Example: Import your tool

class AutomationPlanningAgent extends BaseLlmAgent
{
    protected string $name = 'automation_planning_agent';

    protected string $description = 'Turns cost optimization tasks into executable step-by-step workflow plans.';

    /**
     * Agent instructions hierarchy (first found wins):
     * 1. Runtime: $agent->setPromptOverride('...')
     * 2. Database: agent_prompt_versions table (if enabled)
     * 3. File: resources/prompts/automation_planning_agent/default.blade.php
     * 4. Fallback: This property
     *
     * The prompt file has been created for you at:
     * resources/prompts/automation_planning_agent/default.blade.php
     */
    protected string $instructions = 'For each incoming cost optimization task, deconstruct reasoning and output an execution_plans JSON: steps with what_to_do, impact, dependencies, risk, tool calls, plus autonomy & summary.';

    protected string $model = '';

    protected array $tools = [
        // Example: YourTool::class,
    ];

    /*

    Optional hook methods to override:

    */
    public function beforeLlmCall(array $inputMessages, AgentContext $context): array
    {
        // $context->setState('custom_data_for_llm', 'some_value');
        // $inputMessages[] = ['role' => 'system', 'content' => 'Additional system note for this call.'];
        return parent::beforeLlmCall($inputMessages, $context);
    }

    public function afterLlmResponse(mixed $response, AgentContext $context, ?PendingRequest $request = null): mixed
    {

        Log::info('response', ['automation_planning_agent Response:', $response]);

        return parent::afterLlmResponse($response, $context, $request);

    }

    public function beforeToolCall(string $toolName, array $arguments, AgentContext $context): array
    {

        return parent::beforeToolCall($toolName, $arguments, $context);

    }

    public function afterToolResult(string $toolName, string $result, AgentContext $context): string
    {

        return parent::afterToolResult($toolName, $result, $context);

    }
}
