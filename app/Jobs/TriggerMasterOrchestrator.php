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
 * Job to trigger the MasterOrchestrator agent after user onboarding
 *
 * This job runs on the 'master_orchestrator' queue and coordinates
 * the initial workflow setup for newly onboarded users.
 */
class TriggerMasterOrchestrator implements ShouldQueue
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
        // Set the queue name for this job
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
                Log::warning('TriggerMasterOrchestrator: User not found', [
                    'user_id' => $this->userId,
                ]);

                return;
            }

            Log::info('TriggerMasterOrchestrator: Starting orchestration for user', [
                'user_id' => $this->userId,
                'user_email' => $user->email,
            ]);

            // Get user's knowledge base data from onboarding
            $knowledgeBase = KnowledgeBase::where('user_id', $this->userId)->first();
            $onboardingContext = $knowledgeBase?->context ?? [];

            // Create a unique session ID for this orchestration
            $sessionId = 'master_orchestrator_'.$this->userId.'_'.time();

            // Build the initial prompt for the MasterOrchestrator
            $prompt = "Welcome to Costrym! You've just completed onboarding. ";
            $prompt .= "Please analyze the user's onboarding information and coordinate the appropriate agents to set up their initial cost analysis workflow. ";

            if (! empty($onboardingContext['understanding'])) {
                $prompt .= "\n\nUser's Company Understanding:\n".$onboardingContext['understanding'];
            }

            if (! empty($onboardingContext['organized_content'])) {
                $prompt .= "\n\nCompany Summary:\n".$onboardingContext['organized_content'];
            }

            $prompt .= "\n\nPlease:";
            $prompt .= "\n1. Welcome the user and explain what will happen next";
            $prompt .= "\n2. Determine the best initial workflow based on their profile";
            $prompt .= "\n3. Coordinate the appropriate agents to begin their cost analysis";
            $prompt .= "\n4. Provide a clear summary of what has been set up";

            // Create agent context with user information
            $context = new AgentContext($sessionId);
            $context->setState('user_id', $this->userId);
            $context->setState('onboarding_complete', true);
            $context->setState('onboarding_context', $onboardingContext);
            $context->setState('workflow_state', [
                'triggered_at' => now()->toIso8601String(),
                'triggered_by' => 'onboarding_completion',
            ]);

            // Execute the MasterOrchestrator agent
            // Since we're already in a queued job, we can execute synchronously
            // The agent will handle its own sub-agent delegations
            $response = MasterOrchestrator::run($prompt)
                ->forUser($user)
                ->withSession($sessionId)
                ->go();

            Log::info('TriggerMasterOrchestrator: Orchestration completed', [
                'user_id' => $this->userId,
                'session_id' => $sessionId,
                'response_length' => is_string($response) ? strlen($response) : 'non-string',
                'response_preview' => is_string($response) ? substr($response, 0, 200) : 'non-string',
            ]);
        } catch (\Exception $e) {
            Log::error('TriggerMasterOrchestrator: Failed to trigger orchestration', [
                'user_id' => $this->userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-throw to trigger retry mechanism
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('TriggerMasterOrchestrator: Job failed after all retries', [
            'user_id' => $this->userId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
