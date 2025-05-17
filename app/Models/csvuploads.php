<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class csvuploads extends Model
{
    use HasFactory;

    protected $fillable = [
        'test_id',
        'customer_id',
        'filename',
        'filepath',
        'type',
        'total',
        'notrows',
        'memo',
        'status'
    ];

}
