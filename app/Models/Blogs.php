<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Blogs extends Model
{
    protected $fillable = [
        'title', 
        'subtitle',
        'description', 
        'image', 
        'excerpt', 
        'author',
        'content',
        'conclusion',
        'is_active', 
        'slug'
    ];

    protected $casts = [
        'content' => 'array',
        'is_active' => 'boolean'
    ];
}