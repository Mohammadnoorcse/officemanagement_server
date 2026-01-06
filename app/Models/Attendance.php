<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
     protected $fillable = [
        'user_id','date','login_time','logout_time','ip','country','region','city','zip',
        'latitude','longitude','timezone','isp','organization','as','status'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function breaks()
    {
        return $this->hasMany(BreakTime::class, 'attendance_id');
    }
}
