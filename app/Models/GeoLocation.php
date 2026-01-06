<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeoLocation extends Model
{
   protected $fillable = [
        'user_id',
        'latitude',
        'longitude',
        'device_info',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
