<?php

namespace App\Agents;

use App\Services\CleanUpResponse;
use App\Tools\BaseLineAnalysis\RollingAggregateTool;
use Illuminate\Support\Facades\Log;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Text\PendingRequest;
use Prism\Prism\ValueObjects\Messages\SystemMessage;
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

        $company_financials = $context->getState('company_financials');
        $category_mapping = $context->getState('categorized_data');

        $inputMessages[] = new SystemMessage("Company Financials:\n".json_encode($company_financials, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n\nCategory Mapping:\n".json_encode($category_mapping, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return parent::beforeLlmCall($inputMessages, $context);
    }

    public function afterLlmResponse(mixed $response, AgentContext $context, ?PendingRequest $request = null): mixed
    {
        Log::info('After BaseLineAgent LLM response ...');
        $parsedResponse = CleanUpResponse::extractJsonPayload($response);
        Log::info('Parsed BaseLineAgent response payload.', ['response' => $parsedResponse]);

        $context->setState('baseline_data', $parsedResponse);

        Log::info('Finished setting baseline_data state.');

        return parent::afterLlmResponse($response, $context, $request);
    }
}
