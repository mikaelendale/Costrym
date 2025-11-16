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
     * Generates a Connect token for the frontend Pipedream SDK
     * Uses PipedreamService to create Connect token
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getToken(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user) {
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
                'error' => 'Failed to generate token: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Saves a connected account after OAuth flow completes
     * Uses PipedreamService to store account in database
     *
     * @param Request $request
     * @param string $appName
     * @return JsonResponse
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
                'message' => ucfirst($appName) . ' connected successfully!',
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
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function listAccounts(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user) {
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
     *
     * @param Request $request
     * @param string $appName
     * @return JsonResponse
     */
    public function makeRequest(Request $request, string $appName): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user) {
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
            
            if (!$account) {
                return response()->json([
                    'success' => false,
                    'error' => 'No connected account found for ' . $appName,
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
                'error' => 'Failed to make API request: ' . $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Handles OAuth callback from Pipedream (optional, not used by SDK)
     *
     * @param Request $request
     * @param string $appName
     * @return RedirectResponse
     */
    public function callback(Request $request, string $appName): RedirectResponse
    {
        $user = Auth::user();
        
        if (!$user) {
            return redirect()->route('onboarding')->with('error', 'Please log in first');
        }

        $connectionId = $request->query('connection_id') ?? session('pipedream_connection_id');
        $error = $request->query('error');

        if ($error) {
            return redirect()->route('onboarding')->with('error', 'Connection cancelled or failed');
        }

        if (!$connectionId) {
            return redirect()->route('onboarding')->with('error', 'Invalid callback - missing connection ID');
        }

        try {
            $accountDetails = $this->pipedreamService->getAccountDetails($connectionId);

            if (!$accountDetails) {
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

            return redirect()->route('onboarding')->with('success', ucfirst($appName) . ' connected successfully!');
        } catch (\Exception $e) {
            Log::error('Pipedream callback error', [
                'error' => $e->getMessage(),
                'app' => $appName,
            ]);

            return redirect()->route('onboarding')->with('error', 'Failed to complete connection: ' . $e->getMessage());
        }
    }
}
