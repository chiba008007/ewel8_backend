<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class pdfDownloads extends Model
{
    use HasFactory;
    protected $table = 'pdfDownloads';

    // typeの用途
    // 1: "実行前",
    // 2: "実行中",
    // 3: "実行後",

    protected $fillable = [
    'partner_id',
    'customer_id',
    'test_id',
    'admin_id',
    'type',
    'code',
    ];

}
