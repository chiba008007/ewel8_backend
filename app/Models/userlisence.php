<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class userlisence extends Model
{
    use HasFactory;
    protected $fillable = ['code', 'num'];
    protected $table = 'userlisences';

    public function examLogs()
    {
        return $this->hasMany(ExamLog::class, 'code', 'code');
    }

    public function triggerHistories(): HasMany
    {
        return $this->hasMany(TriggerHistory::class, 'testtype', 'code')
                    ->where('type', 'customer');
    }

}
