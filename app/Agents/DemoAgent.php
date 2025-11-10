<?php

namespace App\Agents;

use Prism\Prism\Text\PendingRequest;
use Vizra\VizraADK\Agents\BaseLlmAgent;
use Vizra\VizraADK\Contracts\ToolInterface;
use Vizra\VizraADK\System\AgentContext;
// use App\Tools\YourTool; // Example: Import your tool

class DemoAgent extends BaseLlmAgent
{
    protected string $name = 'demo_agent';
    /**
     * Agent instructions hierarchy (first found wins):
     * 1. Runtime: $agent->setPromptOverride('...')
     * 2. Database: agent_prompt_versions table (if enabled)
     * 3. File: resources/prompts/demo_agent/default.blade.php
     * 4. Fallback: This property
     * 
     * The prompt file has been created for you at:
     * resources/prompts/demo_agent/default.blade.php
     */
    protected string $description = 'Helps users understand our product features and pricing';

    protected string $instructions = "You are a helpful product assistant for AcmeApp.

        Key product information:
        - AcmeApp is a project management tool for teams
        - Pricing: Free for up to 5 users, $10/user/month for larger teams
        - Features: Task management, team collaboration, time tracking, reporting
        - Integrations: Slack, GitHub, Google Calendar

        Be friendly, concise, and always try to be helpful. If you don't know
        something, be honest about it.";
    protected string $model = 'gpt-5-mini';

    protected array $parameters = [
        'type' => 'object',
        'properties' => [
            'temperature' => ['type' => 'number'],
            'humidity' => ['type' => 'number'],
            'condition' => ['type' => 'string'],
        ],
        'required' => ['temperature', 'condition'],
    ];
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
