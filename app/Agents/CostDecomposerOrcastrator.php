<?php

namespace App\Agents;

use Prism\Prism\Text\PendingRequest;
use Vizra\VizraADK\Agents\BaseLlmAgent;
use Vizra\VizraADK\Agents\SequentialWorkflow;
use Vizra\VizraADK\System\AgentContext;

// use App\Tools\YourTool; // Example: Import your tool

class CostDecomposerOrcastrator extends BaseLlmAgent
{
    protected string $name = 'cost_decomposer_orcastrator';

    protected string $description = 'Describe what this agent does.';

    /**
     * Agent instructions hierarchy (first found wins):
     * 1. Runtime: $agent->setPromptOverride('...')
     * 2. Database: agent_prompt_versions table (if enabled)
     * 3. File: resources/prompts/cost_decomposer_orcastrator/default.blade.php
     * 4. Fallback: This property
     *
     * The prompt file has been created for you at:
     * resources/prompts/cost_decomposer_orcastrator/default.blade.php
     */
    protected string $instructions = 'You are Cost Decomposer Orcastrator. See resources/prompts/cost_decomposer_orcastrator/default.blade.php for full instructions.';

    protected string $model = '';

    protected array $tools = [
        // Example: YourTool::class,
    ];

    public function execute(mixed $input, AgentContext $context): mixed
    {

        $workflow = new SequentialWorkflow;
        $workflow
            ->then('cost_decomposition_agent');
        // ->then('benchmarking_agent')
        // ->then('c_e_r_agent');

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
