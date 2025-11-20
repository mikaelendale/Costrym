<?php

namespace App\Agents;

use App\Traits\LoadsPipedreamTools;
use Illuminate\Support\Facades\Log;
use Vizra\VizraADK\Agents\BaseLlmAgent;
use Vizra\VizraADK\System\AgentContext;

/**
 * Notion Agent
 *
 * Specialized agent for interacting with Notion using Pipedream actions.
 * Automatically loads all available Notion tools based on user's connected account.
 */
class NotionAgent extends BaseLlmAgent
{
    use LoadsPipedreamTools;

    protected string $name = 'notion_agent';

    protected string $description = 'An AI agent specialized in interacting with Notion. Can create pages, update databases, search content, and perform other Notion operations using your connected account.';

    protected string $instructions = 'You are a helpful assistant for working with Notion. You have access to various Notion actions through Pipedream tools. When a user asks you to perform a Notion action, use the appropriate tool. If a required parameter is missing ask, use the search action to find it first, or ask the user for clarification. Always confirm what action you\'re taking and provide clear feedback about the results. If an action fails with an error, do NOT retry the same action with the same parameters - instead, explain the error to the user and suggest alternatives.';

    protected string $model = 'gpt-4o-mini';

    protected array $tools = [];

    // Disable conversation history for this agent
    protected bool $includeConversationHistory = false;

    public function beforeLlmCall(array $inputMessages, AgentContext $context): array
    {
        try {
            // Only load Notion-specific tools to avoid loading too many tools
            $this->loadPipedreamTools($context, false, 'notion');

            $toolCount = count($this->loadedTools);
            Log::info('NotionAgent loaded tools', [
                'user_id' => $context->getState('user_id'),
                'tool_count' => $toolCount,
            ]);

            // Log warning if too many tools are loaded (might cause API errors)
            if ($toolCount > 50) {
                Log::warning('NotionAgent has loaded many tools, this might cause API errors', [
                    'tool_count' => $toolCount,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('NotionAgent failed to load Pipedream tools', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            // Continue execution even if tool loading fails
        }

        return parent::beforeLlmCall($inputMessages, $context);
    }
}
