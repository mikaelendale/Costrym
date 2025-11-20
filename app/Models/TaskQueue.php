<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskQueue extends Model
{
    use HasFactory;

    protected $table = 'task_queue';

    protected $fillable = [
        'task_id',
        'user_id',
        'agent_name',
        'status',
        'priority',
        'payload',
        'attempts',
        'max_attempts',
        'scheduled_at',
        'started_at',
        'completed_at',
        'result',
        'error',
    ];

    protected $casts = [
        'payload' => 'array',
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the task that this queue item belongs to
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Get the user that owns this queue item
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get queued tasks ready to process
     */
    public function scopeReadyToProcess($query)
    {
        return $query->where('status', 'queued')
            ->where(function ($q) {
                $q->whereNull('scheduled_at')
                    ->orWhere('scheduled_at', '<=', now());
            })
            ->where('attempts', '<', \DB::raw('max_attempts'))
            ->orderBy('priority', 'desc')
            ->orderBy('scheduled_at')
            ->orderBy('created_at');
    }

    /**
     * Scope to get tasks by agent
     */
    public function scopeForAgent($query, string $agentName)
    {
        return $query->where('agent_name', $agentName);
    }

    /**
     * Scope to get tasks by user
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Mark as processing
     */
    public function markAsProcessing(): void
    {
        $this->update([
            'status' => 'processing',
            'started_at' => now(),
            'attempts' => $this->attempts + 1,
        ]);
    }

    /**
     * Mark as completed
     */
    public function markAsCompleted(?string $result = null): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'result' => $result,
        ]);

        // Update the original task
        $this->task->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark as failed
     */
    public function markAsFailed(string $error): void
    {
        $shouldRetry = $this->attempts < $this->max_attempts;

        $this->update([
            'status' => $shouldRetry ? 'retrying' : 'failed',
            'error' => $error,
        ]);

        // Update the original task if all attempts exhausted
        if (! $shouldRetry) {
            $this->task->update([
                'status' => 'failed',
                'error_message' => $error,
            ]);
        }
    }
}
