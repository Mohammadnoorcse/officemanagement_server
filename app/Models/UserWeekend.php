<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserWeekend extends Model
{
    protected $fillable = ['user_id', 'day'];
    
     public function user()
    {
        return $this->belongsTo(User::class);
    }

}
