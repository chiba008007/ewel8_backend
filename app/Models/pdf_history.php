<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class pdf_history extends Model
{
    use HasFactory;
    protected $table = 'pdf_history';
    protected $fillable = ['test_id', 'exam_id'];


    // Examとのリレーション
    public function exam()
    {
        return $this->belongsTo(Exam::class, 'exam_id');
    }

}
