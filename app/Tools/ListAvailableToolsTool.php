<?php

namespace App\Tools;

use App\Services\PipedreamToolLoader;
use Vizra\VizraADK\Contracts\ToolInterface;
use Vizra\VizraADK\Memory\AgentMemory;
use Vizra\VizraADK\System\AgentContext;

/**
 * Tool to list all available tools for the agent
 *
 * This tool allows the agent to see what tools are available and their definitions
 */
class ListAvailableToolsTool implements ToolInterface
{
    protected ?int $userId = null;

    public function __construct(?int $userId = null)
    {
        $this->userId = $userId;
    }

    /**
     * Get the tool's definition for the LLM
     */
    public function definition(): array
    {
        return [
            'name' => 'list_available_tools',
            'description' => 'List all available tools/actions that the agent can use. Returns a comprehensive list of all Pipedream actions available for the connected integrations, including tool names, descriptions, and required parameters. Use this to discover what actions are available before attempting to use them.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'app_name' => [
                        'type' => 'string',
                        'description' => 'Optional: Filter tools by app name (e.g., "xero_accounting_api", "quickbooks", "notion"). If not provided, returns all available tools.',
                    ],
                ],
                'required' => [],
            ],
        ];
    }

    /**
     * Execute the tool
     */
    public function execute(array $arguments, AgentContext $context, AgentMemory $memory): string
    {
        try {
            // Get user ID from context if not set in constructor
            $userId = $this->userId ?? $context->getState('user_id');

            if (! $userId) {
                return json_encode([
                    'success' => false,
                    'error' => 'User ID not found in context. Cannot list tools.',
                ]);
            }

            $toolLoader = app(PipedreamToolLoader::class);
            $appName = $arguments['app_name'] ?? null;

            // Load tools for the user
            if ($appName) {
                $tools = $toolLoader->loadToolsForApp($userId, $appName);
            } else {
                // Load all tools (required integrations only for IntegrationIngestor)
                $tools = $toolLoader->loadToolsForUser($userId, true);
            }

            if ($tools->isEmpty()) {
                return json_encode([
                    'success' => true,
                    'message' => 'No tools available',
                    'tools' => [],
                    'total' => 0,
                ]);
            }

            // Build tool definitions
            $toolDefinitions = [];
            foreach ($tools as $tool) {
                $component = $tool->getComponent();
                $definition = $tool->definition();

                $toolDefinitions[] = [
                    'tool_name' => $definition['name'],
                    'component_key' => $component->component_key,
                    'component_name' => $component->component_name,
                    'app_name' => $component->app_name,
                    'description' => $definition['description'],
                    'required_params' => $definition['parameters']['required'] ?? [],
                    'all_params' => array_keys($definition['parameters']['properties'] ?? []),
                    'param_details' => $definition['parameters']['properties'] ?? [],
                ];
            }

            // Group by app
            $toolsByApp = [];
            foreach ($toolDefinitions as $tool) {
                $app = $tool['app_name'];
                if (! isset($toolsByApp[$app])) {
                    $toolsByApp[$app] = [];
                }
                $toolsByApp[$app][] = $tool;
            }

            $totalCount = count($toolDefinitions);
            $appCount = count($toolsByApp);

            return json_encode([
                'success' => true,
                'total_tools' => $totalCount,
                'tools' => $toolDefinitions,
                'tools_by_app' => $toolsByApp,
                'apps' => array_keys($toolsByApp),
                'message' => $appName
                    ? "Found {$totalCount} tools for {$appName}"
                    : "Found {$totalCount} total tools across {$appCount} integrations",
            ]);
        } catch (\Exception $e) {
            return json_encode([
                'success' => false,
                'error' => 'Failed to list tools: '.$e->getMessage(),
            ]);
        }
    }
}
