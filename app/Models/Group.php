<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
     protected $fillable = ['name','created_by'];

    public function members()
    {
        return $this->belongsToMany(User::class,'group_user','group_id','user_id');
    }

    public function messages()
    {
        return $this->hasMany(ChatMessage::class,'chat_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class,'created_by');
    }
}
