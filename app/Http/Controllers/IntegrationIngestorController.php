<?php

namespace App\Http\Controllers;

use App\Agents\IntegrationIngestor;
use App\Models\ConnectedAccount;
use App\Services\PipedreamService;
use App\Services\PipedreamToolLoader;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class IntegrationIngestorController extends Controller
{
    /**
     * Show the Xero Integration Ingestor chat page
     *
     * Note: This agent is currently specialized for Xero only.
     */
    public function index(): Response
    {
        return Inertia::render('integration-ingestor');
    }

    /**
     * Chat with the Xero Integration Ingestor agent
     *
     * This agent is specialized for Xero accounting data only.
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

        // Check if user has Xero connected (IntegrationIngestor currently only supports Xero)
        $toolLoader = app(PipedreamToolLoader::class);
        $connectedApps = $toolLoader->getConnectedAppNames($user->id);

        if (! in_array('xero_accounting_api', $connectedApps)) {
            return response()->json([
                'success' => false,
                'error' => 'Xero account not connected. Please connect your Xero account in Settings > Integrations first. (Note: IntegrationIngestor currently only supports Xero)',
            ], 400);
        }

        try {
            // Execute agent using fluent API
            $response = IntegrationIngestor::run($message)
                ->forUser($user)
                ->withSession($sessionId)
                ->go();

            // Extract text content from response (handles both string and object responses)
            $responseText = $this->extractResponseText($response);

            return response()->json([
                'success' => true,
                'response' => $responseText,
                'session_id' => $sessionId,
            ]);
        } catch (\Vizra\VizraADK\Exceptions\AgentExecutionException $e) {
            // Handle Vizra ADK specific errors
            $errorMessage = $e->getMessage();
            $previousException = $e->getPrevious();

            Log::error('XeroIngestor execution error', [
                'user_id' => $user->id,
                'message' => $message,
                'error' => $errorMessage,
                'previous_error' => $previousException?->getMessage(),
                'previous_trace' => $previousException?->getTraceAsString(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Provide more user-friendly error message
            $userFriendlyError = 'Agent execution failed. ';
            if (str_contains($errorMessage, 'HTTP request returned status code 400')) {
                $userFriendlyError .= 'The request was invalid. This might be due to too many tools or an invalid configuration.';
            } elseif (str_contains($errorMessage, 'HTTP request returned status code 401')) {
                $userFriendlyError .= 'Authentication failed. Please check your API keys.';
            } elseif (str_contains($errorMessage, 'HTTP request returned status code 429')) {
                $userFriendlyError .= 'Rate limit exceeded. Please try again later.';
            } else {
                $userFriendlyError .= $errorMessage;
            }

            return response()->json([
                'success' => false,
                'error' => $userFriendlyError,
            ], 500);
        } catch (\Exception $e) {
            Log::error('XeroIngestor chat error', [
                'user_id' => $user->id,
                'message' => $message,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Agent execution failed: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get available integrations and tools for the authenticated user
     * Returns all available integrations with their tools, connection status, and sync status
     *
     * Note: This endpoint is provided for frontend UI convenience.
     * The agent itself has access to this functionality via the 'list_available_integrations' tool.
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
     *
     * Note: This endpoint is provided for frontend UI convenience.
     * The agent itself has access to this functionality via the 'list_available_tools' tool.
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

    /**
     * Demo endpoint to test fetching invoices from Xero
     * This is a test endpoint to verify the Xero integration works before feeding to AI
     */
    public function testFetchInvoices(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized',
            ], 401);
        }

        // Check if user has Xero connected
        $connectedAccount = ConnectedAccount::where('user_id', $user->id)
            ->where('app_name', 'xero_accounting_api')
            ->where('is_active', true)
            ->first();

        if (! $connectedAccount) {
            return response()->json([
                'success' => false,
                'error' => 'Xero account not connected. Please connect your Xero account in Settings > Integrations first.',
            ], 400);
        }

        try {
            $pipedreamService = app(PipedreamService::class);

            // Build configured props for Pipedream API
            $configuredProps = [
                'xero_accounting_api' => [
                    'authProvisionId' => $connectedAccount->pipedream_account_id,
                ],
            ];

            // Add action parameters (tenantId, modifiedAfter, etc.)
            if ($request->has('tenant_id')) {
                $configuredProps['tenantId'] = $request->input('tenant_id');
            }

            if ($request->has('modified_after')) {
                $configuredProps['modifiedAfter'] = $request->input('modified_after');
            }

            // Execute the list-invoices action
            $result = $pipedreamService->runAction(
                'xero_accounting_api-list-invoices',
                $connectedAccount->external_user_id ?? (string) $user->id,
                $configuredProps
            );

            if ($result['success']) {
                $responseData = $result['data'] ?? null;

                // Check if Pipedream returned an error in the response
                if (isset($responseData['os']) && is_array($responseData['os'])) {
                    foreach ($responseData['os'] as $logEntry) {
                        if (isset($logEntry['k']) && $logEntry['k'] === 'error' && isset($logEntry['err'])) {
                            $error = $logEntry['err'];
                            $errorMessage = $error['name'] ?? 'Unknown error';
                            if (isset($error['body'])) {
                                $errorBody = is_string($error['body']) ? json_decode($error['body'], true) : $error['body'];
                                if (isset($errorBody['message'])) {
                                    $errorMessage = $errorBody['message'];
                                } elseif (isset($errorBody['code'])) {
                                    $errorMessage = $errorBody['code'].': '.($errorBody['message'] ?? $errorMessage);
                                }
                            }

                            return response()->json([
                                'success' => false,
                                'error' => $errorMessage,
                                'xero_error_code' => $error['code'] ?? null,
                                'xero_status' => $error['status'] ?? null,
                            ], 500);
                        }
                    }
                }

                // Extract invoice data
                $invoices = $responseData['ret'] ?? $responseData;

                return response()->json([
                    'success' => true,
                    'action' => 'xero_accounting_api-list-invoices',
                    'invoices' => $invoices,
                    'invoice_count' => is_array($invoices) ? count($invoices) : 0,
                    'raw_response' => $responseData, // Include full response for debugging
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => $result['error'] ?? 'Failed to fetch invoices from Xero',
            ], 500);
        } catch (\Exception $e) {
            Log::error('Test fetch invoices error', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch invoices: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Extract text content from agent response (handles both string and object responses)
     */
    private function extractResponseText(mixed $response): string
    {
        if (is_string($response)) {
            return $response;
        }

        // Handle Prism Response objects
        if (is_object($response)) {
            if (method_exists($response, 'text')) {
                return (string) $response->text;
            }
            if (property_exists($response, 'text')) {
                return (string) $response->text;
            }
            if (method_exists($response, 'content')) {
                return (string) $response->content;
            }
            if (property_exists($response, 'content')) {
                return (string) $response->content;
            }
            if (method_exists($response, '__toString')) {
                return (string) $response;
            }
        }

        // Handle arrays
        if (is_array($response)) {
            if (isset($response['text'])) {
                return (string) $response['text'];
            }
            if (isset($response['content'])) {
                return (string) $response['content'];
            }
            if (isset($response['message']) && is_string($response['message'])) {
                return (string) $response['message'];
            }
        }

        // Fallback: convert to JSON string
        return json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
