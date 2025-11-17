<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    protected $fillable = [
        'name',
        'provider',
        'account_id',
        'txn_id',
        'timestamp',
        'amount',
        'currency',
        'merchant',
        'raw_description',
        'metadata',
        'type',
        'tags',
        'category_model_id',
    ];

    protected $casts = [
        'timestamp' => 'datetime',
        'metadata' => 'array',
        'amount' => 'decimal:2',
        'tags' => 'array',
    ];

    public function category()
    {
        return $this->belongsTo(CategoryModel::class, 'category_model_id');
    }
}
