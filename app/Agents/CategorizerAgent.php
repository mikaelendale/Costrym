<?php

namespace App\Agents;

use App\Services\CleanUpResponse;
use App\Tools\GetCategory;
use App\Tools\GetCompanyContext;
use Illuminate\Support\Facades\Log;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Text\PendingRequest;
use Vizra\VizraADK\Agents\BaseLlmAgent;
use Vizra\VizraADK\System\AgentContext;

class CategorizerAgent extends BaseLlmAgent
{
    protected string $name = 'categorizer_agent';

    protected string $description = 'AI agent that intelligently categorizes financial transactions into predefined expense categories using transaction descriptions, amounts, and context.';

    protected string $instructions = 'You are a financial transaction categorizer. Use the GetCompanyContext tool to see all available categories, then analyze each transaction and assign the most appropriate category_id. Return clean JSON with categorizations.';

    protected string $model = 'gpt-4o-mini';

    protected array $tools = [
        GetCompanyContext::class,
        GetCategory::class,
    ];

    // // protected ?string $provider = Provider::Groq->value;

    public function beforeLlmCall(array $inputMessages, AgentContext $context): array
    {
        // Avoid logging full message payloads; log counts to reduce verbosity
        $count = is_array($inputMessages) ? count($inputMessages) : 0;
        Log::info('CategorizerAgent: Starting categorization', [
            'input_messages_count' => $count,
            // 'batch_size' => $context->getState('batch_size'),
            // 'user_id' => $context->getState('user_id'),
        ]);

        return parent::beforeLlmCall($inputMessages, $context);
    }

    public function afterLlmResponse(mixed $response, AgentContext $context, ?PendingRequest $request = null): mixed
    {
        Log::info('After CategorizerAgent LLM response ...');
        // $parsedResponse = CleanUpResponse::extractJsonPayload($response);
        // Log::info('Parsed CategorizerAgent response payload.', ['response' => $parsedResponse]);

        // Log::info('categorizer agent response', ['categorizer agent response' => $response]);

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
