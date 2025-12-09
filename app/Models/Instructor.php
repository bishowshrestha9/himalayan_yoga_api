<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Instructor extends Model
{
    protected $fillable = [
        'name',
        'profession',
        'experience',
        'bio',
        'specialities',
        'certifications',
        'image',
    ];
    
    protected $casts = [
        'certifications' => 'array',
    ];
}
