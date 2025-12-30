<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PdfOutputCronLog extends Model
{
    use HasFactory;

    protected $table = 'pdf_output_cron_logs';

    protected $fillable = [
        'partner_id',
        'customer_id',
        'test_id',
        'type',
        'total_count',
        'processed_count',
        'status',
        'file_path',
        'error_message',
    ];

    // リレーションを記載
    // パートナー
    public function partner(){
        return $this->belongsTo(User::class, 'partner_id');
    }
    // 企業
    public function customer(){
        return $this->belongsTo(User::class, 'customer_id');
    }
    // テスト
    public function test(){
        return $this->belongsTo(Test::class,'test_id');
    }

}
