<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BillList extends Model
{
    use HasFactory;

    /**
     * テーブル名
     */
    protected $table = 'bills_list';

    /**
     * 主キー
     */
    protected $primaryKey = 'id';

    /**
     * タイムスタンプカラムを自動管理しない
     */
    public $timestamps = false;

    /**
     * 複数代入を許可するカラム
     */
    protected $fillable = [
        'bill_id',
        'number',
        'title',
        'name',
        'kikaku',
        'quantity',
        'unit',
        'money',
        'create_ts',
        'update_ts',
    ];

    /**
     * 請求書（bills）とのリレーション
     * BillList : Bill = 多 : 1
     */
    public function bill()
    {
        return $this->belongsTo(Bill::class, 'bill_id');
    }

}
