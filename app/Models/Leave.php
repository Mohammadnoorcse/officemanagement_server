<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Leave extends Model
{
     use HasFactory;

    protected $fillable = [
        'user_id',    // <--- Add this
        'from_date',
        'to_date',
        'type',
        'reason',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
