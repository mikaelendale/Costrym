<?php

namespace App\Services;

use App\Models\ConnectedAccount;
use Illuminate\Support\Facades\Auth;
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
        if (empty($this->baseUrl) || !filter_var($this->baseUrl, FILTER_VALIDATE_URL)) {
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
            $oauthTokenUrl = $this->baseUrl . '/oauth/token';
            $response = Http::asJson()->post($oauthTokenUrl, [
                'grant_type' => 'client_credentials',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'scope' => 'connect:accounts:read connect:accounts:write connect:tokens:create',
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
     * @param string $externalUserId User ID in your system
     * @param array $allowedOrigins List of allowed origin URLs
     * @return array{success: bool, token?: string, expires_at?: string, error?: string}
     */
    public function createConnectToken(string $externalUserId, array $allowedOrigins = []): array
    {
        try {
            $accessToken = $this->getOAuthAccessToken();
            if (!$accessToken) {
                return [
                    'success' => false,
                    'error' => 'Failed to authenticate with Pipedream',
                ];
            }
            
            if (empty($allowedOrigins)) {
                $allowedOrigins = [config('app.url'), 'http://localhost:8000', 'http://127.0.0.1:8000'];
            }
            
            $tokenUrl = $this->baseUrl . '/connect/tokens';
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
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
                'error' => 'Failed to generate token: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Retrieves account details from Pipedream API
     *
     * @param string $accountId Pipedream account ID
     * @return array|null Account details or null on failure
     */
    public function getAccountDetails(string $accountId): ?array
    {
        try {
            $accessToken = $this->getOAuthAccessToken();
            if (!$accessToken) {
                return null;
            }
            
            $accountUrl = $this->baseUrl . '/connect/' . $this->projectId . '/accounts/' . $accountId;
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
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
     * @param int $userId User ID
     * @param string $appName Application name (e.g., 'gmail', 'slack')
     * @param string $accountId Pipedream account ID
     * @param string $externalUserId External user ID
     * @param array $metadata Additional account metadata
     * @param \DateTime|null $tokenExpiresAt Token expiration date
     * @return ConnectedAccount Created or updated account model
     */
    public function storeAccount(int $userId, string $appName, string $accountId, string $externalUserId, array $metadata = [], ?\DateTime $tokenExpiresAt = null): ConnectedAccount
    {
        $accountDetails = $this->getAccountDetails($accountId);
        
        if (!$accountDetails) {
            $accountDetails = $metadata;
        }
        
        $mergedMetadata = array_merge($accountDetails, [
            'account_id' => $accountId,
            'app' => $appName,
            'connected_at' => now()->toIso8601String(),
        ]);
        
        if (!empty($metadata)) {
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
     * @param int $userId User ID
     * @param string $appName Application name
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
     * @param int $userId User ID
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
     * @param string $accountId Pipedream account ID
     * @param string $method HTTP method (GET, POST, PUT, PATCH, DELETE)
     * @param string $endpoint External API endpoint URL
     * @param array $body Request body (optional)
     * @param array $headers Additional headers (optional)
     * @return array{success: bool, data?: array, status?: int, error?: string}
     */
    public function makeApiRequest(string $accountId, string $method, string $endpoint, array $body = [], array $headers = []): array
    {
        try {
            $accessToken = $this->getOAuthAccessToken();
            if (!$accessToken) {
                return [
                    'success' => false,
                    'error' => 'Failed to authenticate with Pipedream',
                ];
            }
            
            $proxyUrl = $this->baseUrl . '/connect/' . $this->projectId . '/accounts/' . $accountId . '/proxy';
            
            $requestHeaders = [
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'X-PD-Environment' => $this->projectEnvironment,
            ];
            
            $requestHeaders = array_merge($requestHeaders, $headers);
            
            $proxyBody = [
                'method' => strtoupper($method),
                'url' => $endpoint,
            ];
            
            if (!empty($body)) {
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
                'error' => 'Failed to make API request: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Deactivates a connected account
     *
     * @param int $userId User ID
     * @param string $appName Application name
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
}
