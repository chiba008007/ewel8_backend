<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Models\Exam;

class Test extends Model
{
    use HasFactory;

    public function exams()
    {
        return $this->hasMany(Exam::class, 'test_id');
    }

    public static function getTestDetail($id)
    {
        $result = DB::table("tests")->find($id);
        return $result;
    }
    public function getTestParts($testid)
    {
        $result = DB::table("tests")
            ->select(["testpdfs.*"
            ,"users.name"
            ,"partner.element1"
            ,"partner.element2"
            ,"partner.element3"
            ,"partner.element4"
            ,"partner.element5"
            ,"partner.element6"
            ,"partner.element7"
            ,"partner.element8"
            ,"partner.element9"
            ,"partner.element10"
            ,"partner.element11"
            ,"partner.element12"
            ])
            ->join('testpdfs', 'tests.id', '=', 'testpdfs.test_id')
            ->join('users', 'tests.customer_id', '=', 'users.id')
            ->join('users as partner', 'tests.partner_id', '=', 'partner.id')
            ->where([
                ["tests.id","=",$testid],
                ["testpdfs.status","=",1],
            ])
            ->get();
        return $result;
    }

    /**
     * 試験メニュー取得用クエリ
     *
     * - 有効な TestPart のみ結合
     * - 試験ユーザーごとの進捗を付加
     */
    // Eloquent の scopeXxx は、自動的に解決されます。
    public function scopeMenuForExam($query, int $examId, string $params)
    {
        return $query
            ->select(
                'tests.*',
                'testparts.code',
                'testparts.id as testparts_id',
                'examfins.status as examstatus'
            )
            ->leftJoin('testparts', function ($join) {
                $join->on('testparts.test_id', '=', 'tests.id')
                     ->where('testparts.status', 1);
            })
            ->leftJoin('examfins', function ($join) use ($examId) {
                $join->on('examfins.testparts_id', '=', 'testparts.id')
                     ->where('examfins.exam_id', $examId);
            })
            ->where('params', $params);
    }

}
