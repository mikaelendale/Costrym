<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoryModel extends Model
{
    protected $fillable = [
        'name',
        'tags',
        'category',
        'meta_data',
    ];

    protected $casts = [
        'tags' => 'array',
        'meta_data' => 'array',
    ];
    //
}
