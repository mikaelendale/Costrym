<?php

namespace App\Http\Controllers;

use App\Models\ConnectedAccount;
use App\Services\PipedreamService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PipedreamConnectController extends Controller
{
    public function __construct(
        private PipedreamService $pipedreamService
    ) {}

    /**
     * Generate Connect token for frontend SDK
     */
    public function getToken(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        try {
            $config = config('services.pipedream');
            
            if (!$config || !isset($config['client_id']) || !isset($config['client_secret']) || !isset($config['project_id'])) {
                Log::error('Pipedream config missing', ['config' => $config]);
                return response()->json([
                    'success' => false,
                    'error' => 'Pipedream configuration is incomplete',
                ], 500);
            }
            
            $allowedOrigins = [config('app.url'), 'http://localhost:8000', 'http://127.0.0.1:8000'];
            $baseUrl = !empty($config['base_url']) ? $config['base_url'] : 'https://api.pipedream.com/v1';
            
            // Pipedream Connect token endpoint
            $tokenUrl = rtrim($baseUrl, '/') . '/connect/tokens';
            
            // Use Basic Auth with client_id:client_secret
            // Make sure credentials don't have extra whitespace
            $clientId = trim($config['client_id']);
            $clientSecret = trim($config['client_secret']);
            
            // Verify credentials are not empty
            if (empty($clientId) || empty($clientSecret)) {
                Log::error('Pipedream credentials are empty');
                return response()->json([
                    'success' => false,
                    'error' => 'Pipedream credentials are not configured',
                ], 500);
            }
            
            $auth = base64_encode($clientId . ':' . $clientSecret);
            
            Log::info('Creating Pipedream token', [
                'url' => $tokenUrl,
                'external_user_id' => (string) $user->id,
                'project_id' => $config['project_id'],
                'client_id_length' => strlen($clientId),
                'client_secret_length' => strlen($clientSecret),
            ]);
            
            // According to Pipedream docs, use Basic Auth and JSON body
            // Laravel Http will JSON-encode the body, so use array for allowed_origins
            $requestBody = [
                'external_user_id' => (string) $user->id,
                'project_id' => $config['project_id'],
                'project_environment' => $config['project_environment'] ?? 'development',
                'allowed_origins' => $allowedOrigins, // Array - Laravel will JSON-encode it
            ];
            
            Log::debug('Pipedream token request', [
                'url' => $tokenUrl,
                'body' => $requestBody,
                'client_id_prefix' => substr($config['client_id'], 0, 10) . '...',
            ]);
            
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . $auth,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post($tokenUrl, $requestBody);

            if ($response->successful()) {
                $data = $response->json();
                return response()->json([
                    'success' => true,
                    'token' => $data['token'] ?? null,
                ]);
            }

            Log::error('Pipedream token API error', [
                'status' => $response->status(),
                'body' => $response->body(),
                'json' => $response->json(),
                'headers' => $response->headers(),
            ]);

            return response()->json([
                'success' => false,
                'error' => $response->json()['error'] ?? 'Failed to generate token',
                'details' => config('app.debug') ? $response->body() : null,
            ], 500);
        } catch (\Exception $e) {
            Log::error('Pipedream token generation error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Failed to generate token: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Save connection after frontend SDK completes auth
     */
    public function saveConnection(Request $request, string $appName): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $request->validate([
            'connection_id' => 'required|string',
            'external_user_id' => 'nullable|string',
            'metadata' => 'nullable|array',
        ]);

        try {
            ConnectedAccount::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'app_name' => $appName,
                ],
                [
                    'pipedream_account_id' => $request->input('connection_id'),
                    'external_user_id' => $request->input('external_user_id') ?? (string) $user->id,
                    'metadata' => $request->input('metadata', []),
                    'is_active' => true,
                ]
            );

            return response()->json([
                'success' => true,
                'message' => ucfirst($appName) . ' connected successfully!',
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
     * Handle OAuth callback from Pipedream
     */
    public function callback(Request $request, string $appName): RedirectResponse
    {
        $user = Auth::user();
        
        if (!$user) {
            return redirect()->route('onboarding')->with('error', 'Please log in first');
        }

        // Get connection_id from query params or session
        $connectionId = $request->query('connection_id') ?? session('pipedream_connection_id');
        $code = $request->query('code');
        $error = $request->query('error');

        if ($error) {
            return redirect()->route('onboarding')->with('error', 'Connection cancelled or failed');
        }

        if (!$connectionId) {
            Log::warning('Pipedream callback missing connection_id', [
                'query' => $request->query(),
                'session' => session()->all(),
            ]);
            return redirect()->route('onboarding')->with('error', 'Invalid callback - missing connection ID');
        }

        try {
            // Get connected account details from Pipedream
            $account = $this->pipedreamService->getConnectedAccount($connectionId);

            if (!$account) {
                return redirect()->route('onboarding')->with('error', 'Failed to verify connection');
            }

            // Save to database
            ConnectedAccount::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'app_name' => $appName,
                ],
                [
                    'pipedream_account_id' => $connectionId,
                    'external_user_id' => $account['external_user_id'] ?? (string) $user->id,
                    'metadata' => $account,
                    'is_active' => true,
                ]
            );

            // Clear session
            session()->forget(['pipedream_connection_id', 'pipedream_app_name']);

            return redirect()->route('onboarding')->with('success', ucfirst($appName) . ' connected successfully!');
        } catch (\Exception $e) {
            Log::error('Pipedream callback error', [
                'error' => $e->getMessage(),
                'app' => $appName,
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('onboarding')->with('error', 'Failed to complete connection: ' . $e->getMessage());
        }
    }
}

