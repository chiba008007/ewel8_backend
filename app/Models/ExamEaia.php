<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamEaia extends Model
{
    use HasFactory;

    // 使用するテーブル名を指定
    protected $table = 'exam_eaia';

    // 一括登録を許可する項目
    protected $guarded = [];

}
