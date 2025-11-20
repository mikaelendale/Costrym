<?php

namespace App\Agents;

use App\Tools\FireCrawler;
use App\Tools\GetCompanyContext;
use App\Tools\GetCompanyTitle;
use Illuminate\Support\Facades\Log;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Text\PendingRequest;
use Vizra\VizraADK\Agents\BaseLlmAgent;
use Vizra\VizraADK\System\AgentContext;

// use App\Tools\YourTool; // Example: Import your tool

class BenchmarkingAgent extends BaseLlmAgent
{
    protected string $name = 'benchmarking_agent';

    protected string $description = 'Builds a research-backed should-cost OPEX model using FireCrawler.';

    /**
     * Agent instructions hierarchy (first found wins):
     * 1. Runtime: $agent->setPromptOverride('...')
     * 2. Database: agent_prompt_versions table (if enabled)
     * 3. File: resources/prompts/benchmarking_agent/default.blade.php
     * 4. Fallback: This property
     *
     * The prompt file has been created for you at:
     * resources/prompts/benchmarking_agent/default.blade.php
     */
    protected string $instructions = 'Research company context with FireCrawler and output should_cost_model JSON: cost_area, optimized should_cost_percent_of_opex, justification.';

    // protected ?string $provider = Provider::Groq->value;

    protected string $model = '';

    protected array $tools = [
        FireCrawler::class,
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

    public function beforeToolCall(string $toolName, array $arguments, AgentContext $context): array {

        return parent::beforeToolCall($toolName, $arguments, $context);

    }

    public function afterToolResult(string $toolName, string $result, AgentContext $context): string {

        return parent::afterToolResult($toolName, $result, $context);

    } */

    public function beforeLlmCall(array $inputMessages, AgentContext $context): array
    {
        Log::info('BenchmarkingAgent before LLM call...');
        Log::info('Input messages:', ['messages' => $inputMessages]);

        return parent::beforeLlmCall($inputMessages, $context);
    }

    public function afterLlmResponse(mixed $response, AgentContext $context, ?PendingRequest $request = null): mixed
    {
        Log::info('After BenchmarkingAgent LLM response ...');
        Log::info('Response:', ['response' => $response]);

        return parent::afterLlmResponse($response, $context, $request);

    }
}
