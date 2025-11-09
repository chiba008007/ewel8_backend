<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamLog extends Model
{
    use HasFactory;

    protected $fillable = [
    'code',
    'test_id',
    'testparts_id',
    'exam_id',
    'status',
    'started_at',
    'finished_at',
    ];

}
