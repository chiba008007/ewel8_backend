<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamBaj3 extends Model
{
    use HasFactory;

    protected $fillable = [
        'testparts_id',
        'exam_id',
        'status',
        'starttime',
    ];

    public function testpart()
    {
        return $this->belongsTo(testparts::class, 'testparts_id', 'id');
    }

    public function exam()
    {
        return $this->belongsTo(Exam::class, 'exam_id');
    }

}
