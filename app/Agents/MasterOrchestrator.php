<?php

namespace App\Agents;

use App\Agents\CostOptomizerAgent\CostOptomizerAgent as CostOptimizerAgent;
use Illuminate\Support\Facades\Log;
use Prism\Prism\Text\PendingRequest;
use Prism\Prism\ValueObjects\Messages\SystemMessage;
use Vizra\VizraADK\Agents\BaseLlmAgent;
use Vizra\VizraADK\System\AgentContext;

/**
 * MasterOrchestrator Agent
 *
 * The central coordinator for all specialized agents in the system.
 * This agent has its own queue and can orchestrate complex workflows
 * by delegating tasks to specialized sub-agents.
 */
class MasterOrchestrator extends BaseLlmAgent
{
    protected string $name = 'master_orchestrator';

    protected string $description = 'The master orchestrator that coordinates and delegates tasks to specialized agents based on user requirements. Acts as the central coordinator for all agent workflows in the system.';

    /**
     * Agent instructions hierarchy (first found wins):
     * 1. Runtime: $agent->setPromptOverride('...')
     * 2. Database: agent_prompt_versions table (if enabled)
     * 3. File: resources/prompts/master_orchestrator/default.blade.php
     * 4. Fallback: This property
     */
    protected string $instructions = 'You are the Master Orchestrator for full instructions.';

    protected string $model = 'gpt-4o-mini';

    protected array $tools = [
        \App\Tools\KnowledgeBaseTool::class, // Access user business context
        \App\Tools\QueryFinancialRecordsTool::class, // Query and analyze financial transactions
        \App\Tools\ListFinancialCategoriesTool::class, // List all expense categories
    ];

    /**
     * Available sub-agents that can be orchestrated
     */
    // protected array $subAgents = [
    //     CategorizerAgent::class,
    //     BaseLineAgent::class,
    //     CostDecomposerOrcastrator::class,
    //     CostDecompositionAgent::class,
    //     BenchmarkingAgent::class,
    //     CERAgent::class,
    //     AutomationOrcastrator::class,
    //     AutomationPlanningAgent::class,
    //     ApprovalAgent::class,
    //     CostOptimizerAgent::class,
    //     CostValueAlignerAgent::class,
    //     NotionAgent::class,
    //     OnboardingAgent::class,
    //     SmartReducer::class,
    //     ValueMapper::class,
    // ];

    /**
     * Lifecycle hook: Before LLM call
     * Add context about available agents and user information
     */
    public function beforeLlmCall(array $inputMessages, AgentContext $context): array
    {
        // Add information about available agents to context
        $availableAgents = $this->getAvailableAgentsSummary();
        $context->setState('available_agents', $availableAgents);

        // Add user information if available
        $userId = $context->getState('user_id');
        if ($userId) {
            $userInfo = "User ID: {$userId}";
            $inputMessages[] = new SystemMessage($userInfo);
        }

        // Add any existing workflow state
        $workflowState = $context->getState('workflow_state', []);
        if (! empty($workflowState)) {
            $contextBlock = "Current Workflow State:\n".json_encode($workflowState, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            $inputMessages[] = new SystemMessage($contextBlock);
        }

        Log::info('MasterOrchestrator: Preparing to orchestrate agents', [
            'available_agents' => count($this->subAgents),
            'session_id' => $context->getSessionId(),
            'user_id' => $userId,
        ]);

        return parent::beforeLlmCall($inputMessages, $context);
    }

    /**
     * Lifecycle hook: After LLM response
     * Process and log orchestration results
     */
    public function afterLlmResponse(mixed $response, AgentContext $context, ?PendingRequest $request = null): mixed
    {
        Log::info('MasterOrchestrator: Received LLM response', [
            'response_length' => is_string($response) ? strlen($response) : 'non-string',
            'session_id' => $context->getSessionId(),
        ]);

        // Update workflow state if needed
        $workflowState = $context->getState('workflow_state', []);
        $workflowState['last_orchestration'] = now()->toIso8601String();
        $context->setState('workflow_state', $workflowState);

        return parent::afterLlmResponse($response, $context, $request);
    }

    /**
     * Lifecycle hook: Before tool call
     * Log which agent is about to be invoked
     */
    public function beforeToolCall(string $toolName, array $arguments, AgentContext $context): array
    {
        if ($toolName === 'delegate_to_sub_agent') {
            $agentName = $arguments['agent_name'] ?? 'unknown';
            Log::info('MasterOrchestrator: Delegating to sub-agent', [
                'agent_name' => $agentName,
                'session_id' => $context->getSessionId(),
            ]);

            // Track orchestration in workflow state
            $workflowState = $context->getState('workflow_state', []);
            if (! isset($workflowState['invoked_agents'])) {
                $workflowState['invoked_agents'] = [];
            }
            $workflowState['invoked_agents'][] = [
                'agent' => $agentName,
                'timestamp' => now()->toIso8601String(),
                'arguments' => $arguments,
            ];
            $context->setState('workflow_state', $workflowState);
        }

        return parent::beforeToolCall($toolName, $arguments, $context);
    }

    /**
     * Lifecycle hook: After tool result
     * Process results from delegated agents
     */
    public function afterToolResult(string $toolName, string $result, AgentContext $context): string
    {
        if ($toolName === 'delegate_to_sub_agent') {
            Log::info('MasterOrchestrator: Received result from sub-agent', [
                'result_length' => strlen($result),
                'session_id' => $context->getSessionId(),
            ]);

            // Store result in workflow state for potential use by other agents
            $workflowState = $context->getState('workflow_state', []);
            if (! empty($workflowState['invoked_agents'])) {
                $lastAgent = &$workflowState['invoked_agents'][count($workflowState['invoked_agents']) - 1];
                $lastAgent['result_length'] = strlen($result);
                $lastAgent['result_preview'] = substr($result, 0, 200);
            }
            $context->setState('workflow_state', $workflowState);
        }

        return parent::afterToolResult($toolName, $result, $context);
    }

    /**
     * Get a summary of available agents for context
     */
    protected function getAvailableAgentsSummary(): array
    {
        $summary = [];

        foreach ($this->subAgents as $agentClass) {
            try {
                $agent = app($agentClass);
                $summary[] = [
                    'name' => $agent->getName(),
                    'description' => $agent->getDescription() ?? 'No description available',
                ];
            } catch (\Exception $e) {
                Log::warning('MasterOrchestrator: Could not instantiate agent', [
                    'agent_class' => $agentClass,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $summary;
    }
}
