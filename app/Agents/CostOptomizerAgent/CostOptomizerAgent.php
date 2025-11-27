<?php

namespace App\Agents\CostOptomizerAgent;

use Vizra\VizraADK\Agents\BaseLlmAgent;
use Vizra\VizraADK\System\AgentContext;

class CostOptomizerAgent extends BaseLlmAgent
{
    protected string $name = 'cost_optomizer_agent';

    protected string $description = 'Orchestrates a team of specialized agents to analyze costs, generate solutions, and simulate financial impacts for optimal cost reduction strategies.';

    protected string $instructions = <<<'INSTRUCTIONS'
***

### CostOptimizer Agent (Orchestrator)

**Persona:**
You are the **CostOptimizer**, a master AI Cost Engineer. Your function is not to perform the analysis yourself, but to act as the conductor of a specialized team of sub-agents. You manage the entire workflow, ensuring data flows correctly from one agent to the next to produce a final, actionable portfolio of cost-cutting strategies.

**Core Task:**
Your goal is to orchestrate a three-step process by invoking the `RootAnalysisAgent`, `SolutionGenerator`, and `CostImpactSimulator` agents in the correct sequence. You will manage the data pipeline and return only the final, validated output from the `CostImpactSimulator`.

**Inputs:**
*   `rawData`: A JSON object containing detailed, unprocessed financial transaction data.
*   `categoryAgentResponse`: A JSON object containing categorized transactions, including names, categories, and tags.
*   `benchMarkData`: A JSON object containing benchmark comparisons, highlighting variances and priorities.

**Step-by-Step Orchestration Logic:**

1.  **Initiate Analysis:** Your first action is to invoke the **`RootAnalysisAgent`** agent. You will pass it all three input data sources (`rawData`, `categoryAgentResponse`, `benchMarkData`). Your instruction to it is to identify the underlying causes for all "High" priority variances found in the benchmark data.
2.  **Generate Solutions:** Once you receive the JSON output from the `RootAnalysisAgent`, you will immediately invoke the **`SolutionGenerator`** agent. You will pass the entire JSON object from the previous step as its sole input. Your instruction to it is to devise actionable solutions for every root cause identified.
3.  **Simulate and Filter:** Upon receiving the JSON output from the `SolutionGenerator`, you will invoke the final agent, the **`CostImpactSimulator`**. You will pass the entire JSON object of potential solutions as its input. Your instruction to it is to quantify the impact of each solution and, most importantly, to filter out any solution where the risk and effort outweigh the potential savings.
4.  **Final Output:** You will receive the final JSON from the `CostImpactSimulator`. Return this JSON object as your final answer, with no modification.

**Strict Output Constraints:**
* Return only a single, valid JSON object (the simulator's output). No prose or markdown.
* The first character must be `{` and the last must be `}`.

***

INSTRUCTIONS;
    // protected array $tools = [];

    protected array $subAgents = [
        RootAnalysisAgent::class,
        SolutionGeneratorAgent::class,
        SearchAgent::class,
        CostImpactSimulatorAgent::class,
    ];

    /**
     * Orchestrate the pipeline sequentially and pass results via context and memory.
     */
    public function execute(mixed $input, AgentContext $context): mixed
    {
        // Step 1: Root Analysis
        $rootAgent = $this->getSubAgent('root_analysis');
        if (! $rootAgent) {
            return 'Configuration error: sub-agent "root_analysis" not found.';
        }

        $rootTask = is_string($input) ? $input : json_encode($input);
        $contextSummary = 'Initial user task to diagnose cost anomalies and identify root causes.';
        [$subName1, $task1, $summary1] = $this->beforeSubAgentDelegation('root_analysis', $rootTask, $contextSummary, $context);

        $rootCtx = new AgentContext($context->getSessionId().'_sub_'.$subName1);
        $rootCtx->setState('delegation_depth', $context->getState('delegation_depth', 0) + 1);
        $rootCtx->setState('pipeline', [
            'user_input' => $input,
        ]);
        if (! empty($summary1)) {
            $rootCtx->addMessage(['role' => 'system', 'content' => "Context from parent agent: {$summary1}"]);
        }

        $rootResult = $rootAgent->execute($task1, $rootCtx);
        $rootResultStr = is_string($rootResult) ? $rootResult : json_encode($rootResult);

        // Persist for visibility across steps
        $context->setState('root_analysis_result', $rootResultStr);
        $this->memory()->remember($rootResultStr, 'root_analysis_result');

        $rootResultProcessed = $this->afterSubAgentDelegation($subName1, $task1, $rootResultStr, $context, $rootCtx);

        // Step 2: Solution Generation (uses Root Analysis)
        $solutionAgent = $this->getSubAgent('solution_generator');
        if (! $solutionAgent) {
            return 'Configuration error: sub-agent "solution_generator" not found.';
        }

        $solutionInput = "ROOT_ANALYSIS_INPUT:\n".$rootResultProcessed."\nRETURN_JSON: proposed_solutions only";
        $contextSummary2 = 'Generate concrete solutions based on the identified root causes.';
        [$subName2, $task2, $summary2] = $this->beforeSubAgentDelegation('solution_generator', $solutionInput, $contextSummary2, $context);

        $solutionCtx = new AgentContext($context->getSessionId().'_sub_'.$subName2);
        $solutionCtx->setState('delegation_depth', $context->getState('delegation_depth', 0) + 1);
        $solutionCtx->setState('pipeline', [
            'user_input' => $input,
            'root_analysis_result' => $rootResultProcessed,
        ]);
        if (! empty($summary2)) {
            $solutionCtx->addMessage(['role' => 'system', 'content' => "Context from parent agent: {$summary2}"]);
        }

        $solutionResult = $solutionAgent->execute($task2, $solutionCtx);
        $solutionResultStr = is_string($solutionResult) ? $solutionResult : json_encode($solutionResult);

        // Persist between steps
        $context->setState('solution_generator_result', $solutionResultStr);
        $this->memory()->remember($solutionResultStr, 'solution_generator_result');

        $solutionResultProcessed = $this->afterSubAgentDelegation($subName2, $task2, $solutionResultStr, $context, $solutionCtx);

        // Step 3: Search (use Solution output to drive targeted queries)
        $searchAgent = $this->getSubAgent('search');
        if (! $searchAgent) {
            return 'Configuration error: sub-agent "search" not found.';
        }

        $searchInput = "SOLUTIONS_INPUT_JSON:\n".$solutionResultProcessed."\nTASK: For each solution, if its description begins with 'search for this:' extract the query and call the web-related_operations tool with operation='search'; otherwise construct a precise query from the title/description. RETURN_JSON: search_insights only";
        $contextSummary3 = 'Run targeted web searches to support solution estimates.';
        [$subName3, $task3, $summary3] = $this->beforeSubAgentDelegation('search', $searchInput, $contextSummary3, $context);

        $searchCtx = new AgentContext($context->getSessionId().'_sub_'.$subName3);
        $searchCtx->setState('delegation_depth', $context->getState('delegation_depth', 0) + 1);
        $searchCtx->setState('pipeline', [
            'user_input' => $input,
            'root_analysis_result' => $rootResultProcessed,
            'solution_generator_result' => $solutionResultProcessed,
        ]);
        if (! empty($summary3)) {
            $searchCtx->addMessage(['role' => 'system', 'content' => "Context from parent agent: {$summary3}"]);
        }

        $searchResult = $searchAgent->execute($task3, $searchCtx);
        $searchResultStr = is_string($searchResult) ? $searchResult : json_encode($searchResult);

        // Persist search output
        $context->setState('search_agent_result', $searchResultStr);
        $this->memory()->remember($searchResultStr, 'search_agent_result');

        $searchResultProcessed = $this->afterSubAgentDelegation($subName3, $task3, $searchResultStr, $context, $searchCtx);

        // Step 4: Cost Impact Simulation (uses Solutions + Search insights)
        $simAgent = $this->getSubAgent('cost_impact_simulator');
        if (! $simAgent) {
            return 'Configuration error: sub-agent "cost_impact_simulator" not found.';
        }

        $simInput = "SOLUTIONS_INPUT_JSON:\n".$solutionResultProcessed."\nSEARCH_INSIGHTS_JSON:\n".$searchResultProcessed."\nRETURN_JSON: cost_cut_portfolio only";
        $contextSummary4 = 'Simulate impact and approve the most effective strategies using search insights.';
        [$subName4, $task4, $summary4] = $this->beforeSubAgentDelegation('cost_impact_simulator', $simInput, $contextSummary4, $context);

        $simCtx = new AgentContext($context->getSessionId().'_sub_'.$subName4);
        $simCtx->setState('delegation_depth', $context->getState('delegation_depth', 0) + 1);
        $simCtx->setState('pipeline', [
            'user_input' => $input,
            'root_analysis_result' => $rootResultProcessed,
            'solution_generator_result' => $solutionResultProcessed,
            'search_agent_result' => $searchResultProcessed,
        ]);
        if (! empty($summary4)) {
            $simCtx->addMessage(['role' => 'system', 'content' => "Context from parent agent: {$summary4}"]);
        }

        $finalResult = $simAgent->execute($task4, $simCtx);
        $finalResultStr = is_string($finalResult) ? $finalResult : json_encode($finalResult);

        // Persist final output
        $context->setState('final_cost_optimization_portfolio', $finalResultStr);
        $this->memory()->remember($finalResultStr, 'final_cost_optimization_portfolio');

        // Ensure strictly JSON-only output (remove any stray commentary)
        $sanitized = $this->sanitizeJsonOutput($finalResultStr);

        return $this->afterSubAgentDelegation($subName4, $task4, $sanitized, $context, $simCtx);
    }

    /**
     * Extract and return the first valid top-level JSON object from a string.
     * Falls back to empty JSON object on failure.
     */
    protected function sanitizeJsonOutput(string $raw): string
    {
        $raw = trim($raw);
        // Quick pass: if it's already valid JSON object, return as-is
        if (str_starts_with($raw, '{') && str_ends_with($raw, '}')) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
        }

        // Attempt to locate first '{' and matching closing '}' scanning from end
        $startPos = strpos($raw, '{');
        if ($startPos === false) {
            return '{}';
        }
        $endPos = strrpos($raw, '}');
        if ($endPos === false || $endPos <= $startPos) {
            return '{}';
        }
        $candidate = substr($raw, $startPos, $endPos - $startPos + 1);
        $decoded = json_decode($candidate, true);
        if (is_array($decoded)) {
            return json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        // Last resort: return empty object
        return '{}';
    }

    /**
     * Updated CostOptomizerAgent.php to override tool assembly so delegate_to_sub_agent is only added once, even across repeated chat turns.
     * The override ensures we don’t append a second instance when the agent instance is reused, which was causing “Multiple tools with the name delegate_to_sub_agent found”.
     *
     * @return array<\Prism\Prism\Tool>
     */
    protected function getToolsForPrism(AgentContext $context): array
    {
        $tools = [];

        // Start with already-loaded tools
        $allTools = $this->loadedTools;

        // Add delegation tool once if sub-agents exist and it's not already loaded
        if (! empty($this->loadedSubAgents) && ! isset($this->loadedTools['delegate_to_sub_agent'])) {
            $delegateTool = new \Vizra\VizraADK\Tools\DelegateToSubAgentTool($this);
            $this->loadedTools['delegate_to_sub_agent'] = $delegateTool;
            $allTools[] = $delegateTool;
        }

        foreach ($allTools as $tool) {
            $definition = $tool->definition();

            $prismTool = $this->createPrismTool($definition);

            $parameterOrder = [];

            if (! empty($definition['parameters']['properties'])) {
                foreach ($definition['parameters']['properties'] as $paramName => $paramDef) {
                    $description = $paramDef['description'] ?? '';
                    $parameterOrder[] = $paramName;

                    switch ($paramDef['type'] ?? 'string') {
                        case 'string':
                            $prismTool = $prismTool->withStringParameter($paramName, $description);
                            break;
                        case 'number':
                        case 'integer':
                            $prismTool = $prismTool->withNumberParameter($paramName, $description);
                            break;
                        case 'boolean':
                            $prismTool = $prismTool->withBooleanParameter($paramName, $description);
                            break;
                        case 'array':
                            $prismTool = $prismTool->withArrayParameter($paramName, $description, new \Prism\Prism\Schema\StringSchema('item', 'Array item'));
                            break;
                        default:
                            $prismTool = $prismTool->withStringParameter($paramName, $description);
                    }
                }
            }

            $prismTool = $prismTool->using(function (...$args) use ($tool, $context, $parameterOrder) {
                try {
                    $hasNamedKeys = ! empty($args) && ! array_is_list($args);

                    $arguments = [];

                    if ($hasNamedKeys) {
                        $arguments = $args;
                    } elseif (count($args) === 1 && is_array($args[0])) {
                        $arguments = $args[0];
                    } else {
                        foreach ($parameterOrder as $index => $paramName) {
                            if (isset($args[$index])) {
                                $arguments[$paramName] = $args[$index];
                            }
                        }
                    }

                    if (! empty($parameterOrder)) {
                        $definition = $tool->definition();
                        $required = $definition['parameters']['required'] ?? [];
                        foreach ($required as $requiredParam) {
                            if (! isset($arguments[$requiredParam]) || $arguments[$requiredParam] === null) {
                                throw new \Vizra\VizraADK\Exceptions\ToolExecutionException("Required parameter '{$requiredParam}' is missing or null");
                            }
                        }
                    }

                    logger()->info('Tool execution starting', [
                        'tool_name' => $tool->definition()['name'],
                        'agent' => $this->getName(),
                        'arguments' => $arguments,
                    ]);

                    event(new \Vizra\VizraADK\Events\ToolCallInitiating($context, $this->getName(), $tool->definition()['name'], $arguments));

                    $processedArgs = $this->beforeToolCall($tool->definition()['name'], $arguments, $context);

                    $result = $tool->execute($processedArgs, $context, $this->memory());

                    logger()->info('Tool execution completed', [
                        'tool_name' => $tool->definition()['name'],
                        'agent' => $this->getName(),
                        'result_length' => strlen($result),
                        'result_preview' => substr($result, 0, 200),
                    ]);

                    $processedResult = $this->afterToolResult($tool->definition()['name'], $result, $context);

                    event(new \Vizra\VizraADK\Events\ToolCallCompleted($context, $this->getName(), $tool->definition()['name'], $processedResult));

                    $context->addMessage([
                        'role' => 'tool',
                        'tool_name' => $tool->definition()['name'],
                        'content' => $processedResult ?: '',
                    ]);

                    return $processedResult;
                } catch (\Throwable $e) {
                    $this->onToolException($tool->definition()['name'], $e, $context);

                    event(new \Vizra\VizraADK\Events\ToolCallFailed($context, $this->getName(), $tool->definition()['name'], $e));

                    throw new \Vizra\VizraADK\Exceptions\ToolExecutionException("Error executing tool '".$tool->definition()['name']."': ".$e->getMessage(), 0, $e);
                }
            });

            $tools[] = $prismTool;
        }

        return $tools;
    }
}
