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
    // statusの用途
    // 0: "無効",
    // 1: "テストから登録",
    // 2: "管理者から登録",

    protected $fillable = [
    'partner_id',
    'customer_id',
    'test_id',
    'admin_id',
    'type',
    'code',
    'status',
    'admin_cronfile_path',
    ];

    protected $casts = [
        'admin_cronfile_path' => 'array',
    ];

    public function partner()
    {
        return $this->belongsTo(User::class, 'partner_id');
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function test()
    {
        return $this->belongsTo(Test::class, 'test_id');
    }

}
