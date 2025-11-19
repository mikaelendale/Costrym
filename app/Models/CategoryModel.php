<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoryModel extends Model
{
    protected $fillable = [
        'name',
        'meta_data',
        'description',
        'expenses',
    ];

    protected $casts = [
        'meta_data' => 'array',
        'expenses' => 'array',
    ];
}
