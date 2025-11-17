<?php

namespace App\Agents;

use App\Services\CleanUpResponse;
use App\Tools\CreateCategory;
use App\Tools\GetCategory;
use Illuminate\Support\Facades\Log;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Text\PendingRequest;
use Vizra\VizraADK\Agents\BaseLlmAgent;
use Vizra\VizraADK\System\AgentContext;

// use App\Tools\YourTool; // Example: Import your tool

class CategorizerAgent extends BaseLlmAgent
{
    protected string $name = 'categorizer_agent';

    protected string $description = 'Maps and normalizes raw category names to a canonical master list.';

    protected string $instructions = 'Normalize and map incoming category/name variants to the master category list and return a clean JSON mapping. Keep it precise; see prompt file for full details.';

    protected array $tools = [
        GetCategory::class,
        CreateCategory::class,
    ];

    // // protected ?string $provider = Provider::Groq->value;

    public function beforeLlmCall(array $inputMessages, AgentContext $context): array
    {
        Log::info('Before CategorizerAgent LLM call ...', ['input_message' => $inputMessages]);

        return parent::beforeLlmCall($inputMessages, $context);
    }

    public function afterLlmResponse(mixed $response, AgentContext $context, ?PendingRequest $request = null): mixed
    {
        Log::info('After CategorizerAgent LLM response ...');
        $parsedResponse = CleanUpResponse::extractJsonPayload($response);
        Log::info('Parsed CategorizerAgent response payload.', ['response' => $parsedResponse]);

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
