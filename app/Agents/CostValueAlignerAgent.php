<?php

namespace App\Agents;

use Illuminate\Support\Facades\Log;
use Prism\Prism\Text\PendingRequest;
use Vizra\VizraADK\Agents\BaseLlmAgent;
use Vizra\VizraADK\System\AgentContext;

// use App\Tools\YourTool; // Example: Import your tool

class CostValueAlignerAgent extends BaseLlmAgent
{
    protected string $name = 'cost_value_aligner_agent';

    protected string $description = 'Orchestrates the Cost-to-Value Alignment process by coordinating Value Mapping and Smart Reduction sub-agents.';

    protected string $instructions = <<<'INSTRUCTIONS'
    CostValueAligner Agent (Orchestrator)
**Persona:**
You are the **Cost-to-Value Alignment (CVA) Orchestrator**. Your role is purely orchestration. You sequentially invoke the `ValueMapper` then the `SmartReducer` sub-agents. You NEVER perform your own analysis, you ONLY ensure data integrity and sequencing.

See resources/prompts/cost_value_aligner_agent/default.blade.php for full instructions.

**Strict Output Constraints:**
* Return ONLY the final JSON object produced by `SmartReducer`.
* It must be a single valid JSON object. First character `{`, last character `}`.
* If multiple tasks are produced internally, ensure the output is a JSON object whose top-level keys are task identifiers or an array inside a single object. Do NOT return prose.
* Strip any non-JSON commentary from sub-agent outputs before returning.
***
INSTRUCTIONS;

    protected string $model = 'gpt-4o-mini';

    protected array $subAgents = [
        ValueMapper::class,
        SmartReducer::class,
    ];

    /**
     * Orchestrate sequential delegation: ValueMapper -> SmartReducer.
     * Ensures strict JSON-only final output.
     */
    public function execute(mixed $input, AgentContext $context): mixed
    {
        // Simplified: pass the raw user input straight through to sub-agents
        $userInputRaw = $input;

        // Step 1: ValueMapper
        $valueMapperAgent = $this->getSubAgent('value_mapper');
        if (! $valueMapperAgent) {
            return '{"error":"Sub-agent value_mapper not found"}';
        }

        $valueMapperContextSummary = 'Pass-through: analyze provided mixed input and enrich portfolio.';
        [$valueMapperSubAgentName, $valueMapperTask, $valueMapperParentSummary] = $this->beforeSubAgentDelegation('value_mapper', $userInputRaw, $valueMapperContextSummary, $context);

        $valueMapperContext = new AgentContext($context->getSessionId().'_sub_'.$valueMapperSubAgentName);
        $valueMapperContext->setState('delegation_depth', $context->getState('delegation_depth', 0) + 1);
        $valueMapperContext->setState('pipeline', ['original_input_raw' => $userInputRaw]);
        if (! empty($valueMapperParentSummary)) {
            $valueMapperContext->addMessage(['role' => 'system', 'content' => "Context from parent agent: {$valueMapperParentSummary}"]);
        }

        $valueMapperResult = $valueMapperAgent->execute($valueMapperTask, $valueMapperContext);
        $valueMapperResultString = is_string($valueMapperResult) ? $valueMapperResult : json_encode($valueMapperResult, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $context->setState('value_mapper_result', $valueMapperResultString);
        $this->memory()->remember($valueMapperResultString, 'value_mapper_result');
        $valueMapperProcessedOutput = $this->afterSubAgentDelegation($valueMapperSubAgentName, $valueMapperTask, $valueMapperResultString, $context, $valueMapperContext);

        // Step 2: SmartReducer (uses enriched portfolio)
        $smartReducerAgent = $this->getSubAgent('smart_reducer');
        if (! $smartReducerAgent) {
            return '{"error":"Sub-agent smart_reducer not found"}';
        }

        $smartReducerInput = $valueMapperProcessedOutput; // Entire enriched JSON
        $smartReducerContextSummary = 'Filter value-negative items and output final Executor Task JSON.';
        [$smartReducerSubAgentName, $smartReducerTask, $smartReducerParentSummary] = $this->beforeSubAgentDelegation('smart_reducer', $smartReducerInput, $smartReducerContextSummary, $context);

        $smartReducerContext = new AgentContext($context->getSessionId().'_sub_'.$smartReducerSubAgentName);
        $smartReducerContext->setState('delegation_depth', $context->getState('delegation_depth', 0) + 1);
        $smartReducerContext->setState('pipeline', [
            'original_input_raw' => $userInputRaw,
            'value_mapper_result' => $valueMapperProcessedOutput,
        ]);
        if (! empty($smartReducerParentSummary)) {
            $smartReducerContext->addMessage(['role' => 'system', 'content' => "Context from parent agent: {$smartReducerParentSummary}"]);
        }

        $smartReducerResult = $smartReducerAgent->execute($smartReducerTask, $smartReducerContext);
        $smartReducerResultString = is_string($smartReducerResult) ? $smartReducerResult : json_encode($smartReducerResult, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $context->setState('smart_reducer_result', $smartReducerResultString);
        $this->memory()->remember($smartReducerResultString, 'smart_reducer_result');
        $finalAgentOutputString = $this->afterSubAgentDelegation($smartReducerSubAgentName, $smartReducerTask, $smartReducerResultString, $context, $smartReducerContext);

        // Sanitize & ensure strict JSON
        $finalJson = $this->sanitizeJsonOutput($finalAgentOutputString);

        return $finalJson;
    }

    /**
     * Extract and return the first valid top-level JSON object from a string.
     */
    protected function sanitizeJsonOutput(string $raw): string
    {
        $raw = trim($raw);
        if (str_starts_with($raw, '{') && str_ends_with($raw, '}')) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
        }
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

        return '{}';
    }

    /**
     * Override tools assembly to avoid duplicate delegate tool registration.
     *
     * @return array<\Prism\Prism\Tool>
     */
    protected function getToolsForPrism(AgentContext $context): array
    {
        $tools = [];
        $allTools = $this->loadedTools;
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
                            break;
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
                    event(new \Vizra\VizraADK\Events\ToolCallInitiating($context, $this->getName(), $tool->definition()['name'], $arguments));
                    $processedArgs = $this->beforeToolCall($tool->definition()['name'], $arguments, $context);
                    $result = $tool->execute($processedArgs, $context, $this->memory());
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

    public function afterLlmResponse(mixed $response, AgentContext $context, ?PendingRequest $request = null): mixed
    {

        Log::info('After LLM response cost value aligner .....');
        Log::info('Response: ', ['response' => $response]);

        return parent::afterLlmResponse($response, $context, $request);

    }
}
