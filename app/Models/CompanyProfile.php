<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyProfile extends Model
{
    protected $fillable = [
        'name',
        'company_context',
    ];

    public $casts = [
        'company_context' => 'array',
    ];
    //
}
