<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
      protected $fillable = [
        'user_id',
        'name',
        'date',
        'day_of_week',
        'start_time',
        'end_time',
        'status',
    ];
    // Relationship with User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function attendances() {
        return $this->hasMany(Attendance::class);
    }

}
