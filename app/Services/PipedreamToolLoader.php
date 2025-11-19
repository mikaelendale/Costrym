<?php

namespace App\Services;

use App\Models\ConnectedAccount;
use App\Models\PipedreamComponent;
use App\Tools\PipedreamTool;
use Illuminate\Support\Collection;

/**
 * Service for loading Pipedream tools dynamically for agents
 *
 * This service handles:
 * - Loading available Pipedream actions for a user based on connected accounts
 * - Creating tool instances from stored components
 * - Filtering and organizing tools by app
 */
class PipedreamToolLoader
{
    protected PipedreamService $pipedreamService;

    public function __construct(PipedreamService $pipedreamService)
    {
        $this->pipedreamService = $pipedreamService;
    }

    /**
     * Load all available Pipedream tools for a user
     *
     * @param  int  $userId  The user ID
     * @param  bool  $requiredOnly  Only load tools for required integrations
     * @return Collection<PipedreamTool> Collection of tool instances
     */
    public function loadToolsForUser(int $userId, bool $requiredOnly = false): Collection
    {
        // Get all connected accounts for the user
        $connectedAccounts = ConnectedAccount::where('user_id', $userId)
            ->where('is_active', true)
            ->get();

        if ($connectedAccounts->isEmpty()) {
            return collect([]);
        }

        // Get app names from connected accounts
        $appNames = $connectedAccounts->pluck('app_name')->unique()->toArray();

        // Filter to required integrations if requested
        if ($requiredOnly) {
            $integrations = $this->pipedreamService->getAvailableIntegrations();
            $requiredAppIds = array_keys(array_filter($integrations, function ($config) {
                return ($config['required'] ?? false) === true;
            }));
            $appNames = array_intersect($appNames, $requiredAppIds);
        }

        if (empty($appNames)) {
            return collect([]);
        }

        // Get all active actions for these apps (triggers are event sources, not executable tools)
        $components = PipedreamComponent::active()
            ->actions()
            ->whereIn('app_name', $appNames)
            ->orderBy('app_name')
            ->orderBy('component_name')
            ->get();

        // Create tool instances
        return $components->map(function ($component) use ($userId) {
            return new PipedreamTool($component, $userId);
        });
    }

    /**
     * Load tools for a specific app
     *
     * @param  int  $userId  The user ID
     * @param  string  $appName  The app name (e.g., 'notion')
     * @return Collection<PipedreamTool> Collection of tool instances
     */
    public function loadToolsForApp(int $userId, string $appName): Collection
    {
        // Verify user has connected account for this app
        $hasConnection = ConnectedAccount::where('user_id', $userId)
            ->where('app_name', $appName)
            ->where('is_active', true)
            ->exists();

        if (! $hasConnection) {
            return collect([]);
        }

        // Get actions for this app (triggers are event sources, not executable tools)
        $components = PipedreamComponent::active()
            ->actions()
            ->where('app_name', $appName)
            ->orderBy('component_name')
            ->get();

        return $components->map(function ($component) use ($userId) {
            return new PipedreamTool($component, $userId);
        });
    }

    /**
     * Get a summary of available tools for a user
     *
     * @param  int  $userId  The user ID
     * @param  bool  $requiredOnly  Only include required integrations
     * @return array Summary with app names and tool counts
     */
    public function getToolsSummary(int $userId, bool $requiredOnly = false): array
    {
        $tools = $this->loadToolsForUser($userId, $requiredOnly);

        $summary = [];
        foreach ($tools as $tool) {
            $appName = $tool->getComponent()->app_name;
            if (! isset($summary[$appName])) {
                $summary[$appName] = [
                    'app_name' => $appName,
                    'tool_count' => 0,
                    'tools' => [],
                ];
            }
            $summary[$appName]['tool_count']++;
            $summary[$appName]['tools'][] = [
                'name' => $tool->getComponent()->component_name,
                'key' => $tool->getComponent()->component_key,
                'description' => $tool->getComponent()->description,
            ];
        }

        return array_values($summary);
    }

    /**
     * Check if user has any connected Pipedream accounts
     *
     * @param  int  $userId  The user ID
     */
    public function userHasConnectedAccounts(int $userId): bool
    {
        return ConnectedAccount::where('user_id', $userId)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Get list of connected app names for a user
     *
     * @param  int  $userId  The user ID
     * @return array Array of app names
     */
    public function getConnectedAppNames(int $userId): array
    {
        return ConnectedAccount::where('user_id', $userId)
            ->where('is_active', true)
            ->pluck('app_name')
            ->unique()
            ->toArray();
    }
}
