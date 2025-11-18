<?php

namespace App\Services;

use App\Models\ConnectedAccount;
use App\Models\PipedreamComponent;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service class for interacting with Pipedream Connect API
 * Handles authentication, token generation, account management, and API requests
 */
class PipedreamService
{
    private string $clientId;

    private string $clientSecret;

    private string $projectId;

    private string $projectEnvironment;

    private string $baseUrl;

    /**
     * Initializes service with configuration from config/services.php
     * Throws exception if required credentials are missing
     */
    public function __construct()
    {
        $config = config('services.pipedream');
        $this->clientId = trim($config['client_id'] ?? '');
        $this->clientSecret = trim($config['client_secret'] ?? '');
        $this->projectId = trim($config['project_id'] ?? '');
        $this->projectEnvironment = $config['project_environment'] ?? 'development';
        $baseUrl = $config['base_url'] ?? 'https://api.pipedream.com/v1';
        $this->baseUrl = rtrim($baseUrl, '/');

        // Ensure baseUrl is a valid full URL
        if (empty($this->baseUrl) || ! filter_var($this->baseUrl, FILTER_VALIDATE_URL)) {
            $this->baseUrl = 'https://api.pipedream.com/v1';
        }

        if (empty($this->clientId) || empty($this->clientSecret) || empty($this->projectId)) {
            throw new \Exception('Pipedream credentials not fully configured');
        }
    }

