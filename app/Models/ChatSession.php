<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatSession extends Model
{
    protected $fillable = ['phone_number', 'step', 'temp_data', 'last_activity'];
    protected $casts = ['temp_data' => 'array', 'last_activity' => 'datetime'];
}
