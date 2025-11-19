<?php

namespace App\Jobs;

use App\Agents\MasterOrchestrator;
use App\Models\KnowledgeBase;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Vizra\VizraADK\System\AgentContext;

/**
 * TaskDesignerJob - Uses AI to design a queue of tasks for agents
 *
 * This job analyzes the user's situation and creates a flexible
 * task queue stored in the database. Tasks are simple JSON structures
 * that agents can easily understand and execute.
 */
class TaskDesignerJob implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $userId
    ) {
        // Set the queue name
        $this->onQueue('master_orchestrator');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $user = User::find($this->userId);

            if (! $user) {
                Log::warning('TaskDesignerJob: User not found', [
                    'user_id' => $this->userId,
                ]);

                return;
            }

            Log::info('TaskDesignerJob: Starting task design', [
                'user_id' => $this->userId,
            ]);

            // Get user's knowledge base data
            $knowledgeBase = KnowledgeBase::where('user_id', $this->userId)->first();
            $onboardingContext = $knowledgeBase?->context ?? [];

            // Get user's financial data files
            $financialDataFiles = $this->getFinancialDataFiles($this->userId);

            // Build dynamic prompt for task design
            $prompt = $this->buildTaskDesignPrompt($onboardingContext, $financialDataFiles);

            // Create session for MasterOrchestrator
            $sessionId = 'task_designer_'.$this->userId.'_'.time();

            // Create context
            $context = new AgentContext($sessionId);
            $context->setState('user_id', $this->userId);
            $context->setState('onboarding_context', $onboardingContext);

            // Use MasterOrchestrator to design tasks
            $response = MasterOrchestrator::run(input: $prompt)
                ->forUser($user)
                ->withSession($sessionId)
                ->go();

            // Log the AI response (for now, not saving to DB)
            Log::info('TaskDesignerJob: AI response received', [
                'user_id' => $this->userId,
                'response' => is_string($response) ? $response : json_encode($response, JSON_PRETTY_PRINT),
            ]);

            // Parse and log tasks (for now, not saving to DB)
            $this->logTasks($response, $this->userId);

            Log::info('TaskDesignerJob: Task design completed', [
                'user_id' => $this->userId,
            ]);
        } catch (\Exception $e) {
            Log::error('TaskDesignerJob: Failed to design tasks', [
                'user_id' => $this->userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Get user's financial data files
     */
    protected function getFinancialDataFiles(int $userId): array
    {
        $files = [];
        $storagePath = storage_path('app/private/financial_data/'.$userId);

        if (is_dir($storagePath)) {
            $fileList = glob($storagePath.'/*.json');
            foreach ($fileList as $file) {
                $files[] = [
                    'filename' => basename($file),
                    'path' => $file,
                    'size' => filesize($file),
                ];
            }
            
        }

        return $files;
    }

    /**
     * Build the dynamic prompt for task design
     */
    protected function buildTaskDesignPrompt(array $onboardingContext, array $financialDataFiles): string
    {
        $prompt = "You are a Cost Engineering Task Designer. Your job is to analyze the user's financial data and create cost-saving tasks.\n\n";
        $prompt .= "CRITICAL RULE: Every task MUST have the potential to save at least $1. No task should be created unless it can save money.\n\n";

        $prompt .= "User Context:\n";
        if (! empty($onboardingContext['understanding'])) {
            $prompt .= 'Company Understanding: '.$onboardingContext['understanding']."\n";
        }
        if (! empty($onboardingContext['organized_content'])) {
            $prompt .= 'Company Summary: '.$onboardingContext['organized_content']."\n";
        }

        // Add financial data info
        if (! empty($financialDataFiles)) {
            $prompt .= "\nFinancial Data Available:\n";
            $prompt .= 'User has '.count($financialDataFiles)." financial data file(s) uploaded.\n";
            $prompt .= "Analyze this data to identify cost-saving opportunities.\n";
        } else {
            $prompt .= "\nNote: No financial data uploaded yet. Design initial setup tasks.\n";
        }

        $prompt .= "\nYour Task:\n";
        $prompt .= "Design cost-saving tasks that will help this user reduce expenses.\n";
        $prompt .= "Each task can use MULTIPLE agents in sequence (like a workflow).\n";
        $prompt .= "Tasks should be sequential and can be recurring or scheduled.\n\n";

        $prompt .= "Available Agents (you can use multiple per task):\n";
        $prompt .= "- categorizer_agent: Maps and normalizes category names\n";
        $prompt .= "- base_line_agent: Establishes baseline metrics\n";
        $prompt .= "- cost_decomposition_agent: Decomposes costs into components\n";
        $prompt .= "- benchmarking_agent: Compares against industry benchmarks\n";
        $prompt .= "- c_e_r_agent: Computes cost efficiency ratios\n";
        $prompt .= "- cost_optomizer_agent: Orchestrates cost optimization\n";
        $prompt .= "- smart_reducer: Reduces costs intelligently\n";
        $prompt .= "- notion_agent: Interacts with Notion workspaces\n\n";

        $prompt .= "Task Structure:\n";
        $prompt .= "Each task should have:\n";
        $prompt .= "{\n";
        $prompt .= '  "title": "Clear task title",'."\n";
        $prompt .= '  "description": "What this task does and why it saves money",'."\n";
        $prompt .= '  "potential_savings": 1000,'."\n";
        $prompt .= '  "agents": ["categorizer_agent", "base_line_agent"],'."\n";
        $prompt .= '  "schedule": {"type": "recurring", "frequency": "weekly"},'."\n";
        $prompt .= '  "schedule": {"type": "one_time", "run_at": "2025-11-20"},'."\n";
        $prompt .= '  "schedule": {"type": "immediate"},'."\n";
        $prompt .= '  "data": {"description": "what to do", ...any other data...},'."\n";
        $prompt .= '  "priority": 10,'."\n";
        $prompt .= '  "order": 1'."\n";
        $prompt .= "}\n\n";

        $prompt .= "Schedule Types:\n";
        $prompt .= "- 'immediate': Run right away\n";
        $prompt .= "- 'one_time': Run once at specific date/time\n";
        $prompt .= "- 'recurring': Run repeatedly (daily, weekly, monthly)\n\n";

        $prompt .= "Return ONLY a JSON array of tasks. Start with [ and end with ].\n";
        $prompt .= "Each task must have potential_savings >= 1.\n";

        return $prompt;
    }

    /**
     * Parse AI response and log tasks (not saving to DB for now)
     */
    protected function logTasks(mixed $response, int $userId): void
    {
        // Extract JSON from response
        $jsonString = is_string($response) ? $response : json_encode($response);

        // Try to extract JSON array from response
        $jsonString = $this->extractJsonArray($jsonString);

        // Parse JSON
        $tasks = json_decode($jsonString, true);

        if (! is_array($tasks)) {
            Log::warning('TaskDesignerJob: Failed to parse tasks as array', [
                'response' => $jsonString,
            ]);

            return;
        }

        // Log parsed tasks
        Log::info('TaskDesignerJob: Parsed tasks from AI', [
            'user_id' => $userId,
            'task_count' => count($tasks),
            'tasks' => $tasks,
        ]);

        // Log each task individually for clarity
        foreach ($tasks as $index => $taskData) {
            Log::info('TaskDesignerJob: Task parsed', [
                'user_id' => $userId,
                'task_index' => $index + 1,
                'title' => $taskData['title'] ?? 'Untitled',
                'potential_savings' => $taskData['potential_savings'] ?? 0,
                'agents' => $taskData['agents'] ?? [],
                'schedule' => $taskData['schedule'] ?? [],
                'priority' => $taskData['priority'] ?? 0,
                'order' => $taskData['order'] ?? ($index + 1),
                'data' => $taskData['data'] ?? [],
            ]);
        }
    }

    /**
     * Extract JSON array from response string
     */
    protected function extractJsonArray(string $response): string
    {
        $response = trim($response);

        // Find first [ and last ]
        $startPos = strpos($response, '[');
        $endPos = strrpos($response, ']');

        if ($startPos !== false && $endPos !== false && $endPos > $startPos) {
            return substr($response, $startPos, $endPos - $startPos + 1);
        }

        // If no array found, try to parse as is
        return $response;
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('TaskDesignerJob: Job failed after all retries', [
            'user_id' => $this->userId,
            'error' => $exception->getMessage(),
        ]);
    }
}
