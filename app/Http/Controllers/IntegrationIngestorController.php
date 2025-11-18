<?php

namespace App\Http\Controllers;

use App\Agents\IntegrationIngestor;
use App\Services\PipedreamToolLoader;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Vizra\VizraADK\System\AgentContext;

class IntegrationIngestorController extends Controller
{
    /**
     * Show the Integration Ingestor chat page
     */
    public function index(): Response
    {
        return Inertia::render('integration-ingestor');
    }

    /**
     * Chat with the Integration Ingestor agent
     */
    public function chat(Request $request): JsonResponse
    {
        $request->validate([
            'message' => 'required|string',
            'session_id' => 'nullable|string',
        ]);

        $user = $request->user();
        $message = $request->input('message');
        $sessionId = $request->input('session_id', 'ingestor_'.$user->id.'_'.time());

        // Check if user has any connected accounts
        $toolLoader = app(PipedreamToolLoader::class);
        $connectedApps = $toolLoader->getConnectedAppNames($user->id);

        if (empty($connectedApps)) {
            return response()->json([
                'success' => false,
                'error' => 'No integrations connected. Please connect at least one integration in Settings > Integrations first.',
            ], 400);
        }

        try {
            // Create agent instance
            $agent = new IntegrationIngestor;

            // Create context with user ID
            $context = new AgentContext($sessionId);
            $context->setState('user_id', $user->id);

            // Execute agent
            $response = $agent->execute($message, $context);

            return response()->json([
                'success' => true,
                'response' => $response,
                'session_id' => $sessionId,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Agent execution failed: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get available integrations and tools for the authenticated user
     * Returns all available integrations with their tools, connection status, and sync status
     */
    public function getAvailableIntegrations(Request $request): JsonResponse
    {
        $user = $request->user();

        $toolLoader = app(PipedreamToolLoader::class);
        $pipedreamService = app(\App\Services\PipedreamService::class);

        // Get all integrations from config
        $allIntegrations = $pipedreamService->getAvailableIntegrations();
        $connectedApps = $toolLoader->getConnectedAppNames($user->id);

        // Get tools summary for connected integrations
        $toolsSummary = $toolLoader->getToolsSummary($user->id, false);

        // Build comprehensive integration list
        $integrations = [];
        foreach ($allIntegrations as $appId => $config) {
            $appName = $config['app_id'] ?? $appId;
            $isConnected = in_array($appName, $connectedApps);

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
            $syncedTriggersCount = \App\Models\PipedreamComponent::active()
                ->triggers()
                ->where('app_name', $appName)
                ->count();

            $integrations[] = [
                'app_id' => $appId,
                'app_name' => $appName,
                'name' => $config['name'] ?? $appName,
                'category' => $config['category'] ?? 'other',
                'required' => $config['required'] ?? false,
                'is_connected' => $isConnected,
                'has_tools' => $toolsData !== null,
                'available_tools_count' => $toolsData['tool_count'] ?? 0,
                'available_tools' => $toolsData['tools'] ?? [],
                'synced_actions_count' => $syncedActionsCount,
                'synced_triggers_count' => $syncedTriggersCount,
                'is_synced' => $syncedActionsCount > 0 || $syncedTriggersCount > 0,
            ];
        }

        // Separate required and optional integrations
        $requiredIntegrations = array_filter($integrations, function ($integration) {
            return $integration['required'] === true;
        });
        $optionalIntegrations = array_filter($integrations, function ($integration) {
            return $integration['required'] === false;
        });

        return response()->json([
            'success' => true,
            'integrations' => array_values($integrations),
            'required_integrations' => array_values($requiredIntegrations),
            'optional_integrations' => array_values($optionalIntegrations),
            'connected_apps' => $connectedApps,
            'total_integrations' => count($integrations),
            'connected_count' => count($connectedApps),
            'total_tools' => array_sum(array_column($integrations, 'available_tools_count')),
        ]);
    }

    /**
     * Get all available tools with their definitions for the authenticated user
     * Returns detailed tool information including parameters and descriptions
     */
    public function getAvailableTools(Request $request): JsonResponse
    {
        $user = $request->user();
        $toolLoader = app(PipedreamToolLoader::class);

        // Load all tools for the user (required integrations only for IntegrationIngestor)
        $tools = $toolLoader->loadToolsForUser($user->id, true);

        // Build detailed tool definitions
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
                'parameters' => $definition['parameters'] ?? [],
                'required_params' => $definition['parameters']['required'] ?? [],
                'param_details' => $definition['parameters']['properties'] ?? [],
            ];
        }

        // Group by app
        $toolsByApp = [];
        foreach ($toolDefinitions as $tool) {
            $appName = $tool['app_name'];
            if (! isset($toolsByApp[$appName])) {
                $toolsByApp[$appName] = [];
            }
            $toolsByApp[$appName][] = $tool;
        }

        return response()->json([
            'success' => true,
            'total_tools' => count($toolDefinitions),
            'tools' => $toolDefinitions,
            'tools_by_app' => $toolsByApp,
            'apps' => array_keys($toolsByApp),
        ]);
    }
}
