<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
       protected $fillable = [
        'user_id',
        'name',       // matches migration
        'date',
        'start_time',
        'end_time',
        'status',     // optional if you want to allow mass update
    ];

    // Relationship with User
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