    /**
     * Obtains OAuth access token using client credentials
     * Required for all Pipedream API requests
     *
     * @return string|null Access token or null on failure
     */
    public function getOAuthAccessToken(): ?string
    {
        try {
            $oauthTokenUrl = $this->baseUrl.'/oauth/token';
            $response = Http::asJson()->post($oauthTokenUrl, [
                'grant_type' => 'client_credentials',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'scope' => 'connect:*', // Wildcard scope for full Connect API access
                'project_environment' => $this->projectEnvironment,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                return $data['access_token'] ?? null;
            }

            Log::error('Pipedream OAuth token error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Error getting OAuth token', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Creates a Connect token for frontend SDK
     * Two-step process: OAuth token, then Connect token
     *
     * @param  string  $externalUserId  User ID in your system
     * @param  array  $allowedOrigins  List of allowed origin URLs
     * @return array{success: bool, token?: string, expires_at?: string, error?: string}
     */
    public function createConnectToken(string $externalUserId, array $allowedOrigins = []): array
    {
        try {
            $accessToken = $this->getOAuthAccessToken();
            if (! $accessToken) {
                return [
                    'success' => false,
                    'error' => 'Failed to authenticate with Pipedream',
                ];
            }

            if (empty($allowedOrigins)) {
                $allowedOrigins = [config('app.url'), 'http://localhost:8000', 'http://127.0.0.1:8000'];
            }

            $tokenUrl = $this->baseUrl.'/connect/tokens';
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$accessToken,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'X-PD-Environment' => $this->projectEnvironment,
            ])->post($tokenUrl, [
                'external_user_id' => $externalUserId,
                'project_id' => $this->projectId,
                'allowed_origins' => $allowedOrigins,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'success' => true,
                    'token' => $data['token'] ?? null,
                    'expires_at' => $data['expires_at'] ?? null,
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['error'] ?? 'Failed to generate Connect token',
            ];
        } catch (\Exception $e) {
            Log::error('Pipedream token generation error', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'error' => 'Failed to generate token: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Retrieves account details from Pipedream API
     *
     * @param  string  $accountId  Pipedream account ID
     * @return array|null Account details or null on failure
     */
    public function getAccountDetails(string $accountId): ?array
    {
        try {
            $accessToken = $this->getOAuthAccessToken();
            if (! $accessToken) {
                return null;
            }

            $accountUrl = $this->baseUrl.'/connect/'.$this->projectId.'/accounts/'.$accountId;

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$accessToken,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'X-PD-Environment' => $this->projectEnvironment,
            ])->get($accountUrl);

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Error fetching account from Pipedream', [
                'error' => $e->getMessage(),
                'account_id' => $accountId,
            ]);

            return null;
        }
    }

    /**
     * Stores connected account in database
     * Creates or updates existing connection for user and app
     * Includes token expiration and connection status tracking
     *
     * @param  int  $userId  User ID
     * @param  string  $appName  Application name (e.g., 'gmail', 'slack')
     * @param  string  $accountId  Pipedream account ID
     * @param  string  $externalUserId  External user ID
     * @param  array  $metadata  Additional account metadata
     * @param  \DateTime|null  $tokenExpiresAt  Token expiration date
     * @return ConnectedAccount Created or updated account model
     */
    public function storeAccount(int $userId, string $appName, string $accountId, string $externalUserId, array $metadata = [], ?\DateTime $tokenExpiresAt = null): ConnectedAccount
    {
        $accountDetails = $this->getAccountDetails($accountId);

        if (! $accountDetails) {
            $accountDetails = $metadata;
        }

        $mergedMetadata = array_merge($accountDetails, [
            'account_id' => $accountId,
            'app' => $appName,
            'connected_at' => now()->toIso8601String(),
        ]);

        if (! empty($metadata)) {
            $mergedMetadata = array_merge($mergedMetadata, $metadata);
        }

        return ConnectedAccount::updateOrCreate(
            [
                'user_id' => $userId,
                'app_name' => $appName,
            ],
            [
                'pipedream_account_id' => $accountId,
                'external_user_id' => $externalUserId,
                'metadata' => $mergedMetadata,
                'is_active' => true,
                'connection_status' => 'connected',
                'token_expires_at' => $tokenExpiresAt,
                'last_synced_at' => now(),
                'last_error' => null,
            ]
        );
    }

    /**
     * Retrieves stored account for user and app
     * Uses optimized query with indexes
     *
     * @param  int  $userId  User ID
     * @param  string  $appName  Application name
     * @return ConnectedAccount|null Account model or null if not found
     */
    public function getStoredAccount(int $userId, string $appName): ?ConnectedAccount
    {
        return ConnectedAccount::active()
            ->forUser($userId)
            ->forApp($appName)
            ->first();
    }

    /**
     * Lists all stored accounts for a user
     * Uses optimized query with indexes
     *
     * @param  int  $userId  User ID
     * @return \Illuminate\Database\Eloquent\Collection Collection of ConnectedAccount models
     */
    public function listStoredAccounts(int $userId)
    {
        return ConnectedAccount::active()
            ->forUser($userId)
            ->orderBy('app_name')
            ->get();
    }

    /**
     * Makes API request to external service using connected account
     * Uses Pipedream API proxy for authentication
     *
     * @param  string  $accountId  Pipedream account ID
     * @param  string  $method  HTTP method (GET, POST, PUT, PATCH, DELETE)
     * @param  string  $endpoint  External API endpoint URL
     * @param  array  $body  Request body (optional)
     * @param  array  $headers  Additional headers (optional)
     * @return array{success: bool, data?: array, status?: int, error?: string}
     */
    public function makeApiRequest(string $accountId, string $method, string $endpoint, array $body = [], array $headers = []): array
    {
        try {
            $accessToken = $this->getOAuthAccessToken();
            if (! $accessToken) {
                return [
                    'success' => false,
                    'error' => 'Failed to authenticate with Pipedream',
                ];
            }

            $proxyUrl = $this->baseUrl.'/connect/'.$this->projectId.'/accounts/'.$accountId.'/proxy';

            $requestHeaders = [
                'Authorization' => 'Bearer '.$accessToken,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'X-PD-Environment' => $this->projectEnvironment,
            ];

            $requestHeaders = array_merge($requestHeaders, $headers);

            $proxyBody = [
                'method' => strtoupper($method),
                'url' => $endpoint,
            ];

            if (! empty($body)) {
                $proxyBody['body'] = $body;
            }

            $response = Http::withHeaders($requestHeaders)
                ->post($proxyUrl, $proxyBody);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                    'status' => $response->status(),
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['error'] ?? 'Request failed',
                'status' => $response->status(),
            ];
        } catch (\Exception $e) {
            Log::error('Pipedream API request error', [
                'error' => $e->getMessage(),
                'account_id' => $accountId,
            ]);

            return [
                'success' => false,
                'error' => 'Failed to make API request: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Deactivates a connected account
     *
     * @param  int  $userId  User ID
     * @param  string  $appName  Application name
     * @return bool True if account was deactivated
     */
    public function disconnectAccount(int $userId, string $appName): bool
    {
        $account = $this->getStoredAccount($userId, $appName);

        if ($account) {
            $account->update(['is_active' => false]);

            return true;
        }

        return false;
    }

    /**
     * Searches for apps in Pipedream Connect
     *
     * @param  string  $query  Search query (e.g., 'slack', 'gmail')
     * @return array{success: bool, data?: array, error?: string}
     */
    public function searchApps(string $query): array
    {
        try {
            $accessToken = $this->getOAuthAccessToken();
            if (! $accessToken) {
                return [
                    'success' => false,
                    'error' => 'Failed to authenticate with Pipedream',
                ];
            }

            $appsUrl = $this->baseUrl.'/connect/apps';
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$accessToken,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->get($appsUrl, [
                'q' => $query,
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['error'] ?? 'Failed to search apps',
            ];
        } catch (\Exception $e) {
            Log::error('Pipedream search apps error', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'error' => 'Failed to search apps: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Lists available actions for an app
     *
     * @param  string  $appName  App name slug (e.g., 'slack', 'gmail')
     * @param  array  $params  Additional query parameters (limit, cursor, etc.)
     * @return array{success: bool, data?: array, error?: string}
     */
    public function listActions(string $appName, array $params = []): array
    {
        try {
            $accessToken = $this->getOAuthAccessToken();
            if (! $accessToken) {
                return [
                    'success' => false,
                    'error' => 'Failed to authenticate with Pipedream',
                ];
            }

            $actionsUrl = $this->baseUrl.'/connect/'.$this->projectId.'/actions';
            $queryParams = array_merge(['app' => $appName], $params);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$accessToken,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'X-PD-Environment' => $this->projectEnvironment,
            ])->get($actionsUrl, $queryParams);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['error'] ?? 'Failed to list actions',
            ];
        } catch (\Exception $e) {
            Log::error('Pipedream list actions error', [
                'error' => $e->getMessage(),
                'app' => $appName,
            ]);

            return [
                'success' => false,
                'error' => 'Failed to list actions: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Lists available triggers for an app
     *
     * @param  string  $appName  App name slug (e.g., 'slack', 'gmail')
     * @param  array  $params  Additional query parameters (limit, cursor, etc.)
     * @return array{success: bool, data?: array, error?: string}
     */
    public function listTriggers(string $appName, array $params = []): array
    {
        try {
            $accessToken = $this->getOAuthAccessToken();
            if (! $accessToken) {
                return [
                    'success' => false,
                    'error' => 'Failed to authenticate with Pipedream',
                ];
            }

            $triggersUrl = $this->baseUrl.'/connect/'.$this->projectId.'/triggers';
            $queryParams = array_merge(['app' => $appName], $params);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$accessToken,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'X-PD-Environment' => $this->projectEnvironment,
            ])->get($triggersUrl, $queryParams);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['error'] ?? 'Failed to list triggers',
            ];
        } catch (\Exception $e) {
            Log::error('Pipedream list triggers error', [
                'error' => $e->getMessage(),
                'app' => $appName,
            ]);

            return [
                'success' => false,
                'error' => 'Failed to list triggers: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Gets details for a specific component
     *
     * @param  string  $componentKey  Component key (e.g., 'slack-send-message-to-channel')
     * @param  string  $type  Component type ('action' or 'trigger')
     * @return array{success: bool, data?: array, error?: string}
     */
    public function getComponentDetails(string $componentKey, string $type = 'action'): array
    {
        try {
            $accessToken = $this->getOAuthAccessToken();
            if (! $accessToken) {
                return [
                    'success' => false,
                    'error' => 'Failed to authenticate with Pipedream',
                ];
            }

            $endpoint = $type === 'trigger' ? 'triggers' : 'actions';
            $componentUrl = $this->baseUrl.'/connect/'.$this->projectId.'/'.$endpoint.'/'.$componentKey;

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$accessToken,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'X-PD-Environment' => $this->projectEnvironment,
            ])->get($componentUrl);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['error'] ?? 'Failed to get component details',
            ];
        } catch (\Exception $e) {
            Log::error('Pipedream get component details error', [
                'error' => $e->getMessage(),
                'component_key' => $componentKey,
            ]);

            return [
                'success' => false,
                'error' => 'Failed to get component details: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Runs a Pipedream action
     *
     * @param  string  $actionId  Action ID or key (e.g., 'slack-send-message-to-channel')
     * @param  string  $externalUserId  External user ID
     * @param  array  $configuredProps  Configured properties for the action
     * @return array{success: bool, data?: array, error?: string}
     */
    public function runAction(string $actionId, string $externalUserId, array $configuredProps): array
    {
        try {
            $accessToken = $this->getOAuthAccessToken();
            if (! $accessToken) {
                return [
                    'success' => false,
                    'error' => 'Failed to authenticate with Pipedream',
                ];
            }

            $runUrl = $this->baseUrl.'/connect/'.$this->projectId.'/actions/run';

            $requestBody = [
                'id' => $actionId,
                'external_user_id' => $externalUserId,
                'configured_props' => $configuredProps,
            ];

            // Log request (truncate long content for readability)
            $logBody = $requestBody;
            if (isset($logBody['configured_props']['pageContent'])) {
                $logBody['configured_props']['pageContent'] = substr($logBody['configured_props']['pageContent'], 0, 100).'...';
            }

            Log::info('Pipedream run action request', [
                'action_id' => $actionId,
                'url' => $runUrl,
                'request_body' => $logBody,
            ]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$accessToken,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'X-PD-Environment' => $this->projectEnvironment,
            ])->post($runUrl, $requestBody);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            $errorBody = $response->json();
            $errorMessage = $errorBody['error'] ?? $errorBody['message'] ?? 'Failed to run action';

            Log::error('Pipedream run action failed', [
                'action_id' => $actionId,
                'url' => $runUrl,
                'status' => $response->status(),
                'error' => $errorMessage,
                'response_body' => $errorBody,
            ]);

            return [
                'success' => false,
                'error' => $errorMessage,
                'status' => $response->status(),
            ];
        } catch (\Exception $e) {
            Log::error('Pipedream run action error', [
                'error' => $e->getMessage(),
                'action_id' => $actionId,
            ]);

            return [
                'success' => false,
                'error' => 'Failed to run action: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Stores a component in the database
     * Creates or updates existing component record
     *
     * @param  array  $componentData  Component data from Pipedream API
     * @param  string  $type  Component type ('action' or 'trigger')
     * @param  string  $appName  Associated app name
     * @param  string|null  $appId  Pipedream app ID
     * @return PipedreamComponent Created or updated component model
     */
    public function storeComponent(array $componentData, string $type, string $appName, ?string $appId = null): PipedreamComponent
    {
        $componentKey = $componentData['key'] ?? $componentData['id'] ?? null;
        $componentName = $componentData['name'] ?? 'Unknown';
        $version = $componentData['version'] ?? null;
        $description = $componentData['description'] ?? null;

        if (! $componentKey) {
            throw new \Exception('Component key is required');
        }

        return PipedreamComponent::updateOrCreate(
            ['component_key' => $componentKey],
            [
                'component_name' => $componentName,
                'component_type' => $type,
                'app_name' => $appName,
                'app_id' => $appId,
                'version' => $version,
                'component_data' => $componentData,
                'description' => $description,
                'is_active' => true,
                'last_synced_at' => now(),
            ]
        );
    }

    /**
     * Gets available integrations from config
     *
     * @return array Array of integration configurations
     */
    public function getAvailableIntegrations(): array
    {
        return config('pipedream_integrations', []);
    }

    /**
     * Checks if an integration is configured/available
     *
     * @param  string  $appId  App ID to check
     * @return bool True if integration is configured
     */
    public function isIntegrationAvailable(string $appId): bool
    {
        $integrations = $this->getAvailableIntegrations();

        return isset($integrations[$appId]);
    }

    /**
     * Gets integration config by app ID
     *
     * @param  string  $appId  App ID
     * @return array|null Integration config or null if not found
     */
    public function getIntegrationConfig(string $appId): ?array
    {
        $integrations = $this->getAvailableIntegrations();

        return $integrations[$appId] ?? null;
    }

    /**
     * Syncs all components for an app and stores them in the database
     * Only syncs if the app is in the configured integrations list
     *
     * @param  string  $appName  App name slug
     * @param  string|null  $appId  Pipedream app ID (optional)
     * @return array{success: bool, actions_count?: int, triggers_count?: int, error?: string}
     */
    public function syncAppComponents(string $appName, ?string $appId = null): array
    {
        // Check if this integration is configured
        $integration = $this->getIntegrationConfig($appName);
        if (! $integration) {
            return [
                'success' => false,
                'error' => "Integration '{$appName}' is not configured. Only configured integrations can be synced.",
            ];
        }

        try {
            $actionsCount = 0;
            $triggersCount = 0;

            // Fetch all actions with pagination
            $actionsResult = $this->listActions($appName, ['limit' => 100]);
            while ($actionsResult['success'] && isset($actionsResult['data']['data']) && ! empty($actionsResult['data']['data'])) {
                foreach ($actionsResult['data']['data'] as $action) {
                    $this->storeComponent($action, 'action', $appName, $appId);
                    $actionsCount++;
                }

                // Check if there are more pages
                $pageInfo = $actionsResult['data']['page_info'] ?? null;
                if ($pageInfo && isset($pageInfo['end_cursor']) && isset($pageInfo['has_next_page']) && $pageInfo['has_next_page']) {
                    $actionsResult = $this->listActions($appName, ['limit' => 100, 'cursor' => $pageInfo['end_cursor']]);
                } else {
                    break;
                }
            }

            // Fetch all triggers with pagination
            $triggersResult = $this->listTriggers($appName, ['limit' => 100]);
            while ($triggersResult['success'] && isset($triggersResult['data']['data']) && ! empty($triggersResult['data']['data'])) {
                foreach ($triggersResult['data']['data'] as $trigger) {
                    $this->storeComponent($trigger, 'trigger', $appName, $appId);
                    $triggersCount++;
                }

                // Check if there are more pages
                $pageInfo = $triggersResult['data']['page_info'] ?? null;
                if ($pageInfo && isset($pageInfo['end_cursor']) && isset($pageInfo['has_next_page']) && $pageInfo['has_next_page']) {
                    $triggersResult = $this->listTriggers($appName, ['limit' => 100, 'cursor' => $pageInfo['end_cursor']]);
                } else {
                    break;
                }
            }

            return [
                'success' => true,
                'actions_count' => $actionsCount,
                'triggers_count' => $triggersCount,
            ];
        } catch (\Exception $e) {
            Log::error('Pipedream sync components error', [
                'error' => $e->getMessage(),
                'app' => $appName,
            ]);

            return [
                'success' => false,
                'error' => 'Failed to sync components: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Syncs components for all configured integrations
     * This is useful for initial setup or periodic syncing
     *
     * @param  array|null  $appIds  Optional array of specific app IDs to sync (if null, syncs all)
     * @return array{success: bool, synced: array, failed: array, total_actions: int, total_triggers: int}
     */
    public function syncAllConfiguredIntegrations(?array $appIds = null): array
    {
        $integrations = $this->getAvailableIntegrations();
        $synced = [];
        $failed = [];
        $totalActions = 0;
        $totalTriggers = 0;

        // If specific app IDs provided, only sync those
        if ($appIds !== null) {
            $integrations = array_filter($integrations, function ($key) use ($appIds) {
                return in_array($key, $appIds);
            }, ARRAY_FILTER_USE_KEY);
        }

        foreach ($integrations as $appId => $config) {
            $appName = $config['app_id'] ?? $appId;

            Log::info("Syncing components for integration: {$appName}");

            $result = $this->syncAppComponents($appName, null);

            if ($result['success']) {
                $synced[] = [
                    'app_id' => $appId,
                    'app_name' => $appName,
                    'name' => $config['name'] ?? $appName,
                    'actions_count' => $result['actions_count'] ?? 0,
                    'triggers_count' => $result['triggers_count'] ?? 0,
                ];
                $totalActions += $result['actions_count'] ?? 0;
                $totalTriggers += $result['triggers_count'] ?? 0;
            } else {
                $failed[] = [
                    'app_id' => $appId,
                    'app_name' => $appName,
                    'name' => $config['name'] ?? $appName,
                    'error' => $result['error'] ?? 'Unknown error',
                ];
            }
        }

        return [
            'success' => count($failed) === 0,
            'synced' => $synced,
            'failed' => $failed,
            'total_actions' => $totalActions,
            'total_triggers' => $totalTriggers,
            'total_integrations' => count($integrations),
        ];
    }

    /**
     * Retrieves stored components from database
     *
     * @param  string|null  $appName  Filter by app name (optional)
     * @param  string|null  $type  Filter by type ('action' or 'trigger') (optional)
     * @param  bool  $activeOnly  Only return active components
     * @return \Illuminate\Database\Eloquent\Collection Collection of PipedreamComponent models
     */
    public function getStoredComponents(?string $appName = null, ?string $type = null, bool $activeOnly = true)
    {
        $query = PipedreamComponent::query();

        if ($activeOnly) {
            $query->active();
        }

        if ($appName) {
            $query->forApp($appName);
        }

        if ($type === 'action') {
            $query->actions();
        } elseif ($type === 'trigger') {
            $query->triggers();
        }

        return $query->orderBy('app_name')->orderBy('component_name')->get();
    }

    /**
     * Gets a stored component by key
     *
     * @param  string  $componentKey  Component key
     * @return PipedreamComponent|null Component model or null if not found
     */
    public function getStoredComponent(string $componentKey): ?PipedreamComponent
    {
        return PipedreamComponent::where('component_key', $componentKey)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Gets available Pipedream actions for a user based on their connected accounts
     * Only returns actions for apps the user has connected
     *
     * @param  int  $userId  User ID
     * @return array Array of action information with app_name, component_key, component_name, description
     */
    public function getAvailableActionsForUser(int $userId): array
    {
        // Get all connected accounts for the user
        $connectedAccounts = $this->listStoredAccounts($userId);

        if ($connectedAccounts->isEmpty()) {
            return [];
        }

        // Get app names from connected accounts
        $appNames = $connectedAccounts->pluck('app_name')->unique()->toArray();

        // Get all active actions for these apps
        $actions = PipedreamComponent::active()
            ->actions()
            ->whereIn('app_name', $appNames)
            ->orderBy('app_name')
            ->orderBy('component_name')
            ->get();

        // Format the response
        return $actions->map(function ($action) {
            $componentData = $action->component_data ?? [];

            return [
                'app_name' => $action->app_name,
                'component_key' => $action->component_key,
                'component_name' => $action->component_name,
                'description' => $action->description ?? $componentData['description'] ?? '',
                'version' => $action->version,
            ];
        })->toArray();
    }

    /**
     * Gets available actions grouped by app for a user
     *
     * @param  int  $userId  User ID
     * @return array Array grouped by app_name
     */
    public function getAvailableActionsGroupedByApp(int $userId): array
    {
        $actions = $this->getAvailableActionsForUser($userId);

        $grouped = [];
        foreach ($actions as $action) {
            $appName = $action['app_name'];
            if (! isset($grouped[$appName])) {
                $grouped[$appName] = [];
            }
            $grouped[$appName][] = $action;
        }

        return $grouped;
    }
}
