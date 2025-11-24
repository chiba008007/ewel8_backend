<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InformationUser extends Model
{
    use HasFactory;
    protected $table = 'information_user';

    public function information()
    {
        return $this->belongsTo(Information::class, 'information_id');
    }
}
