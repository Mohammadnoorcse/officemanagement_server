<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeamTask extends Model
{
    protected $fillable = ['title','description','assigned_by','assigned_to','due_date','status'];
    public function assignedBy() { return $this->belongsTo(User::class,'assigned_by'); }
    public function assignedTo() { return $this->belongsTo(User::class,'assigned_to'); }
}
