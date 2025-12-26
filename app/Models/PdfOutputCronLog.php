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

}
