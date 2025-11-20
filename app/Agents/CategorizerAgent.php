<?php

namespace App\Agents;

use App\Services\CleanUpResponse;
use Illuminate\Support\Facades\Log;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Text\PendingRequest;
use Vizra\VizraADK\Agents\BaseLlmAgent;
use Vizra\VizraADK\System\AgentContext;

class CategorizerAgent extends BaseLlmAgent
{
    protected string $name = 'categorizer_agent';

    protected string $description = 'AI agent that intelligently categorizes financial transactions into predefined expense categories using transaction descriptions, amounts, and context.';

    protected string $instructions = 'You are a financial transaction categorizer. Use the list_financial_categories tool to see all available categories, then analyze each transaction and assign the most appropriate category_id. Return clean JSON with categorizations.';

    protected string $model = 'gpt-4o-mini';

    protected array $tools = [
        ListFinancialCategoriesTool::class,
    ];

    // // protected ?string $provider = Provider::Groq->value;

    public function beforeLlmCall(array $inputMessages, AgentContext $context): array
    {
        Log::info('CategorizerAgent: Starting categorization', [
            'batch_size' => $context->getState('batch_size'),
            'user_id' => $context->getState('user_id'),
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
