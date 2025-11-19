<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * PipedreamComponent Model
 *
 * Represents a Pipedream Connect component (action or trigger)
 * Stores component metadata and configuration for easy access
 */
class PipedreamComponent extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable
     */
    protected $fillable = [
        'component_key',
        'component_name',
        'component_type',
        'app_name',
        'app_id',
        'version',
        'component_data',
        'description',
        'last_synced_at',
        'is_active',
    ];

    /**
     * The attributes that should be cast
     */
    protected $casts = [
        'component_data' => 'array',
        'is_active' => 'boolean',
        'last_synced_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Scope a query to only include active components
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include actions
     */
    public function scopeActions(Builder $query): Builder
    {
        return $query->where('component_type', 'action');
    }

    /**
     * Scope a query to only include triggers
     */
    public function scopeTriggers(Builder $query): Builder
    {
        return $query->where('component_type', 'trigger');
    }

    /**
     * Scope a query to only include components for a specific app
     */
    public function scopeForApp(Builder $query, string $appName): Builder
    {
        return $query->where('app_name', $appName);
    }

    /**
     * Scope a query to only include components that need syncing
     */
    public function scopeNeedsSync(Builder $query, int $hours = 24): Builder
    {
        return $query->where(function ($q) use ($hours) {
            $q->whereNull('last_synced_at')
                ->orWhere('last_synced_at', '<', now()->subHours($hours));
        });
    }

    /**
     * Mark component as synced
     */
    public function markAsSynced(): void
    {
        $this->update(['last_synced_at' => now()]);
    }
}
