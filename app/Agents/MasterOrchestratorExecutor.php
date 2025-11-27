<?php

namespace App\Agents;

use Illuminate\Support\Facades\Log;
use Vizra\VizraADK\Agents\BaseLlmAgent;
use Vizra\VizraADK\System\AgentContext;

/**
 * MasterOrchestratorExecutor Agent
 *
 * Specialized agent for executing approved tasks by coordinating and delegating
 * to the most appropriate specialized agents. This agent focuses specifically
 * on task execution and result synthesis.
 */
class MasterOrchestratorExecutor extends BaseLlmAgent
{
    protected string $name = 'master_orchestrator_executor';

    protected string $description = 'Specialized executor that coordinates and delegates approved tasks to the most appropriate specialized agents, synthesizing comprehensive results.';

    protected string $instructions = <<<'INSTRUCTIONS'
You are the Master Orchestrator Executor, responsible for executing approved tasks.

Your role:
1. Analyze the task requirements thoroughly
2. Select the best agent(s) from your available team to accomplish the task
3. Delegate work using the `delegate_to_sub_agent` tool
4. Coordinate multiple agents if needed for complex tasks
5. Synthesize all results into a comprehensive, actionable report

Focus on:
- Delivering measurable results
- Providing specific findings and recommendations
- Including concrete numbers and savings where applicable
- Creating actionable next steps
INSTRUCTIONS;

    protected string $model = 'gpt-4o';

    protected array $tools = [];

    /**
     * Available sub-agents for delegation (loaded from config)
     */
    protected array $subAgents = [];

    /**
     * Get instructions - override to bypass Blade rendering and build dynamically
     */
    public function getInstructions(): string
    {
        // Build instructions dynamically without Blade to avoid $__env issues
        return $this->buildDynamicInstructions();
    }

    /**
     * Before LLM call - load available agents from config
     */
    public function beforeLlmCall(array $inputMessages, AgentContext $context): array
    {
        // Load available agents from config
        $this->loadSubAgentsFromConfig();

        // Add available agents to context
        $context->setState('available_agents', $this->getAvailableAgentsSummary());

        // Add task execution context
        $taskData = $context->getState('task_data', []);
        if (! empty($taskData)) {
            $context->setState('task_name', $taskData['name'] ?? 'Unknown Task');
            $context->setState('task_description', $taskData['description'] ?? '');
            $context->setState('estimated_savings', $taskData['estimated_savings'] ?? 'N/A');
        }

        Log::info('MasterOrchestratorExecutor: Preparing to execute task', [
            'available_agents' => count($this->subAgents),
            'session_id' => $context->getSessionId(),
            'task_name' => $context->getState('task_name'),
        ]);

        return parent::beforeLlmCall($inputMessages, $context);
    }

    /**
     * Build dynamic instructions without Blade template
     */
    protected function buildDynamicInstructions(): string
    {
        // Get task context if available (will be empty on first call)
        return <<<'PROMPT'
# 🎯 Task Execution Mode

You are the **Master Orchestrator Executor**, a specialized AI agent responsible for executing approved tasks.

Your role:
1. Analyze the task requirements thoroughly
2. Select the best approach to accomplish the task
3. Execute the task with available tools and data
4. Provide comprehensive, actionable results

Focus on:
- Delivering measurable results
- Providing specific findings and recommendations
- Including concrete numbers and savings where applicable
- Creating actionable next steps

**IMPORTANT:** Always return your response as a well-formatted Markdown document that will be saved as a report for the user.
PROMPT;
    }

    /**
     * After LLM response - log execution results
     */
    public function afterLlmResponse(mixed $response, AgentContext $context, ?\Prism\Prism\Text\PendingRequest $request = null): mixed
    {
        Log::info('MasterOrchestratorExecutor: Task execution completed', [
            'response_length' => is_string($response) ? strlen($response) : 'non-string',
            'session_id' => $context->getSessionId(),
        ]);

        return parent::afterLlmResponse($response, $context, $request);
    }

    /**
     * Load sub-agents from config
     */
    protected function loadSubAgentsFromConfig(): void
    {
        $configAgents = config('agents.available_agents', []);

        foreach ($configAgents as $key => $agentConfig) {
            // Only include enabled agents
            if ($agentConfig['enabled'] ?? true) {
                $this->subAgents[] = $agentConfig['class'];
            }
        }
    }

    /**
     * Get summary of available agents for context
     */
    protected function getAvailableAgentsSummary(): array
    {
        $summary = [];
        $configAgents = config('agents.available_agents', []);

        foreach ($configAgents as $key => $agentConfig) {
            if ($agentConfig['enabled'] ?? true) {
                $summary[] = [
                    'name' => $key,
                    'description' => $agentConfig['description'] ?? '',
                    'capabilities' => implode(', ', array_slice($agentConfig['capabilities'] ?? [], 0, 5)),
                ];
            }
        }

        return $summary;
    }
}
