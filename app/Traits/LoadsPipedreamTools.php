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
     */
    protected function loadPipedreamTools(AgentContext $context, bool $requiredOnly = false): void
    {
        $userId = $context->getState('user_id');

        if (! $userId) {
            return;
        }

        $toolLoader = app(PipedreamToolLoader::class);

        if (! $toolLoader->userHasConnectedAccounts($userId)) {
            return;
        }

        // Load tools for this user (optionally only required integrations)
        $tools = $toolLoader->loadToolsForUser($userId, $requiredOnly);

        // Add tools to agent's loadedTools array
        foreach ($tools as $tool) {
            $toolName = $tool->definition()['name'];
            $this->loadedTools[$toolName] = $tool;
        }
    }
}
