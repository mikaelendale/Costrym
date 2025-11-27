<?php

namespace App\Agents\CostOptomizerAgent;

use App\Tools\FirecrawlTool;
use Prism\Prism\Text\PendingRequest;
use Vizra\VizraADK\Agents\BaseLlmAgent;
use Vizra\VizraADK\System\AgentContext;

class SearchAgent extends BaseLlmAgent
{
    protected string $name = 'search';

    protected string $description = 'Runs targeted web searches using FirecrawlTool based on SolutionGenerator outputs to gather alternatives, pricing, and implementation insights.';

    protected string $instructions = <<<'INSTRUCTIONS'
        ***

        ### SearchAgent

        Persona:
        You are a focused web research assistant. Your sole job is to extract concrete queries from incoming `proposed_solutions` and call the `web-related_operations` tool (FirecrawlTool) to retrieve relevant results (vendors, pricing pages, benchmarks, implementation guides).

        Core Task:
        For every item in `proposed_solutions`:
        - If the `solution_description` starts with `search for this:` extract the query that follows and CALL the `web-related_operations` tool with operation='search' and the query string.
        - Otherwise, construct a precise query from `solution_title` and `solution_description` that targets alternates, optimization strategies, or pricing comparisons and CALL the `web-related_operations` tool with operation='search'.
        - DO NOT skip the tool call; you must use the `web-related_operations` tool (with operation='search') for every solution.

        Inputs:
        - A JSON object with `proposed_solutions` from the SolutionGenerator.

        Output Schema (Return Exactly One JSON Object):
        {
            "search_insights": [
                {
                    "problem_area": "category/vendor name",
                    "solution_title": "title from proposed solution",
                    "identified_cause": "exact cause string",
                    "query": "the query used for web search",
                    "results": [ { "title": "...", "url": "...", "description": "..." } ],
                    "insight_summary": "brief synthesis of the most actionable findings"
                }
            ]
        }

        Strict Constraints:
        - Always call the `web-related_operations` tool with operation='search' once per solution.
        - Return only JSON; no prose or markdown.
        - If nothing is found, return { "search_insights": [] }.

        ***
        INSTRUCTIONS;

    protected array $tools = [
        FirecrawlTool::class,
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
