<?php

namespace App\Traits;

use App\Services\PipedreamToolLoader;
use Vizra\VizraADK\System\AgentContext;

/**
 * Trait for agents that need Pipedream tool access
 *
 * Add this trait to any agent that should have access to Pipedream actions
 * based on the user's connected accounts.
 */
trait LoadsPipedreamTools
{
    /**
     * Load Pipedream tools dynamically in beforeLlmCall
     *
     * Override beforeLlmCall in your agent and call this method:
     *
     * public function beforeLlmCall(array $inputMessages, AgentContext $context): array
     * {
     *     $this->loadPipedreamTools($context);
     *     return parent::beforeLlmCall($inputMessages, $context);
     * }
     *
     * @param  AgentContext  $context  The agent context
     * @param  bool  $requiredOnly  Only load tools for required integrations
     * @param  string|null  $appName  Load tools for a specific app only (e.g., 'notion')
     */
    protected function loadPipedreamTools(AgentContext $context, bool $requiredOnly = false, ?string $appName = null): void
    {
        $userId = $context->getState('user_id');

        if (! $userId) {
            return;
        }

        $toolLoader = app(PipedreamToolLoader::class);

        if (! $toolLoader->userHasConnectedAccounts($userId)) {
            return;
        }

        // Load tools for specific app or all user tools
        if ($appName) {
            $tools = $toolLoader->loadToolsForApp($userId, $appName);
        } else {
            $tools = $toolLoader->loadToolsForUser($userId, $requiredOnly);
        }

        // Add tools to agent's loadedTools array
        foreach ($tools as $tool) {
            try {
                $toolName = $tool->definition()['name'];
                $this->loadedTools[$toolName] = $tool;
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('Failed to load Pipedream tool', [
                    'error' => $e->getMessage(),
                    'component_key' => $tool->getComponent()->component_key ?? 'unknown',
                ]);
            }
        }
    }
}
