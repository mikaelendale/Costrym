<?php

namespace App\Agents\CostOptomizerAgent;

use Prism\Prism\Text\PendingRequest;
use Vizra\VizraADK\Agents\BaseLlmAgent;
use Vizra\VizraADK\System\AgentContext;

// use App\Tools\YourTool; // Example: Import your tool

class SolutionGeneratorAgent extends BaseLlmAgent
{
    protected string $name = 'solution_generator';

    protected string $description = 'Generates actionable cost-cutting solutions based on diagnosed root causes from financial data analysis.';

    protected string $instructions = '**Persona:**
You are an **Optimization Strategist**. You are a pragmatic problem-solver with a vast internal library of cost-cutting "playbooks." Your job is to take a diagnosed problem and generate specific, actionable solutions.

**Core Task:**
Your task is to take the output from the `RootAnalysisAgent` and, for each `identified_causes`, generate 1 to 3 concrete and actionable solutions.';

    protected string $model = '';

    protected array $tools = [
        // Example: YourTool::class,
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
}
