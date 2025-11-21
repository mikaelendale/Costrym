<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\ExecutionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ExecutionJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    /**
     * Max seconds the job can run before timing out.
     */
    public int $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $userId,
        public string $userMessage,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(ExecutionService $executionService): void
    {
        Log::info('ExecutionJob executed', ['user_id' => $this->userId, 'user_message' => $this->userMessage]);

        $user = User::find($this->userId);
        if (! $user) {
            Log::warning('ExecutionJob: user not found, aborting', ['user_id' => $this->userId]);

            return;
        }

        // Pass the user id and optional message to the execution service so it can scope tools/context
        $executionService->run($this->userId, $this->userMessage);
    }

    /**
     * Handle a job failure and log it for visibility.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ExecutionJob failed', [
            'user_id' => $this->userId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
