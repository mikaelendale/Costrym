<?php

namespace App\Http\Controllers;

use App\Services\PipedreamService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PipedreamConnectController extends Controller
{
    public function __construct(
        private PipedreamService $pipedreamService
    ) {}

    /**
     * Generates a Connect token for the frontend Pipedream SDK
     * Uses PipedreamService to create Connect token
     */
    public function getToken(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        if (! $user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        try {
            $result = $this->pipedreamService->createConnectToken((string) $user->id);
            
            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'token' => $result['token'],
                    'expires_at' => $result['expires_at'],
                ]);
            }
            
            return response()->json([
                'success' => false,
                'error' => $result['error'],
            ], 500);
        } catch (\Exception $e) {
            Log::error('Pipedream token generation error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to generate token: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Saves a connected account after OAuth flow completes
     * Uses PipedreamService to store account in database
     */
    public function saveConnection(Request $request, string $appName): JsonResponse
    {
        $user = Auth::user();
        
        if (! $user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $request->validate([
            'connection_id' => 'required|string',
            'external_user_id' => 'nullable|string',
            'metadata' => 'nullable|array',
        ]);

        try {
            $accountId = $request->input('connection_id');
            $externalUserId = $request->input('external_user_id') ?? (string) $user->id;
            $metadata = $request->input('metadata', []);
            
            // Parse token expiration if provided
            $tokenExpiresAt = null;
            if (isset($metadata['expires_at']) || isset($metadata['token_expires_at'])) {
                $expiresAt = $metadata['expires_at'] ?? $metadata['token_expires_at'];
                try {
                    $tokenExpiresAt = new \DateTime($expiresAt);
                } catch (\Exception $e) {
                    // Invalid date, will be null
                }
            }
            
            $account = $this->pipedreamService->storeAccount(
                userId: $user->id,
                appName: $appName,
                accountId: $accountId,
                externalUserId: $externalUserId,
                metadata: $metadata,
                tokenExpiresAt: $tokenExpiresAt
            );

            return response()->json([
                'success' => true,
                'message' => ucfirst($appName).' connected successfully!',
                'account' => [
                    'id' => $account->id,
                    'app_name' => $account->app_name,
                    'pipedream_account_id' => $account->pipedream_account_id,
                    'metadata' => $account->metadata,
                    'connected_at' => $account->created_at,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Pipedream save connection error', [
                'error' => $e->getMessage(),
                'app' => $appName,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to save connection',
            ], 500);
        }
    }

    /**
     * Lists all connected accounts for the authenticated user
     * Uses PipedreamService to retrieve stored accounts
     */
    public function listAccounts(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        if (! $user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        try {
            $accounts = $this->pipedreamService->listStoredAccounts($user->id)
                ->map(function ($account) {
                    return [
                        'id' => $account->id,
                        'pipedream_account_id' => $account->pipedream_account_id,
                        'app' => $account->app_name,
                        'external_user_id' => $account->external_user_id,
                        'metadata' => $account->metadata,
                        'is_active' => $account->is_active,
                        'connected_at' => $account->created_at,
                        'updated_at' => $account->updated_at,
                    ];
                });

            return response()->json([
                'success' => true,
                'accounts' => $accounts,
            ]);
        } catch (\Exception $e) {
            Log::error('Pipedream list accounts error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to list accounts',
            ], 500);
        }
    }

    /**
     * Makes an API request to an external service using a connected account
     * Uses PipedreamService to make authenticated requests via API proxy
     */
    public function makeRequest(Request $request, string $appName): JsonResponse
    {
        $user = Auth::user();
        
        if (! $user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $request->validate([
            'method' => 'required|string|in:GET,POST,PUT,PATCH,DELETE',
            'endpoint' => 'required|string',
            'body' => 'nullable|array',
            'headers' => 'nullable|array',
        ]);

        try {
            $account = $this->pipedreamService->getStoredAccount($user->id, $appName);
            
            if (! $account) {
                return response()->json([
                    'success' => false,
                    'error' => 'No connected account found for '.$appName,
                ], 404);
            }
            
            $result = $this->pipedreamService->makeApiRequest(
                accountId: $account->pipedream_account_id,
                method: $request->input('method'),
                endpoint: $request->input('endpoint'),
                body: $request->input('body', []),
                headers: $request->input('headers', [])
            );
            
            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'data' => $result['data'],
                    'status' => $result['status'],
                ]);
            }
            
            return response()->json([
                'success' => false,
                'error' => $result['error'],
                'status' => $result['status'] ?? 500,
            ], $result['status'] ?? 500);
            
        } catch (\Exception $e) {
            Log::error('Pipedream API request error', [
                'error' => $e->getMessage(),
                'app' => $appName,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to make API request: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Handles OAuth callback from Pipedream (optional, not used by SDK)
     */
    public function callback(Request $request, string $appName): RedirectResponse
    {
        $user = Auth::user();
        
        if (! $user) {
            return redirect()->route('onboarding')->with('error', 'Please log in first');
        }

        $connectionId = $request->query('connection_id') ?? session('pipedream_connection_id');
        $error = $request->query('error');

        if ($error) {
            return redirect()->route('onboarding')->with('error', 'Connection cancelled or failed');
        }

        if (! $connectionId) {
            return redirect()->route('onboarding')->with('error', 'Invalid callback - missing connection ID');
        }

        try {
            $accountDetails = $this->pipedreamService->getAccountDetails($connectionId);

            if (! $accountDetails) {
                return redirect()->route('onboarding')->with('error', 'Failed to verify connection');
            }

            $this->pipedreamService->storeAccount(
                userId: $user->id,
                appName: $appName,
                accountId: $connectionId,
                externalUserId: $accountDetails['external_user_id'] ?? (string) $user->id,
                metadata: $accountDetails
            );

            session()->forget(['pipedream_connection_id', 'pipedream_app_name']);

            return redirect()->route('onboarding')->with('success', ucfirst($appName).' connected successfully!');
        } catch (\Exception $e) {
            Log::error('Pipedream callback error', [
                'error' => $e->getMessage(),
                'app' => $appName,
            ]);

            return redirect()->route('onboarding')->with('error', 'Failed to complete connection: '.$e->getMessage());
        }
    }

    /**
     * Disconnects a connected account
     */
    public function disconnect(Request $request, string $appName): JsonResponse
    {
        $user = Auth::user();

        if (! $user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        try {
            $result = $this->pipedreamService->disconnectAccount($user->id, $appName);

            if ($result) {
                return response()->json([
                    'success' => true,
                    'message' => ucfirst($appName).' disconnected successfully',
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => 'Account not found or already disconnected',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Pipedream disconnect error', [
                'error' => $e->getMessage(),
                'app' => $appName,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to disconnect: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Searches for apps in Pipedream Connect
     */
    public function searchApps(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:1',
        ]);

        try {
            $result = $this->pipedreamService->searchApps($request->input('q'));

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'data' => $result['data'],
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => $result['error'],
            ], 500);
        } catch (\Exception $e) {
            Log::error('Pipedream search apps error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to search apps: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Lists available actions for an app
     */
    public function listActions(Request $request, string $appName): JsonResponse
    {
        try {
            $params = $request->only(['limit', 'cursor']);
            $result = $this->pipedreamService->listActions($appName, $params);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'data' => $result['data'],
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => $result['error'],
            ], 500);
        } catch (\Exception $e) {
            Log::error('Pipedream list actions error', [
                'error' => $e->getMessage(),
                'app' => $appName,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to list actions: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Lists available triggers for an app
     */
    public function listTriggers(Request $request, string $appName): JsonResponse
    {
        try {
            $params = $request->only(['limit', 'cursor']);
            $result = $this->pipedreamService->listTriggers($appName, $params);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'data' => $result['data'],
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => $result['error'],
            ], 500);
        } catch (\Exception $e) {
            Log::error('Pipedream list triggers error', [
                'error' => $e->getMessage(),
                'app' => $appName,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to list triggers: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Gets details for a specific component
     */
    public function getComponentDetails(Request $request, string $componentKey): JsonResponse
    {
        $request->validate([
            'type' => 'nullable|string|in:action,trigger',
        ]);

        try {
            $type = $request->input('type', 'action');
            $result = $this->pipedreamService->getComponentDetails($componentKey, $type);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'data' => $result['data'],
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => $result['error'],
            ], 500);
        } catch (\Exception $e) {
            Log::error('Pipedream get component details error', [
                'error' => $e->getMessage(),
                'component_key' => $componentKey,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to get component details: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Runs a Pipedream action
     */
    public function runAction(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (! $user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $request->validate([
            'action_id' => 'required|string',
            'configured_props' => 'required|array',
        ]);

        try {
            $result = $this->pipedreamService->runAction(
                actionId: $request->input('action_id'),
                externalUserId: (string) $user->id,
                configuredProps: $request->input('configured_props')
            );

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'data' => $result['data'],
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => $result['error'],
            ], $result['status'] ?? 500);
        } catch (\Exception $e) {
            Log::error('Pipedream run action error', [
                'error' => $e->getMessage(),
                'action_id' => $request->input('action_id'),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to run action: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Gets available integrations from config
     */
    public function getAvailableIntegrations(Request $request): JsonResponse
    {
        try {
            $integrations = $this->pipedreamService->getAvailableIntegrations();

            $formatted = array_map(function ($key, $config) {
                return [
                    'app_id' => $key,
                    'name' => $config['name'] ?? $key,
                    'category' => $config['category'] ?? 'other',
                    'required' => $config['required'] ?? false,
                ];
            }, array_keys($integrations), $integrations);

            return response()->json([
                'success' => true,
                'integrations' => array_values($formatted),
            ]);
        } catch (\Exception $e) {
            Log::error('Pipedream get integrations error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to get integrations: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Syncs all components for an app and stores them in the database
     * Only syncs if the app is in the configured integrations list
     */
    public function syncComponents(Request $request, string $appName): JsonResponse
    {
        try {
            // Check if integration is configured
            if (! $this->pipedreamService->isIntegrationAvailable($appName)) {
                return response()->json([
                    'success' => false,
                    'error' => "Integration '{$appName}' is not configured. Only configured integrations can be synced.",
                ], 400);
            }

            $appId = $request->input('app_id');
            $result = $this->pipedreamService->syncAppComponents($appName, $appId);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Components synced successfully',
                    'actions_count' => $result['actions_count'],
                    'triggers_count' => $result['triggers_count'],
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => $result['error'],
            ], 500);
        } catch (\Exception $e) {
            Log::error('Pipedream sync components error', [
                'error' => $e->getMessage(),
                'app' => $appName,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to sync components: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Syncs components for all configured integrations
     */
    public function syncAllIntegrations(Request $request): JsonResponse
    {
        try {
            $appIds = $request->input('app_ids'); // Optional: specific apps to sync
            $result = $this->pipedreamService->syncAllConfiguredIntegrations($appIds);

            return response()->json([
                'success' => $result['success'],
                'message' => $result['success']
                    ? 'All integrations synced successfully'
                    : 'Some integrations failed to sync',
                'synced' => $result['synced'],
                'failed' => $result['failed'],
                'total_actions' => $result['total_actions'],
                'total_triggers' => $result['total_triggers'],
                'total_integrations' => $result['total_integrations'],
            ]);
        } catch (\Exception $e) {
            Log::error('Pipedream sync all integrations error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to sync integrations: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Lists stored components from database
     */
    public function listStoredComponents(Request $request): JsonResponse
    {
        try {
            $appName = $request->input('app');
            $type = $request->input('type');
            $activeOnly = $request->input('active_only', true);

            $components = $this->pipedreamService->getStoredComponents($appName, $type, $activeOnly)
                ->map(function ($component) {
                    return [
                        'id' => $component->id,
                        'component_key' => $component->component_key,
                        'component_name' => $component->component_name,
                        'component_type' => $component->component_type,
                        'app_name' => $component->app_name,
                        'app_id' => $component->app_id,
                        'version' => $component->version,
                        'description' => $component->description,
                        'is_active' => $component->is_active,
                        'last_synced_at' => $component->last_synced_at,
                        'created_at' => $component->created_at,
                    ];
                });

            return response()->json([
                'success' => true,
                'components' => $components,
            ]);
        } catch (\Exception $e) {
            Log::error('Pipedream list stored components error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to list stored components: '.$e->getMessage(),
            ], 500);
        }
    }
}
