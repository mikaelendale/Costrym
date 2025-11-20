<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialRecord extends Model
{
    protected $fillable = [
        'user_id',
        'integration_type',
        'integration_record_id',
        'record_type',
        'amount',
        'currency',
        'date',
        'description',
        'category_id',
        'raw_data',
        'normalized_data',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'date' => 'date',
            'raw_data' => 'array',
            'normalized_data' => 'array',
            'metadata' => 'array',
        ];
    }

    /**
     * Get the user that owns this financial record
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the category for this financial record
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(FinancialCategory::class, 'category_id');
    }

    /**
     * Scope: Filter by integration type
     */
    public function scopeForIntegration($query, string $integrationType)
    {
        return $query->where('integration_type', $integrationType);
    }

    /**
     * Scope: Filter by record type
     */
    public function scopeOfType($query, string $recordType)
    {
        return $query->where('record_type', $recordType);
    }

    /**
     * Scope: Filter by date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Get a unique identifier for this record (integration + external ID)
     */
    public function getUniqueIdentifier(): string
    {
        return "{$this->integration_type}:{$this->integration_record_id}";
    }
}
