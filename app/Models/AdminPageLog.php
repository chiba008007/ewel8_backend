<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminPageLog extends Model
{
    use HasFactory;
    protected $table = 'admin_page_logs';

    protected $fillable = [
        'user_id',
        'route_name',
        'title',
        'path',
        'params',
        'ip',
        'user_agent',
    ];

    protected $casts = [
        'params' => 'array',
    ];

    // リレーション
    public function user(){
        return $this->belongsTo(User::class);
    }
}
