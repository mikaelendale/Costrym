<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyProfile extends Model
{
    protected $fillable = [
        'name',
        'title',
        'company_context',
    ];

    protected $casts = [
        'title' => 'array',
        'company_context' => 'array',
    ];
    //
}
