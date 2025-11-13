<?php

namespace App\Agents;

use App\Tools\GetTotalCostByCategory;
use Illuminate\Support\Facades\Log;
use Prism\Prism\Text\PendingRequest;
use Vizra\VizraADK\Agents\BaseLlmAgent;
use Vizra\VizraADK\System\AgentContext;

// use App\Tools\YourTool; // Example: Import your tool

class CostDecompositionAgent extends BaseLlmAgent
{
    protected string $name = 'cost_decomposition_agent';

    protected string $description = 'Describe what this agent does.';

    /**
     * Agent instructions hierarchy (first found wins):
     * 1. Runtime: $agent->setPromptOverride('...')
     * 2. Database: agent_prompt_versions table (if enabled)
     * 3. File: resources/prompts/cost_decomposition_agent/default.blade.php
     * 4. Fallback: This property
     *
     * The prompt file has been created for you at:
     * resources/prompts/cost_decomposition_agent/default.blade.php
     */
    protected string $instructions = 'You are Cost Decomposition Agent. See resources/prompts/cost_decomposition_agent/default.blade.php for full instructions.';

    protected string $model = 'gpt-4o-mini';

    protected array $tools = [
        GetTotalCostByCategory::class,
        // Example: YourTool::class,
    ];

    public function beforeLlmCall(array $inputMessages, AgentContext $context): array
    {
        // $context->setState('custom_data_for_llm', 'some_value');
        // $inputMessages[] = ['role' => 'system', 'content' => 'Additional system note for this call.'];

        Log::info('CostDecompositionAgent.beforeLlmCall:start', [
            'input_msg' => $inputMessages,
        ]);

        $result = parent::beforeLlmCall($inputMessages, $context);

        return $result;
    }

    public function afterLlmResponse(mixed $response, AgentContext $context, ?PendingRequest $request = null): mixed
    {

        $processed = parent::afterLlmResponse($response, $context, $request);

        Log::info('CostDecompositionAgent.afterLlmResponse:end', [
            'response' => $response,
        ]);

        return $processed;

    }

    public function beforeToolCall(string $toolName, array $arguments, AgentContext $context): array
    {
        // Log::info('CostDecompositionAgent.beforeToolCall:start', [
        //     'agent' => $this->name,
        //     'tool' => $toolName,
        //     'arguments' => $arguments,
        // ]);

        $result = parent::beforeToolCall($toolName, $arguments, $context);

        // Log::info('CostDecompositionAgent.beforeToolCall:end', [
        //     'agent' => $this->name,
        //     'tool' => $toolName,
        //     'arguments' => $result,
        // ]);

        return $result;

    }

    public function afterToolResult(string $toolName, string $result, AgentContext $context): string
    {
        Log::info('CostDecompositionAgent.afterToolResult:start', [
            'agent' => $this->name,
            'tool' => $toolName,
            'result' => $result,
        ]);

        $processed = parent::afterToolResult($toolName, $result, $context);

        return $processed;

    }
}
