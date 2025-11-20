<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IngestionLog extends Model
{
    protected $fillable = [
        'user_id',
        'integration_type',
        'status',
        'records_fetched',
        'records_saved',
        'records_updated',
        'records_skipped',
        'errors',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'records_fetched' => 'integer',
            'records_saved' => 'integer',
            'records_updated' => 'integer',
            'records_skipped' => 'integer',
            'errors' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * Get the user that owns this ingestion log
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: Filter by integration type
     */
    public function scopeForIntegration($query, string $integrationType)
    {
        return $query->where('integration_type', $integrationType);
    }

    /**
     * Scope: Filter by status
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Get recent logs
     */
    public function scopeRecent($query, int $limit = 10)
    {
        return $query->latest('started_at')->limit($limit);
    }

    /**
     * Mark as running
     */
    public function markAsRunning(): void
    {
        $this->update([
            'status' => 'running',
            'started_at' => now(),
        ]);
    }

    /**
     * Mark as completed
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark as failed
     */
    public function markAsFailed(array $errors = []): void
    {
        $this->update([
            'status' => 'failed',
            'completed_at' => now(),
            'errors' => $errors,
        ]);
    }

    /**
     * Get the duration in seconds
     */
    public function getDurationInSeconds(): ?int
    {
        if (! $this->started_at || ! $this->completed_at) {
            return null;
        }

        return $this->completed_at->diffInSeconds($this->started_at);
    }

    /**
     * Check if the ingestion is currently running
     */
    public function isRunning(): bool
    {
        return $this->status === 'running';
    }

    /**
     * Check if the ingestion completed successfully
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if the ingestion failed
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }
}
