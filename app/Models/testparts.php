<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\exampfs;

class testparts extends Model
{
    use HasFactory;

    public function exampfs()
    {
        return $this->hasMany(exampfs::class, 'testparts_id', 'id');
    }

}
