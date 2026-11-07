<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PasswordReminder extends Model
{
    protected $table = 'password_reminders';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'token',
        'expires_at',
        'created_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
    ];
}
