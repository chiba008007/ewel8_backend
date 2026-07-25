<?php

namespace App\Http\Controllers;

use App\Models\exampfs;
use App\Models\Test;
use App\Models\testparts;
use App\Models\User;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\DB;

class CSVEAIBController extends TestController
{
    //
    public function getEAIb(Request $request)
    {
        // testparts.codeと完全一致させる
        $code = 'EAIb';

        $testId = $request->test_id;

        try {
            // テスト情報を取得
            $test = Test::query()
                ->select(
                    'tests.testname',
                    'a.name as customername',
                    'b.name as partnername'
                )
                ->join('users as a', 'tests.user_id', '=', 'a.id')
                ->join('users as b', 'a.partner_id', '=', 'b.id')
                ->where('tests.id', $testId)
                ->first();

            // 対象テストの受検者を取得
            $exams = DB::select(
                'SELECT * FROM exams WHERE test_id = ?',
                [$testId]
            );

            // 受検者ごとに最新のEAIbデータを取得
            $eaibList = DB::select(
                "
            SELECT
                exam_eaib.*,
                DATE_FORMAT(starttime, '%Y/%m/%d') AS startdate,
                DATE_FORMAT(starttime, '%H:%i:%s') AS starttimes,
                TIMEDIFF(endtime, starttime) AS timer
            FROM exam_eaib
            WHERE id IN (
                SELECT MAX(id)
                FROM exam_eaib
                WHERE testparts_id = (
                    SELECT id
                    FROM testparts
                    WHERE test_id = ?
                      AND code = ?
                    LIMIT 1
                )
                GROUP BY exam_id
            )
            ",
                [$testId, $code]
            );

            // exam_idをキーにして検索しやすくする
            $eaibByExamId = [];

            foreach ($eaibList as $eaib) {
                $eaibByExamId[$eaib->exam_id] = $eaib;
            }

            $passwd = config('const.consts.PASSWORD');
            $result = [];

            foreach ($exams as $key => $exam) {
                // 生年月日の暗号化データを復号する
                $pwd = openssl_decrypt(
                    $exam->password,
                    'aes-256-cbc',
                    $passwd['key'],
                    0,
                    $passwd['iv']
                );

                $password = $pwd === 'password' ? '' : $pwd;

                // 年齢を算出する
                $age = '';

                if ($password) {
                    $age = floor(
                        (strtotime($exam->created_at) - strtotime($password))
                        / (60 * 60 * 24 * 365)
                    );
                }

                $result[$key] = [
                    'pwd' => $password,
                    'age' => $age,
                    'exam' => $exam,

                    // Vue側のvalue.eaibと合わせる
                    'eaib' => $eaibByExamId[$exam->id] ?? null,
                ];
            }

            return response([
                'list' => $result,
                'test' => $test,
            ], 200);
        } catch (\Throwable $e) {
            // ログに実際のエラーを残す
            report($e);

            return response([], 400);
        }
    }
}
