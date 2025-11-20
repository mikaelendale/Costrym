<?php

namespace App\Tools;

use App\Services\PipedreamService;
use App\Services\PipedreamToolLoader;
use Vizra\VizraADK\Contracts\ToolInterface;
use Vizra\VizraADK\Memory\AgentMemory;
use Vizra\VizraADK\System\AgentContext;

/**
 * Tool to list all available integrations for the user
 *
 * This tool allows the agent to see what integrations are connected and available
 */
class ListAvailableIntegrationsTool implements ToolInterface
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
            'name' => 'list_available_integrations',
            'description' => 'List all available integrations (accounting systems, payment processors, etc.) showing which ones are connected and available. Returns integration status, connection state, and available tool counts. Use this to check which integrations the user has connected before attempting to fetch data.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'category' => [
                        'type' => 'string',
                        'description' => 'Optional: Filter by category (accounting, payment_processing, crm, etc.). If not provided, returns all integrations.',
                    ],
                    'connected_only' => [
                        'type' => 'boolean',
                        'description' => 'Optional: If true, only returns connected integrations. Default is false (shows all).',
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
                    'error' => 'User ID not found in context. Cannot list integrations.',
                ]);
            }

            $toolLoader = app(PipedreamToolLoader::class);
            $pipedreamService = app(PipedreamService::class);

            $category = $arguments['category'] ?? null;
            $connectedOnly = $arguments['connected_only'] ?? false;

            // Get all integrations from config
            $allIntegrations = $pipedreamService->getAvailableIntegrations();
            $connectedApps = $toolLoader->getConnectedAppNames($userId);

            // Get tools summary for connected integrations
            $toolsSummary = $toolLoader->getToolsSummary($userId, false);

            // Build comprehensive integration list
            $integrations = [];
            foreach ($allIntegrations as $appId => $config) {
                $appName = $config['app_id'] ?? $appId;
                $isConnected = in_array($appName, $connectedApps);

                // Filter by category if specified
                if ($category && ($config['category'] ?? null) !== $category) {
                    continue;
                }

                // Filter by connected status if specified
                if ($connectedOnly && ! $isConnected) {
                    continue;
                }

                // Find tools for this integration
                $integrationTools = array_filter($toolsSummary, function ($summary) use ($appName) {
                    return $summary['app_name'] === $appName;
                });
                $toolsData = ! empty($integrationTools) ? array_values($integrationTools)[0] : null;

                // Check if components are synced in database
                $syncedActionsCount = \App\Models\PipedreamComponent::active()
                    ->actions()
                    ->where('app_name', $appName)
                    ->count();

                $integrations[] = [
                    'app_id' => $appId,
                    'app_name' => $appName,
                    'name' => $config['name'] ?? $appName,
                    'category' => $config['category'] ?? 'other',
                    'required' => $config['required'] ?? false,
                    'is_connected' => $isConnected,
                    'available_tools_count' => $toolsData['tool_count'] ?? 0,
                    'synced_actions_count' => $syncedActionsCount,
                ];
            }

            // Separate required and optional integrations
            $requiredIntegrations = array_filter($integrations, function ($integration) {
                return $integration['required'] === true;
            });
            $optionalIntegrations = array_filter($integrations, function ($integration) {
                return $integration['required'] === false;
            });

            $connectedCount = count(array_filter($integrations, function ($i) {
                return $i['is_connected'];
            }));

            $message = $connectedOnly
                ? "Found {$connectedCount} connected integrations"
                : 'Found '.count($integrations)." total integrations ({$connectedCount} connected)";

            if ($category) {
                $message .= " in category: {$category}";
            }

            return json_encode([
                'success' => true,
                'message' => $message,
                'integrations' => array_values($integrations),
                'required_integrations' => array_values($requiredIntegrations),
                'optional_integrations' => array_values($optionalIntegrations),
                'connected_apps' => $connectedApps,
                'total_integrations' => count($integrations),
                'connected_count' => $connectedCount,
                'total_tools' => array_sum(array_column($integrations, 'available_tools_count')),
            ]);
        } catch (\Exception $e) {
            return json_encode([
                'success' => false,
                'error' => 'Failed to list integrations: '.$e->getMessage(),
            ]);
        }
    }
}
