<?php

namespace App\Services;

use App\Agents\ExecutionAgent;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Vizra\VizraADK\System\AgentContext;

class ExecutionService
{
    public function __construct() {}

    /**
     * Run execution tasks for a given user by invoking the ExecutionAgent.
     */
    public function run(int $userId, string $userMessage): mixed
    {
        Log::info('ExecutionService: starting execution', ['user_id' => $userId]);

        $sessionId = 'execution'.$userId.'_'.time();
        // set userId to context
        $context = new AgentContext($sessionId);
        $context->setState('user_id', $userId);

        $prompt = sprintf(
            'User Message: %s', $userMessage
        );
        $user = User::find($userId);
        if (! $user) {
            Log::warning('ExecutionService: user not found, aborting', ['user_id' => $userId]);

            return null;
        }
        Log::info('ExecutionService: running ExecutionAgent', [
            'user_id' => $userId,
            'session_id' => $sessionId,
            'user' => $user->toArray(),
        ]);

        try {
            $ExecutionResponse = ExecutionAgent::run($prompt)->forUser($user)
                ->withSession($sessionId)
                ->go();

            Log::info('ExecutionService: ExecutionAgent response', [
                'user_id' => $userId,
                'response' => is_scalar($ExecutionResponse) ? $ExecutionResponse : json_encode($ExecutionResponse),
            ]);

            $parsed = CleanUpResponse::extractJsonPayload($ExecutionResponse);

            return $parsed;
        } catch (\Throwable $e) {
            Log::error('ExecutionService: execution failed', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-throw so job retry / failure handling is triggered by the queue worker
            throw $e;
        }
    }
}
