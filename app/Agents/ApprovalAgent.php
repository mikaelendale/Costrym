<?php

namespace App\Agents;

use Illuminate\Support\Facades\Log;
use Prism\Prism\Text\PendingRequest;
use Vizra\VizraADK\Agents\BaseLlmAgent;
use Vizra\VizraADK\System\AgentContext;

// use App\Tools\YourTool; // Example: Import your tool

class ApprovalAgent extends BaseLlmAgent
{
    protected string $name = 'approval_agent';

    protected string $description = 'Final checkpoint: summarizes a workflow plan and prepares user-facing approval notification.';

    /**
     * Agent instructions hierarchy (first found wins):
     * 1. Runtime: $agent->setPromptOverride('...')
     * 2. Database: agent_prompt_versions table (if enabled)
     * 3. File: resources/prompts/approval_agent/default.blade.php
     * 4. Fallback: This property
     *
     * The prompt file has been created for you at:
     * resources/prompts/approval_agent/default.blade.php
     */
    protected string $instructions = 'Analyze an execution workflow plan; produce JSON with notification_title, body, update summary, and distilled details (what_to_do, why, impact, dependencies, risk) for approve/reject.';

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
        Log::info('ApprovalAgent.beforeLlmCall:start', [
            'context' => $context,
            'inputMessages' => $inputMessages,
        ]);

        return parent::beforeLlmCall($inputMessages, $context);
    }

    public function afterLlmResponse(mixed $response, AgentContext $context, ?PendingRequest $request = null): mixed
    {
        // Log::info('response', ['approval_agent Response:', $response]);
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
