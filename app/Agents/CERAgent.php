<?php

namespace App\Agents;

use App\Tools\CERCalculator;
use Illuminate\Support\Facades\Log;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Text\PendingRequest;
use Vizra\VizraADK\Agents\BaseLlmAgent;
use Vizra\VizraADK\System\AgentContext;

// use App\Tools\YourTool; // Example: Import your tool

class CERAgent extends BaseLlmAgent
{
    protected string $name = 'c_e_r_agent';

    protected string $description = 'Computes cost efficiency ratios: actual OPEX% vs benchmark per category.';

    /**
     * Agent instructions hierarchy (first found wins):
     * 1. Runtime: $agent->setPromptOverride('...')
     * 2. Database: agent_prompt_versions table (if enabled)
     * 3. File: resources/prompts/c_e_r_agent/default.blade.php
     * 4. Fallback: This property
     *
     * The prompt file has been created for you at:
     * resources/prompts/c_e_r_agent/default.blade.php
     */
    protected string $instructions = 'Given should_cost_opex benchmarks, normalize categories and use c_e_r_calculator to output actual/benchmark ratios (0 if unknown or benchmark=0). Return concise normalized JSON.';

    // protected ?string $provider = Provider::Groq->value;

    protected string $model = 'gpt-4o-mini';

    protected array $tools = [
        CERCalculator::class,
        // Example: YourTool::class,
    ];

    // Optional hook methods to override:

    public function beforeLlmCall(array $inputMessages, AgentContext $context): array
    {
        // $context->setState('custom_data_for_llm', 'some_value');
        // $inputMessages[] = ['role' => 'system', 'content' => 'Additional system note for this call.'];
        return parent::beforeLlmCall($inputMessages, $context);
    }

    public function afterLlmResponse(mixed $response, AgentContext $context, ?PendingRequest $request = null): mixed
    {

        return parent::afterLlmResponse($response, $context, $request);

    }

    public function beforeToolCall(string $toolName, array $arguments, AgentContext $context): array
    {
        // Log tool call arguments for traceability
        try {
            Log::info('CERAgent beforeToolCall', [
                'agent' => $this->name,
                'tool' => $toolName,
                'arguments' => $arguments,
            ]);
        } catch (\Throwable $e) {
            // Avoid interrupting flow due to logging issues
        }

        return parent::beforeToolCall($toolName, $arguments, $context);

    }

    public function afterToolResult(string $toolName, string $result, AgentContext $context): string
    {
        // Log the tool result for debugging/analytics
        try {
            $decoded = json_decode($result, true);
            Log::info('CERAgent afterToolResult', [
                'agent' => $this->name,
                'tool' => $toolName,
                'result' => $decoded !== null ? $decoded : $result,
            ]);
        } catch (\Throwable $e) {
            // Avoid interrupting flow due to logging issues
        }

        return parent::afterToolResult($toolName, $result, $context);

    }
}
