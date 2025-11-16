<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KnowledgeBase extends Model
{
    protected $table = 'knowledge_base';

    protected $fillable = [
        'user_id',
        'context',
    ];

    protected $casts = [
        'context' => 'array',
    ];

    /**
     * Get the user that owns this knowledge base entry
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
