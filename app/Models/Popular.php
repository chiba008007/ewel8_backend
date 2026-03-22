<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Popular extends Model
{
    protected $table = 'popular';

    protected $fillable = [
        'dev1','dev2','dev3','dev4','dev5','dev6',
        'dev7','dev8','dev9','dev10','dev11','dev12'
    ];
}
