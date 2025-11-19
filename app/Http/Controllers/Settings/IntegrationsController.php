<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Services\PipedreamService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class IntegrationsController extends Controller
{
    public function __construct(
        private PipedreamService $pipedreamService
    ) {}

    /**
     * Display the integrations page
     */
    public function index(Request $request): Response
    {
        $user = Auth::user();

        // Get connected accounts
        $connectedAccounts = $this->pipedreamService->listStoredAccounts($user->id)
            ->map(function ($account) {
                return [
                    'id' => $account->id,
                    'pipedream_account_id' => $account->pipedream_account_id,
                    'app' => $account->app_name,
                    'external_user_id' => $account->external_user_id,
                    'metadata' => $account->metadata,
                    'is_active' => $account->is_active,
                    'connected_at' => $account->created_at?->toISOString(),
                    'updated_at' => $account->updated_at?->toISOString(),
                ];
            });

        // Get available integrations from config
        $availableIntegrations = collect($this->pipedreamService->getAvailableIntegrations())
            ->map(function ($config, $appId) {
                return [
                    'app_id' => $appId,
                    'name' => $config['name'] ?? $appId,
                    'category' => $config['category'] ?? 'other',
                    'required' => $config['required'] ?? false,
                ];
            })
            ->values()
            ->toArray();

        return Inertia::render('settings/integrations', [
            'connectedAccounts' => $connectedAccounts,
            'availableIntegrations' => $availableIntegrations,
        ]);
    }
}
