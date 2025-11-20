<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Automation extends Model
{
    protected $fillable = [
        'user_id',
        'task_id',
        'task_queue_id',
        'type',
        'name',
        'description',
        'markdown_content',
        'file_path',
        'metadata',
        'status',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that owns this automation
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Accessor for formatted created_at
     */
    protected function createdAt(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn ($value) => $value ? \Carbon\Carbon::parse($value)->format('Y-m-d H:i:s') : null,
        );
    }

    /**
     * Get the associated task
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Get the associated task queue entry
     */
    public function taskQueue(): BelongsTo
    {
        return $this->belongsTo(TaskQueue::class);
    }

    /**
     * Save markdown content to storage and update file_path
     */
    public function saveToStorage(): bool
    {
        if (empty($this->markdown_content)) {
            return false;
        }

        $fileName = "automation_{$this->id}_".now()->timestamp.'.md';
        $path = "automations/{$this->user_id}/{$fileName}";

        \Storage::disk('private')->put($path, $this->markdown_content);

        $this->update(['file_path' => $path]);

        return true;
    }

    /**
     * Get markdown content from storage
     */
    public function getContentFromStorage(): ?string
    {
        if (empty($this->file_path)) {
            return $this->markdown_content;
        }

        if (\Storage::disk('private')->exists($this->file_path)) {
            return \Storage::disk('private')->get($this->file_path);
        }

        return $this->markdown_content;
    }

    /**
     * Archive this automation
     */
    public function archive(): void
    {
        $this->update(['status' => 'archived']);
    }
}
