<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeamTaskReport extends Model
{
    protected $fillable = ['task_id','user_id','work_summary','hours_spent'];
     public function task()
    {
        return $this->belongsTo(TeamTask::class, 'task_id');
    }

    public function user(){ return $this->belongsTo(User::class); }
}
