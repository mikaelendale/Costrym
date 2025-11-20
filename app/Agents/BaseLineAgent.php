<?php

namespace App\Agents;

use App\Services\CleanUpResponse;
use App\Tools\BaseLineAnalysis\RollingAggregateTool;
use App\Tools\GetCompanyContext;
use App\Tools\GetCompanyTitle;
use Illuminate\Support\Facades\Log;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Text\PendingRequest;
use Vizra\VizraADK\Agents\BaseLlmAgent;
use Vizra\VizraADK\System\AgentContext;

class BaseLineAgent extends BaseLlmAgent
{
    protected string $name = 'base_line_agent';

    protected string $description = 'Analyzes company spending patterns to define baselines, identify recurring costs, and major expense drivers.';

    // protected ?string $provider = Provider::Groq->value;

    protected string $model = '';

    protected array $tools = [
        RollingAggregateTool::class,
        GetCompanyTitle::class,
        GetCompanyContext::class,
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
        Log::info('Input messages:', ['messages' => $inputMessages]);

        return parent::beforeLlmCall($inputMessages, $context);
    }

    public function afterLlmResponse(mixed $response, AgentContext $context, ?PendingRequest $request = null): mixed
    {
        Log::info('After BaseLineAgent LLM response ...');
        // $parsedResponse = CleanUpResponse::extractJsonPayload($response);
        // Log::info('Parsed BaseLineAgent response payload.', ['response' => $parsedResponse]);

        Log::info('Finished setting baseline_data state.', ['response' => $response]);

        return parent::afterLlmResponse($response, $context, $request);
    }
}
