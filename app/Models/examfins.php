<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class examfins extends Model
{
    use HasFactory;
    protected $fillable = [
        'exam_id',
        'testparts_id',
        'status',
    ];
    public static function complete(int $examId, int $testpartsId): self
    {
        $exists = self::where('exam_id', $examId)
            ->where('testparts_id', $testpartsId)
            ->exists();

        if ($exists) {
            throw new \Exception('既に登録されています');
        }

        return self::create([
            'exam_id' => $examId,
            'testparts_id' => $testpartsId,
            'status' => 1,
        ]);
    }
}
