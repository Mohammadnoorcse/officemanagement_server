<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{

     use HasFactory;
     

    protected $fillable = [
        'name',        // Holiday name
        'start_date',  // Start of holiday
        'end_date',    // End of holiday
        'description', // Optional description
    ];
}
