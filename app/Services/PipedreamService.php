<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PipedreamService
{
    private string $clientId;
    private string $clientSecret;
    private string $projectId;
    private string $projectEnvironment;
    private string $baseUrl;

    public function __construct()
    {
        $config = config('services.pipedream');
        $this->clientId = $config['client_id'] ?? '';
        $this->clientSecret = $config['client_secret'] ?? '';
        $this->projectId = $config['project_id'] ?? '';
        $this->projectEnvironment = $config['project_environment'] ?? 'development';
        $this->baseUrl = $config['base_url'] ?? 'https://api.pipedream.com/v1';
        
        if (!$this->clientId || !$this->clientSecret || !$this->projectId) {
            throw new \Exception('Pipedream credentials not fully configured');
        }
    }

    /**
     * Get authorization URL for connecting an app
     */
    public function getAuthUrl(string $appName, string $externalUserId, ?string $redirectUri = null): array
    {
        try {
            // Use Basic Auth with client_id:client_secret
            $auth = base64_encode($this->clientId . ':' . $this->clientSecret);
            
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . $auth,
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/connect/auth", [
                'app' => $appName,
                'external_user_id' => $externalUserId,
                'project_id' => $this->projectId,
                'project_environment' => $this->projectEnvironment,
                'redirect_uri' => $redirectUri ?? route('pipedream.callback', ['app' => $appName]),
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'auth_url' => $response->json()['auth_url'] ?? null,
                    'connection_id' => $response->json()['connection_id'] ?? null,
                ];
            }

            Log::error('Pipedream auth URL failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'error' => $response->json()['error'] ?? 'Failed to get auth URL',
            ];
        } catch (\Exception $e) {
            Log::error('Pipedream service error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get connected account details
     */
    public function getConnectedAccount(string $connectionId): ?array
    {
        try {
            $auth = base64_encode($this->clientId . ':' . $this->clientSecret);
            
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . $auth,
            ])->get("{$this->baseUrl}/connect/connections/{$connectionId}", [
                'project_id' => $this->projectId,
                'project_environment' => $this->projectEnvironment,
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Pipedream get connection error', ['error' => $e->getMessage()]);
            return null;
        }
    }
}

