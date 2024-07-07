<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class userlisence extends Model
{
    use HasFactory;
    protected $fillable = ['code', 'num'];
}
