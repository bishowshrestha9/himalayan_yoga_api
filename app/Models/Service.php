<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{

    protected $fillable = [
        'title',
        'slug',
        'description',
        'yoga_type',
        'benefits',
        'class_schedule',
        'session_time',
        'instructor_id',
        'price',
        'capacity',
        'currency',
        'images',
        'is_active',
    ];

    protected $casts = [
        'benefits' => 'array',
        'class_schedule' => 'array',
        'images' => 'array',
        'is_active' => 'boolean',
    ];

    public function instructor()
    {
        return $this->belongsTo(Instructor::class);
    }

}
