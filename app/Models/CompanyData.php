<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyData extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
    ];
    //
}
