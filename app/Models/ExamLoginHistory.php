<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamLoginHistory extends Model
{
    use HasFactory;

    protected $table = 'exam_login_histories';

    protected $fillable = [
        'exam_id',
        'ip_address',
        'user_agent',
        'logged_in_at',
    ];

    protected $appends = [
        "partner_name",
        "customer_name",
        "test_name",
        "name",
        "email",
        "platform",
        "browser",
    ];

    // ------------------------------------------------------------------
    // Exam とのリレーション（任意）
    // ------------------------------------------------------------------
    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }
    // アクセサを定義
    public function getPartnerNameAttribute(){
        return $this->exam?->partner?->name;
    }
    public function getCustomerNameAttribute(){
        return $this->exam?->customer?->name;
    }
    public function getTestNameAttribute(){
        return $this->exam?->test?->testname;
    }
    public function getNameAttribute(){
        return $this->exam?->name;
    }
    public function getEmailAttribute(){
        return $this->exam?->email;
    }

    public function getPlatformAttribute()
    {
        $ua = $this->user_agent ?? '';
        $os = null;
        if (stripos($ua, 'Windows NT 10.0') !== false) $os = 'Windows 10/11';
        if (stripos($ua, 'Windows NT 6.3') !== false) $os = 'Windows 8.1';
        if (stripos($ua, 'Windows NT 6.1') !== false) $os = 'Windows 7';
        if (stripos($ua, 'Mac OS X') !== false) $os = 'macOS';
        if (stripos($ua, 'Android') !== false) $os = 'Android';
        if (stripos($ua, 'iPhone') !== false) $os = 'iOS';

        return $os."\n".$this->ip_address;
    }

    public function getBrowserAttribute()
    {
        $ua = $this->user_agent ?? '';

        if (preg_match('/Chrome\/([\d\.]+)/i', $ua, $m)) {
            return 'Chrome ' . $m[1];
        }
        if (preg_match('/Firefox\/([\d\.]+)/i', $ua, $m)) {
            return 'Firefox ' . $m[1];
        }
        if (preg_match('/Safari\/([\d\.]+)/i', $ua, $m) &&
            stripos($ua, 'Chrome') === false
        ) {
            return 'Safari ' . $m[1];
        }

        return 'Unknown';
    }

}
