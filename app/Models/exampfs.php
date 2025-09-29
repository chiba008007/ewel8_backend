<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Exam;
use Carbon\Carbon;
use App\Models\testparts;

class exampfs extends Model
{
    use HasFactory;
    protected $table = 'exampfses';

    public function testpart()
    {
        return $this->belongsTo(testparts::class, 'testparts_id', 'id');
    }

    public function exam()
    {
        return $this->belongsTo(Exam::class, 'exam_id');
    }

    // 開始日（Y-m-d）
    public function getStartDateAttribute()
    {
        return isset($this->attributes['starttime'])
            ? Carbon::parse($this->attributes['starttime'])->format('Y-m-d')
            : null;
    }
    // 開始時刻
    public function getStartTimeAttribute()
    {
        return isset($this->attributes['starttime'])
           ? Carbon::parse($this->attributes['starttime'])->format('H:i')
           : null;
    }

    // 差分（HH:MM:SS）
    public function getDurationAttribute()
    {
        $start = Carbon::parse($this->attributes['starttime']);
        $end   = Carbon::parse($this->attributes['endtime']);

        $diffInSeconds = $end->diffInSeconds($start);

        $hours   = floor($diffInSeconds / 3600);
        $minutes = floor(($diffInSeconds % 3600) / 60);
        $seconds = $diffInSeconds % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    }

}
