<?php

namespace App\Jobs;

use App\Agents\MasterOrchestrator;
use App\Models\KnowledgeBase;
use App\Models\Task;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class MasterOrchestratorJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [60, 120, 300];

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $userId,
        public string $scenario = 'first_onboarding',
        public ?array $additionalContext = null
    ) {
        $this->onQueue('master_orchestrator');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('MasterOrchestratorJob started', [
            'user_id' => $this->userId,
            'scenario' => $this->scenario,
        ]);

        try {
            $user = User::findOrFail($this->userId);

            // Build dynamic prompt based on scenario
            $prompt = $this->buildPromptForScenario($user);

            Log::info('MasterOrchestratorJob: Running agent', [
                'scenario' => $this->scenario,
                'prompt_length' => strlen($prompt),
            ]);

            // Run MasterOrchestrator agent
            $sessionId = "master_orchestrator_{$this->userId}_{$this->scenario}_".time();
            $agentResponse = MasterOrchestrator::run(input: $prompt)
                ->forUser($user)
                ->withSession($sessionId)
                ->withContext([
                    'scenario' => $this->scenario,
                    'user_id' => $this->userId,
                ])
                ->go();

            Log::info('MasterOrchestratorJob: Agent response received', [
                'response_length' => strlen($agentResponse),
            ]);

            // Process response based on scenario
            $this->processResponse($agentResponse, $user);

            Log::info('MasterOrchestratorJob completed', [
                'user_id' => $this->userId,
                'scenario' => $this->scenario,
            ]);

        } catch (\Exception $e) {
            Log::error('MasterOrchestratorJob failed', [
                'user_id' => $this->userId,
                'scenario' => $this->scenario,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Build dynamic prompt based on scenario
     */
    protected function buildPromptForScenario(User $user): string
    {
        // Get user context from knowledge base
        $knowledgeBase = KnowledgeBase::where('user_id', $user->id)->first();
        $userContext = $knowledgeBase?->context ?? [];

        // Get existing tasks count
        $existingTasksCount = Task::where('user_id', $user->id)->count();

        // Prepare common data
        $promptData = [
            'user' => $user,
            'user_context' => $userContext,
            'scenario' => $this->scenario,
            'existing_tasks_count' => $existingTasksCount,
            'company_name' => $userContext['company_name'] ?? 'the company',
            'industry' => $userContext['industry'] ?? 'their industry',
            'financial_goals' => $userContext['financial_goals'] ?? [],
            'priorities' => $userContext['priorities'] ?? [],
            'pain_points' => $userContext['pain_points'] ?? [],
            'additional_context' => $this->additionalContext ?? [],
        ];

        // Render scenario-specific prompt
        $promptPath = match ($this->scenario) {
            'first_onboarding' => 'prompts/master_orchestrator/first_onboarding.blade.php',
            'task_generation' => 'prompts/master_orchestrator/task_generation.blade.php',
            'task_review' => 'prompts/master_orchestrator/task_review.blade.php',
            'cost_analysis' => 'prompts/master_orchestrator/cost_analysis.blade.php',
            default => 'prompts/master_orchestrator/default.blade.php',
        };

        $fullPath = resource_path($promptPath);

        if (! file_exists($fullPath)) {
            Log::warning('MasterOrchestratorJob: Prompt file not found, using default', [
                'scenario' => $this->scenario,
                'path' => $promptPath,
            ]);
            $fullPath = resource_path('prompts/master_orchestrator/default.blade.php');
        }

        return view()->file($fullPath, $promptData)->render();
    }

    /**
     * Process agent response based on scenario
     */
    protected function processResponse(string $response, User $user): void
    {
        match ($this->scenario) {
            'first_onboarding' => $this->processTaskGeneration($response, $user),
            'task_generation' => $this->processTaskGeneration($response, $user),
            'task_review' => $this->processTaskReview($response, $user),
            'cost_analysis' => $this->processCostAnalysis($response, $user),
            default => Log::info('No specific processing for scenario', ['scenario' => $this->scenario]),
        };
    }

    /**
     * Process task generation response
     */
    protected function processTaskGeneration(string $response, User $user): void
    {
        try {
            // Extract JSON from response
            $tasksData = $this->extractJsonArray($response);

            if (empty($tasksData)) {
                Log::warning('No tasks extracted from agent response', [
                    'user_id' => $user->id,
                ]);

                return;
            }

            // Create tasks (agent will be selected dynamically when approved)
            $createdTasks = [];
            $createdCount = 0;
            foreach ($tasksData as $index => $taskData) {
                $task = Task::create([
                    'user_id' => $user->id,
                    'agent_name' => null, // Agent selected dynamically on approval
                    'status' => 'pending',
                    'priority' => $taskData['priority'] ?? 5,
                    'order' => $index + 1,
                    'data' => [
                        'name' => $taskData['name'] ?? $taskData['task_name'] ?? 'Unnamed Task',
                        'description' => $taskData['description'] ?? '',
                        'task_type' => $taskData['task_type'] ?? 'one_time',
                        'schedule' => $taskData['schedule'] ?? null,
                        'estimated_savings' => $taskData['estimated_savings'] ?? null,
                        'input' => $taskData['input'] ?? [],
                        'metadata' => $taskData['metadata'] ?? [],
                    ],
                ]);

                $createdTasks[] = $task;
                $createdCount++;
            }

            Log::info('Tasks created from agent response', [
                'user_id' => $user->id,
                'count' => $createdCount,
            ]);

            // Save task generation summary as MD
            $this->saveTaskGenerationSummary($response, $user, $createdTasks);

        } catch (\Exception $e) {
            Log::error('Failed to process task generation', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Save task generation summary as Automation (MD file)
     */
    protected function saveTaskGenerationSummary(string $response, User $user, array $createdTasks): void
    {
        try {
            // Generate a clean MD summary
            $taskCount = count($createdTasks);
            $totalSavings = collect($createdTasks)->sum(function ($task) {
                $savings = $task->data['estimated_savings'] ?? '0';

                return (int) filter_var($savings, FILTER_SANITIZE_NUMBER_INT);
            });

            $tasksList = collect($createdTasks)->map(function ($task) {
                return "- **{$task->data['name']}**: {$task->data['description']} (Priority: {$task->priority}, Savings: {$task->data['estimated_savings']})";
            })->join("\n");

            $mdContent = <<<MD
# 📋 Task Generation Report

**Date:** {$this->getFormattedDate()}
**Scenario:** {$this->scenario}
**User:** {$user->name}

---

## 📊 Summary

- **Total Tasks Generated:** {$taskCount}
- **Estimated Total Savings:** \${$totalSavings}/month
- **Status:** ✅ Tasks pending user approval

---

## 🎯 Generated Tasks

{$tasksList}

---

## 📝 Details

{$response}

---

*Generated by MasterOrchestrator on {$this->getFormattedDate()}*
MD;

            \App\Models\Automation::create([
                'user_id' => $user->id,
                'type' => 'task_generation',
                'name' => "Task Generation - {$this->scenario}",
                'description' => "Generated {$taskCount} tasks with estimated savings of \${$totalSavings}/month",
                'markdown_content' => $mdContent,
                'metadata' => [
                    'scenario' => $this->scenario,
                    'task_count' => $taskCount,
                    'estimated_savings' => $totalSavings,
                    'task_ids' => collect($createdTasks)->pluck('id')->toArray(),
                ],
                'status' => 'active',
            ]);

            Log::info('Task generation summary saved as Automation', [
                'user_id' => $user->id,
                'task_count' => $taskCount,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to save task generation summary', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
            ]);
        }
    }

    /**
     * Get formatted date
     */
    protected function getFormattedDate(): string
    {
        return now()->format('Y-m-d H:i:s');
    }

    /**
     * Process task review response
     */
    protected function processTaskReview(string $response, User $user): void
    {
        Log::info('Task review completed', [
            'user_id' => $user->id,
            'response_length' => strlen($response),
        ]);
        // Additional processing can be added here
    }

    /**
     * Process cost analysis response
     */
    protected function processCostAnalysis(string $response, User $user): void
    {
        Log::info('Cost analysis completed', [
            'user_id' => $user->id,
            'response_length' => strlen($response),
        ]);
        // Additional processing can be added here
    }

    /**
     * Extract JSON array from raw response
     */
    protected function extractJsonArray(string $response): array
    {
        // Try to find JSON array in response
        if (preg_match('/\[[\s\S]*\]/', $response, $matches)) {
            try {
                $decoded = json_decode($matches[0], true);
                if (is_array($decoded)) {
                    return $decoded;
                }
            } catch (\Exception $e) {
                Log::warning('Failed to decode JSON from response', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [];
    }

    /**
     * Handle failed job
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('MasterOrchestratorJob permanently failed', [
            'user_id' => $this->userId,
            'scenario' => $this->scenario,
            'error' => $exception->getMessage(),
        ]);
    }
}
