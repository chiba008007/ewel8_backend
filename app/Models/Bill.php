<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class Bill extends Model
{
    use HasFactory;

    /**
     * テーブル名
     */
    protected $table = 'bills';

    /**
     * 主キー
     */
    protected $primaryKey = 'id';

    /**
     * タイムスタンプカラムを自動管理しない
     * （create_ts, update_ts を独自に使うため）
     */
    public $timestamps = false;

    /**
     * 複数代入を許可するカラム
     */
    protected $fillable = [
        'post',
        'address_1',
        'address_2',
        'company_name',
        'busyo',
        'yakusyoku',
        'name',
        'money',
        'title',
        'pay_date',
        'pay_bank',
        'pay_number',
        'pay_name',
        'bill_number',
        'bill_date',
        'from_post',
        'from_address_1',
        'from_address_2',
        'from_name',
        'from_tel',
        'company_print_flag',
        'tanto_print_flag',
        'note',
        'create_ts',
        'update_ts',
    ];

    /**
     * 日付カラムのフォーマット化
     *
     * @var array
     */
    protected $casts = [
        'bill_date' => 'date',
        'pay_date'  => 'date',
    ];

    /**
     * 明細（bills_list）とのリレーション
     * Bill : BillList = 1 : 多
     */
    public function lists()
    {
        return $this->hasMany(BillList::class, 'bill_id');
    }


    /**
     * bill_number 自動採番
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($bill) {

            if (empty($bill->bill_number)) {
                $bill->bill_number = self::generateBillNumber();
            }

            // bill_date が未指定なら自動で今日の日付を設定
            if (empty($bill->bill_date)) {
                $bill->bill_date = now()->format('Y-m-d');
            }
        });
    }

    /**
     * 連番の bill_number を生成する
     */
    public static function generateBillNumber()
    {
        // 日付部分（年2桁+月+日）
        $prefix = 's' . now()->format('ymd');

        // 当日の既存番号の最大値を取得
        $latest = DB::table('bills')
            ->where('bill_number', 'like', $prefix . '%')
            ->orderByRaw('CAST(SUBSTRING(bill_number, 8, 2) AS UNSIGNED) DESC')
            ->value('bill_number');

        if ($latest) {
            // 最後の2桁（連番）を取り出して +1
            $number = intval(substr($latest, -2)) + 1;
        } else {
            $number = 1;
        }

        // 2桁ゼロ埋め
        return $prefix . str_pad($number, 2, '0', STR_PAD_LEFT);
    }

    // 支払日日付のフォーマット化
    public function getPayDateFormattedAttribute()
    {
        return $this->pay_date
            ? Carbon::parse($this->pay_date)->format('Y年m月d日')
            : null;
    }

}
