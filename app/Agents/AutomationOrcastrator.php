<?php

namespace App\Agents;

use Prism\Prism\Text\PendingRequest;
use Vizra\VizraADK\Agents\BaseLlmAgent;
use Vizra\VizraADK\Agents\SequentialWorkflow;
use Vizra\VizraADK\System\AgentContext;

// use App\Tools\YourTool; // Example: Import your tool

class AutomationOrcastrator extends BaseLlmAgent
{
    protected string $name = 'automation_orcastrator';

    protected string $description = 'Describe what this agent does.';

    /**
     * Agent instructions hierarchy (first found wins):
     * 1. Runtime: $agent->setPromptOverride('...')
     * 2. Database: agent_prompt_versions table (if enabled)
     * 3. File: resources/prompts/automation_orcastrator/default.blade.php
     * 4. Fallback: This property
     *
     * The prompt file has been created for you at:
     * resources/prompts/automation_orcastrator/default.blade.php
     */
    protected string $instructions = <<<'INSTRUCTIONS'
        automation_orcastrator Agent (Orchestrator)
        **Persona:**
        You are the **Automation (CVA) Orchestrator**. Your role is purely orchestration. You sequentially invoke the `Automation planner` then the `Approval Agent` sub-agents. You NEVER perform your own analysis, you ONLY ensure data integrity and sequencing.

        See resources/prompts/automation_orcastrator/default.blade.php for full instructions.

        **Strict Output Constraints:**
        * Return ONLY the final JSON object produced by `Approval Agent`.
        * It must be a single valid JSON object. First character `{`, last character `}`.
        * If multiple tasks are produced internally, ensure the output is a JSON object whose top-level keys are task identifiers or an array inside a single object. Do NOT return prose.
        * Strip any non-JSON commentary from sub-agent outputs before returning.
        ***
        INSTRUCTIONS;

    protected string $model = '';

    protected array $tools = [
        // Example: YourTool::class,
    ];

    public function execute(mixed $input, AgentContext $context): mixed
    {

        $workflow = new SequentialWorkflow;
        $workflow
            ->then('automation_planning_agent')
            ->then('approval_agent');
        $context = new AgentContext('session-123');

        // Execute with input and context
        return $workflow->execute($input, $context);
    }

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
