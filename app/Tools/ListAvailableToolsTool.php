<?php

namespace App\Tools;

use App\Models\ConnectedAccount;
use App\Models\PipedreamComponent;
use Vizra\VizraADK\Contracts\ToolInterface;
use Vizra\VizraADK\Memory\AgentMemory;
use Vizra\VizraADK\System\AgentContext;

/**
 * Tool to list all available tools for the agent
 *
 * This tool queries the database directly to avoid repeated API calls.
 * Tools are cached in the pipedream_components table.
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
            'description' => 'List all available tools/actions that the agent can use. Returns a comprehensive list of all Pipedream actions available for the connected integrations, including tool names, descriptions, and required parameters. IMPORTANT: This tool queries cached data from the database - call it ONCE at the start of your workflow, not repeatedly.',
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
     * Execute the tool - queries database directly for efficiency
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

            $appName = $arguments['app_name'] ?? null;

            // Get connected accounts for the user
            $connectedAccounts = ConnectedAccount::where('user_id', $userId)
                ->where('is_active', true)
                ->get();

            if ($connectedAccounts->isEmpty()) {
                return json_encode([
                    'success' => true,
                    'message' => 'No connected integrations found',
                    'tools' => [],
                    'total_tools' => 0,
                ]);
            }

            // Get app names from connected accounts
            $appNames = $connectedAccounts->pluck('app_name')->unique()->toArray();

            // Filter by specific app if requested
            if ($appName) {
                if (! in_array($appName, $appNames)) {
                    return json_encode([
                        'success' => true,
                        'message' => "App '{$appName}' is not connected",
                        'tools' => [],
                        'total_tools' => 0,
                    ]);
                }
                $appNames = [$appName];
            }

            // Query components directly from database (cached data)
            $components = PipedreamComponent::active()
                ->actions()
                ->whereIn('app_name', $appNames)
                ->orderBy('app_name')
                ->orderBy('component_name')
                ->get();

            if ($components->isEmpty()) {
                return json_encode([
                    'success' => true,
                    'message' => 'No tools available for connected integrations',
                    'tools' => [],
                    'total_tools' => 0,
                ]);
            }

            // Build tool definitions directly from database
            $toolDefinitions = [];
            foreach ($components as $component) {
                $componentData = $component->component_data ?? [];
                $props = $componentData['props'] ?? [];

                // Extract parameter information from component_data
                $paramDetails = [];
                $requiredParams = [];
                foreach ($props as $key => $prop) {
                    $paramDetails[$key] = [
                        'type' => $prop['type'] ?? 'string',
                        'description' => $prop['label'] ?? $prop['description'] ?? '',
                    ];
                    if (($prop['optional'] ?? false) === false) {
                        $requiredParams[] = $key;
                    }
                }

                $toolDefinitions[] = [
                    'tool_name' => 'pipedream_'.$component->component_key,
                    'component_key' => $component->component_key,
                    'component_name' => $component->component_name,
                    'app_name' => $component->app_name,
                    'description' => $component->description ?? $component->component_name,
                    'required_params' => $requiredParams,
                    'all_params' => array_keys($paramDetails),
                    'param_details' => $paramDetails,
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
                    ? "Found {$totalCount} tools for {$appName} (from cached database)"
                    : "Found {$totalCount} total tools across {$appCount} integrations (from cached database)",
            ]);
        } catch (\Exception $e) {
            return json_encode([
                'success' => false,
                'error' => 'Failed to list tools: '.$e->getMessage(),
            ]);
        }
    }
}
