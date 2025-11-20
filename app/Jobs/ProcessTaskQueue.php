<?php

namespace App\Jobs;

use App\Models\TaskQueue;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Vizra\VizraADK\Execution\AgentExecutor;
use Vizra\VizraADK\System\AgentContext;

class ProcessTaskQueue implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300; // 5 minutes

    public int $tries = 3;

    public int $backoff = 60; // 1 minute between retries

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $queueEntryId
    ) {
        $this->onQueue('task_execution');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $queueEntry = TaskQueue::with('task', 'user')->find($this->queueEntryId);

        if (! $queueEntry) {
            Log::warning('Queue entry not found', ['queue_id' => $this->queueEntryId]);

            return;
        }

        // Check if already processed
        if ($queueEntry->status === 'completed') {
            Log::info('Queue entry already completed', ['queue_id' => $this->queueEntryId]);

            return;
        }

        // Mark as processing
        $queueEntry->markAsProcessing();

        Log::info('Processing task from queue', [
            'queue_id' => $queueEntry->id,
            'task_id' => $queueEntry->task_id,
            'agent_name' => $queueEntry->agent_name,
            'user_id' => $queueEntry->user_id,
            'attempt' => $queueEntry->attempts,
        ]);

        try {
            // Check if we should use MasterOrchestrator
            $useMasterOrchestrator = config('agents.task_execution.use_master_orchestrator', true);

            if ($useMasterOrchestrator) {
                // Use MasterOrchestrator as the executor
                $result = $this->executeWithMasterOrchestrator($queueEntry);
            } else {
                // Use direct agent execution (legacy)
                $result = $this->executeWithDirectAgent($queueEntry);
            }

            // Mark as completed
            $queueEntry->markAsCompleted($result);

            // Save workflow as Automation (MD file)
            $this->saveWorkflowAsAutomation($queueEntry, $result);

            Log::info('Task completed successfully', [
                'queue_id' => $queueEntry->id,
                'task_id' => $queueEntry->task_id,
                'result_length' => strlen($result),
            ]);

            // Handle recurring tasks
            $this->handleRecurringTask($queueEntry);
        } catch (\Exception $e) {
            Log::error('Task execution failed', [
                'queue_id' => $queueEntry->id,
                'task_id' => $queueEntry->task_id,
                'error' => $e->getMessage(),
                'attempt' => $queueEntry->attempts,
            ]);

            // Mark as failed
            $queueEntry->markAsFailed($e->getMessage());

            // Re-throw for retry mechanism if attempts remain
            if ($queueEntry->attempts < $queueEntry->max_attempts) {
                throw $e;
            }
        }
    }

    /**
     * Execute task using MasterOrchestratorExecutor
     */
    protected function executeWithMasterOrchestrator(TaskQueue $queueEntry): string
    {
        Log::info('Executing task with MasterOrchestratorExecutor', [
            'queue_id' => $queueEntry->id,
            'task_id' => $queueEntry->task_id,
        ]);

        // Update queue entry to show it's using MasterOrchestratorExecutor
        $queueEntry->update(['agent_name' => 'master_orchestrator_executor']);

        // Get available agents for executor
        $availableAgents = config('agents.available_agents', []);
        $enabledAgents = collect($availableAgents)
            ->filter(fn ($agent) => $agent['enabled'] ?? true)
            ->map(fn ($agent, $key) => [
                'name' => $key,
                'description' => $agent['description'] ?? '',
                'capabilities' => $agent['capabilities'] ?? [],
            ])
            ->values()
            ->toArray();

        // Get task data
        $taskData = $queueEntry->payload['task_data'] ?? [];

        // Build context for executor
        $taskName = $taskData['name'] ?? 'Task Execution';
        $taskDescription = $taskData['description'] ?? '';
        $estimatedSavings = $taskData['estimated_savings'] ?? 'N/A';

        // Execute with MasterOrchestratorExecutor
        $user = $queueEntry->user;
        $sessionId = "task_execution_{$queueEntry->id}_".time();

        $response = \App\Agents\MasterOrchestratorExecutor::run(input: "Execute the approved task: {$taskName}")
            ->forUser($user)
            ->withSession($sessionId)
            ->withContext([
                'task_execution' => true,
                'queue_id' => $queueEntry->id,
                'task_id' => $queueEntry->task_id,
                'user_id' => $queueEntry->user_id,
                'task_data' => $taskData,
                'task_name' => $taskName,
                'task_description' => $taskDescription,
                'estimated_savings' => $estimatedSavings,
                'available_agents' => $enabledAgents,
            ])
            ->go();

        return is_string($response) ? $response : json_encode($response);
    }

    /**
     * Execute task with direct agent selection (legacy)
     */
    protected function executeWithDirectAgent(TaskQueue $queueEntry): string
    {
        // Select the best agent for this task dynamically
        $agentSelector = app(\App\Services\AgentSelector::class);
        $taskData = $queueEntry->payload['task_data'] ?? [];

        $selection = $agentSelector->selectAgent($taskData);
        $agentClass = $selection['agent_class'];
        $agentName = $selection['agent_name'];

        // Update queue entry with selected agent
        $queueEntry->update(['agent_name' => $agentName]);

        Log::info('Agent selected for task execution', [
            'queue_id' => $queueEntry->id,
            'selected_agent' => $agentName,
            'reasoning' => $selection['reasoning'],
        ]);

        if (! $agentClass || ! class_exists($agentClass)) {
            throw new \Exception("Agent class not found: {$agentClass}");
        }

        // Instantiate the agent
        $agent = app($agentClass);

        // Prepare context
        $context = new AgentContext('task_queue_'.$queueEntry->id);
        $context->setState('user_id', $queueEntry->user_id);
        $context->setState('task_id', $queueEntry->task_id);
        $context->setState('queue_id', $queueEntry->id);
        $context->setState('task_data', $queueEntry->payload['task_data'] ?? []);

        // Build prompt from task data
        $prompt = $this->buildPrompt($taskData);

        // Execute the agent
        $executor = AgentExecutor::for($agent)
            ->withContext($context->all());

        $response = $executor->run($prompt);

        // Extract response
        return is_string($response) ? $response : json_encode($response);
    }

    /**
     * Build prompt for MasterOrchestrator
     */
    protected function buildMasterOrchestratorPrompt(array $taskData, array $availableAgents): string
    {
        $name = $taskData['name'] ?? 'Unnamed Task';
        $description = $taskData['description'] ?? 'No description provided';
        $savings = $taskData['estimated_savings'] ?? 'N/A';

        $agentsList = '';
        foreach ($availableAgents as $agent) {
            $capabilities = implode(', ', array_slice($agent['capabilities'], 0, 5));
            $agentsList .= "- **{$agent['name']}**: {$agent['description']}\n  Capabilities: {$capabilities}\n\n";
        }

        return <<<PROMPT
You are the Master Orchestrator executing an approved task.

## Task Details
**Name:** {$name}
**Description:** {$description}
**Estimated Savings:** {$savings}

## Your Mission
Execute this task by delegating to the most appropriate agent(s) from your available team.

## Available Agents
{$agentsList}

## Instructions
1. Analyze the task requirements
2. Select the best agent(s) to accomplish this task
3. Delegate the work using the `delegate_to_sub_agent` tool
4. Coordinate multiple agents if needed
5. Synthesize results into a comprehensive report

## Output Requirements
Provide a detailed report including:
- Actions taken
- Which agent(s) were used and why
- Results achieved
- Specific findings (amounts, opportunities, recommendations)
- Next steps or follow-up actions if any

Focus on delivering actionable insights and measurable results.
PROMPT;
    }

    /**
     * Resolve agent class name from agent_name
     */
    protected function resolveAgentClass(string $agentName): ?string
    {
        // Convert snake_case to StudlyCase
        $className = str_replace('_', '', ucwords($agentName, '_'));

        // Common agent class locations
        $possibleClasses = [
            "App\\Agents\\{$className}",
            "App\\Agents\\{$className}Agent",
            "App\\Agents\\{$className}\\{$className}",
        ];

        foreach ($possibleClasses as $class) {
            if (class_exists($class)) {
                return $class;
            }
        }

        return null;
    }

    /**
     * Build prompt for the agent from task data
     */
    protected function buildPrompt(array $taskData): string
    {
        $name = $taskData['name'] ?? 'Unnamed Task';
        $description = $taskData['description'] ?? 'No description provided';

        return <<<PROMPT
You are executing an approved task.

Task: {$name}

Description: {$description}

Please complete this task and provide a detailed report of:
1. Actions taken
2. Results achieved
3. Any issues encountered
4. Recommendations (if applicable)

Be thorough and specific in your response.
PROMPT;
    }

    /**
     * Save workflow execution result as Automation (MD file)
     */
    protected function saveWorkflowAsAutomation(TaskQueue $queueEntry, string $result): void
    {
        try {
            $taskData = $queueEntry->payload['task_data'] ?? [];
            $taskName = $taskData['name'] ?? 'Task Execution';

            // Create automation record with MD content
            $automation = \App\Models\Automation::create([
                'user_id' => $queueEntry->user_id,
                'task_id' => $queueEntry->task_id,
                'task_queue_id' => $queueEntry->id,
                'type' => 'execution_report',
                'name' => "Execution Report: {$taskName}",
                'description' => $taskData['description'] ?? '',
                'markdown_content' => $result, // Store the MD response
                'metadata' => [
                    'agent_name' => $queueEntry->agent_name,
                    'estimated_savings' => $taskData['estimated_savings'] ?? null,
                    'priority' => $queueEntry->priority,
                    'execution_time' => now()->diffInSeconds($queueEntry->started_at ?? now()),
                    'task_type' => $taskData['task_type'] ?? 'one_time',
                ],
                'status' => 'active',
            ]);

            // Optionally save to storage
            $automation->saveToStorage();

            Log::info('Workflow saved as Automation', [
                'automation_id' => $automation->id,
                'queue_id' => $queueEntry->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to save workflow as Automation', [
                'error' => $e->getMessage(),
                'queue_id' => $queueEntry->id,
            ]);
        }
    }

    /**
     * Handle recurring tasks by scheduling the next execution
     */
    protected function handleRecurringTask(TaskQueue $queueEntry): void
    {
        $taskType = $queueEntry->payload['task_data']['task_type'] ?? 'one_time';

        if ($taskType === 'looping') {
            $schedule = $queueEntry->payload['task_data']['schedule'] ?? 'weekly';

            $nextScheduledAt = match ($schedule) {
                'daily' => now()->addDay(),
                'weekly' => now()->addWeek(),
                'monthly' => now()->addMonth(),
                default => now()->addWeek(),
            };

            // Create a new queue entry for the next execution
            // Agent will be selected dynamically again
            TaskQueue::create([
                'task_id' => $queueEntry->task_id,
                'user_id' => $queueEntry->user_id,
                'agent_name' => null, // Will be selected dynamically
                'status' => 'queued',
                'priority' => $queueEntry->priority,
                'payload' => $queueEntry->payload,
                'max_attempts' => 3,
                'scheduled_at' => $nextScheduledAt,
            ]);

            Log::info('Recurring task scheduled for next execution', [
                'task_id' => $queueEntry->task_id,
                'next_scheduled_at' => $nextScheduledAt,
                'schedule' => $schedule,
                'agent_selection' => 'dynamic',
            ]);
        }
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessTaskQueue job failed permanently', [
            'queue_id' => $this->queueEntryId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
