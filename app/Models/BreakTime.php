<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BreakTime extends Model
{
    protected $table = 'breaks';
    protected $fillable = [
        'attendance_id',
        'reason',
        'start_break',
        'end_break',
        'break_minutes',
    ];

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }
}
