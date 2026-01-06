<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Salary extends Model
{
     use HasFactory;



    // Fields that can be mass-assigned
    protected $fillable = [
        'user_id',
        'month',
        'month_in_days',
        'basic_salary',
        'total_working_days',
        'present_days',
        'late_days',
        'leave_days',
        'absent_days',
        'holiday',
        'weekend_days',
        'late_deduction_days',
        'deduction_amount',
        'total_overtime_minutes',
        'overtime_amount',
        'per_day_amount',
        'final_salary',
        'status',
    ];

    // Optional: define relationship to User
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
