<?php

namespace App\Repositories;

use App\Models\ConnectedAccount;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Repository for ConnectedAccount model
 * Provides data access layer with optimized queries and caching
 */
class ConnectedAccountRepository
{
    /**
     * Get active account for user and app
     * Uses index for optimal performance
     */
    public function getActiveAccount(int $userId, string $appName): ?ConnectedAccount
    {
        return ConnectedAccount::where('user_id', $userId)
            ->where('app_name', $appName)
            ->where('is_active', true)
            ->where('connection_status', 'connected')
            ->first();
    }

    /**
     * Get all active accounts for user
     * Optimized query with proper indexes
     */
    public function getActiveAccountsForUser(int $userId): Collection
    {
        return ConnectedAccount::where('user_id', $userId)
            ->where('is_active', true)
            ->where('connection_status', 'connected')
            ->orderBy('app_name')
            ->get();
    }

    /**
     * Get accounts by app name across all users
     * Useful for admin operations
     */
    public function getAccountsByApp(string $appName, int $limit = 100): Collection
    {
        return ConnectedAccount::where('app_name', $appName)
            ->where('is_active', true)
            ->limit($limit)
            ->get();
    }

    /**
     * Get expired connections that need attention
     */
    public function getExpiredConnections(int $limit = 100): Collection
    {
        return ConnectedAccount::expired()
            ->limit($limit)
            ->get();
    }

    /**
     * Get connections that need syncing
     */
    public function getConnectionsNeedingSync(int $hours = 24, int $limit = 100): Collection
    {
        return ConnectedAccount::needsSync($hours)
            ->limit($limit)
            ->get();
    }

    /**
     * Get paginated accounts for user
     */
    public function getPaginatedAccountsForUser(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return ConnectedAccount::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Count active connections for user
     */
    public function countActiveConnections(int $userId): int
    {
        return ConnectedAccount::where('user_id', $userId)
            ->where('is_active', true)
            ->where('connection_status', 'connected')
            ->count();
    }

    /**
     * Check if user has connection for app
     */
    public function hasConnection(int $userId, string $appName): bool
    {
        return ConnectedAccount::where('user_id', $userId)
            ->where('app_name', $appName)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Bulk deactivate expired connections
     *
     * @return int Number of connections deactivated
     */
    public function deactivateExpiredConnections(int $limit = 100): int
    {
        return ConnectedAccount::expired()
            ->limit($limit)
            ->update([
                'is_active' => false,
                'connection_status' => 'expired',
            ]);
    }

    /**
     * Get connection statistics for user
     */
    public function getConnectionStats(int $userId): array
    {
        $stats = ConnectedAccount::where('user_id', $userId)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN is_active = 1 AND connection_status = "connected" THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN connection_status = "expired" THEN 1 ELSE 0 END) as expired,
                SUM(CASE WHEN connection_status = "error" THEN 1 ELSE 0 END) as errors
            ')
            ->first();

        return [
            'total' => $stats->total ?? 0,
            'active' => $stats->active ?? 0,
            'expired' => $stats->expired ?? 0,
            'errors' => $stats->errors ?? 0,
        ];
    }
}
