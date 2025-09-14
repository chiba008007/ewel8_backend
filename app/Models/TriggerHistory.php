<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TriggerHistory extends Model
{
    use HasFactory;
    protected $table = 'trigger_history';

    // partner_id → users.id
    public function partner()
    {
        return $this->belongsTo(User::class, 'partner_id', 'id');
    }

    // customer_id → users.id
    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id', 'id');
    }

}
