<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    //     // id: string
    //   userName: string
    //   userEmail: string
    //   service: string
    //   fromDate: string
    //   toDate: string
    //   time: string
    //   status: "confirmed" | "pending" | "cancelled"
    //   participants: number
    //   price: number
    // }
    protected $fillable = [
        'userName',
        'userEmail',
        'service_id', //service id foreign key
        'fromDate',
        'toDate',
        'time',
        'status',
        'participants',
        'price',
        'payment_id'
    ];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

}
