<?php
namespace App\Services;

use App\Models\Test;

class TestExamMenuService
{
    /**
     * 試験メニュー一覧を取得する
     *
     * - 指定された params に紐づく Test を対象とする
     * - 有効な TestPart（status = 1）のみを結合
     * - 試験ユーザー（exam_id）ごとの受験状況を付加する
     *
     * @param int $examId
     * @param string $params
     * @return \Illuminate\Support\Collection
     */
    public function getMenuForExam(int $examId, string $params)
    {
        // Eloquent の scopeXxx は、自動的に解決されます。
        // TestモデルのscopeMenuForExamを読む
        return Test::menuForExam($examId, $params)->get();

    }
}
