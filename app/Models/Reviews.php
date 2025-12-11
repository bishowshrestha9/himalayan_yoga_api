<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reviews extends Model
{
    protected $fillable = ['name', 'email', 'review', 'rating', 'status', 'service_id'];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
