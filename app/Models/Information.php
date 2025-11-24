<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// displayの条件 1: 全体 2:代理店 3:顧客 4:個別
class Information extends Model
{
    use HasFactory;

    protected $table = 'informations';

    // ★ POST で受け取って代入したいカラムを指定（mass assignment 対策）
    protected $fillable = [
        'title',
        'started_at',
        'ended_at',
        'display',
        'note',
        'file',
        'status'
    ];

    protected $appends = ['display_labels'];

    public function getDisplayLabelsAttribute()
    {
        $labels = [
            1 => '全体',
            2 => '代理店',
            3 => '顧客',
            4 => '個別',
        ];

        return $labels[$this->display] ?? '';
    }


    // ★ リレーション（閲覧可能ユーザー）
    public function viewers()
    {
        return $this->belongsToMany(User::class, 'information_user')
                    ->withPivot('status')
                    ->withTimestamps();
    }

    // display=4 の個別指定を参照する
    public function informationUsers()
    {
        return $this->hasMany(InformationUser::class, 'information_id');
    }

}
