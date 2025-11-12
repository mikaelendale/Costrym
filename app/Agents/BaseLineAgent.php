<?php

namespace App\Agents;

use App\Tools\BaseLineAnalysis\RollingAggregateTool;
use Illuminate\Support\Facades\Log;
use Prism\Prism\Text\PendingRequest;
use Vizra\VizraADK\Agents\BaseLlmAgent;
use Vizra\VizraADK\System\AgentContext;

class BaseLineAgent extends BaseLlmAgent
{
    protected string $name = 'base_line_agent';

    protected string $description = 'Analyzes company spending patterns to define baselines, identify recurring costs, and major expense drivers.';

    /**
     * Agent instructions hierarchy (first found wins):
     * 1. Runtime: $agent->setPromptOverride('...')
     * 2. Database: agent_prompt_versions table (if enabled)
     * 3. File: resources/prompts/base_line_agent/default.blade.php
     * 4. Fallback: This property
     *
     * The prompt file has been created for you at:
     * resources/prompts/base_line_agent/default.blade.php
     */
    protected string $instructions = '';

    protected string $model = 'gemini-2.5-flash';

    protected array $tools = [
        RollingAggregateTool::class,
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
    */

    public function beforeToolCall(string $toolName, array $arguments, AgentContext $context): array
    {

        return parent::beforeToolCall($toolName, $arguments, $context);

    }

    public function afterToolResult(string $toolName, string $result, AgentContext $context): string
    {

        return parent::afterToolResult($toolName, $result, $context);

    }

    public function beforeLlmCall(array $inputMessages, AgentContext $context): array
    {
        Log::info('BaseLineAgent before LLM call...');
        Log::info('inputmessages: ', ['inputMessages' => $inputMessages]);
        Log::info('context value: ', ['context' => $context->getAllState()]);

        Log::info('----------------------------------------');

        return parent::beforeLlmCall($inputMessages, $context);
    }

    public function afterLlmResponse(mixed $response, AgentContext $context, ?PendingRequest $request = null): mixed
    {
        Log::info('BaseLineAgent After LLM Call...');
        Log::info('Response: ', ['response' => $response]);
        Log::info('context state: ', ['context_state' => $context->getAllState()]);
        Log::info('request value: ', ['request' => $request ? $request : null]);
        Log::info('----------------------------------------');

        return parent::afterLlmResponse($response, $context, $request);

    }
}
