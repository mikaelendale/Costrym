<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Crypt;

/**
 * ConnectedAccount Model
 * 
 * Represents a connected third-party account via Pipedream Connect
 * Includes encryption for sensitive metadata and query scopes for common operations
 */
class ConnectedAccount extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable
     */
    protected $fillable = [
        'user_id',
        'app_name',
        'pipedream_account_id',
        'external_user_id',
        'metadata',
        'is_active',
        'token_expires_at',
        'last_synced_at',
        'connection_status',
        'last_error',
    ];

    /**
     * The attributes that should be cast
     * Note: metadata is NOT cast to JSON because it's encrypted as a string
     */
    protected $casts = [
        'is_active' => 'boolean',
        'token_expires_at' => 'datetime',
        'last_synced_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        // metadata is handled by accessor/mutator for encryption, not cast
    ];

    /**
     * Attributes that should be encrypted when stored
     */
    protected $encrypted = [
        'metadata',
    ];

    /**
     * Get the user that owns the connected account
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include active connections
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where('connection_status', 'connected');
    }

    /**
     * Scope a query to only include connections for a specific app
     */
    public function scopeForApp(Builder $query, string $appName): Builder
    {
        return $query->where('app_name', $appName);
    }

    /**
     * Scope a query to only include connections for a specific user
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to only include expired connections
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('token_expires_at', '<', now())
            ->where('is_active', true);
    }

    /**
     * Scope a query to only include connections that need syncing
     */
    public function scopeNeedsSync(Builder $query, int $hours = 24): Builder
    {
        return $query->where(function ($q) use ($hours) {
            $q->whereNull('last_synced_at')
                ->orWhere('last_synced_at', '<', now()->subHours($hours));
        })
        ->where('is_active', true);
    }

    /**
     * Check if the connection token is expired
     */
    public function isExpired(): bool
    {
        if (!$this->token_expires_at) {
            return false;
        }
        return $this->token_expires_at->isPast();
    }

    /**
     * Check if the connection needs syncing
     */
    public function needsSync(int $hours = 24): bool
    {
        if (!$this->last_synced_at) {
            return true;
        }
        return $this->last_synced_at->lt(now()->subHours($hours));
    }

    /**
     * Mark connection as synced
     */
    public function markAsSynced(): void
    {
        $this->update(['last_synced_at' => now()]);
    }

    /**
     * Mark connection as expired
     */
    public function markAsExpired(): void
    {
        $this->update([
            'is_active' => false,
            'connection_status' => 'expired',
        ]);
    }

    /**
     * Mark connection with error
     */
    public function markAsError(string $error): void
    {
        $this->update([
            'connection_status' => 'error',
            'last_error' => $error,
        ]);
    }

    /**
     * Get metadata with automatic decryption
     * Handles both encrypted and unencrypted data for backward compatibility
     */
    public function getMetadataAttribute($value)
    {
        if (empty($value)) {
            return [];
        }
        
        // If value is already an array (from JSON decode), check if it's encrypted
        if (is_array($value)) {
            // Check if it's our encrypted format
            if (isset($value['encrypted']) && is_string($value['encrypted'])) {
                try {
                    $decrypted = Crypt::decryptString($value['encrypted']);
                    return json_decode($decrypted, true) ?? [];
                } catch (\Exception $e) {
                    return [];
                }
            }
            // Otherwise return as-is (unencrypted data)
            return $value;
        }
        
        // If value is a string, try to decrypt or decode
        if (is_string($value)) {
            try {
                // Try to decrypt (for encrypted data)
                $decrypted = Crypt::decryptString($value);
                return json_decode($decrypted, true) ?? [];
            } catch (\Exception $e) {
                // If decryption fails, try to decode as JSON (for unencrypted data)
                $decoded = json_decode($value, true);
                return is_array($decoded) ? $decoded : [];
            }
        }
        
        return [];
    }

    /**
     * Set metadata with automatic encryption
     * Encrypts sensitive data before storing
     * Note: JSON column type requires valid JSON string
     */
    public function setMetadataAttribute($value)
    {
        if (empty($value)) {
            $this->attributes['metadata'] = null;
            return;
        }
        
        // If value is already a JSON string with encrypted format, store as-is
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (is_array($decoded) && isset($decoded['encrypted']) && count($decoded) === 1) {
                // Already in encrypted format, store as JSON string
                $this->attributes['metadata'] = $value;
                return;
            }
        }
        
        // Encrypt sensitive metadata
        $json = is_array($value) ? json_encode($value) : (is_string($value) ? $value : json_encode($value));
        $encrypted = Crypt::encryptString($json);
        
        // Store encrypted value as JSON string (required for JSON column type)
        $this->attributes['metadata'] = json_encode(['encrypted' => $encrypted]);
    }

    /**
     * Get a specific metadata value
     */
    public function getMetadataValue(string $key, $default = null)
    {
        $metadata = $this->metadata;
        return $metadata[$key] ?? $default;
    }

    /**
     * Set a specific metadata value
     */
    public function setMetadataValue(string $key, $value): void
    {
        $metadata = $this->metadata;
        $metadata[$key] = $value;
        $this->metadata = $metadata;
        $this->save();
    }
}
