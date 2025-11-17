<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    protected $fillable = [
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
    ];

    protected $casts = [
        'timestamp' => 'datetime',
        'metadata' => 'array',
        'amount' => 'decimal:2',
    ];
}
