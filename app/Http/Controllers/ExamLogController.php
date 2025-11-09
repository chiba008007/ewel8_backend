<?php

namespace App\Http\Controllers;

use App\Models\ExamLog;
use App\Http\Requests\ExamLogRequest;
use Laravel\Sanctum\PersonalAccessToken;

class ExamLogController extends Controller
{
    //
    public function set(ExamLogRequest $request)
    {
        // データの登録
        // バリデートはexample-app/app/Http/Requests/ExamLogRequest.phpに準備
        // バリデーション済データ
        $validated = $request->validated();
        $token = PersonalAccessToken::findToken($validated['tokenExam']);
        if (!$token) {
            return response()->json([
                'result' => false,
                'message' => 'トークンが無効です。',
            ], 401);
        }
        // tokenable は App\Models\Exam のインスタンス
        $exam = $token->tokenable;

        // 同じ条件の既存レコードを完了扱い(status=0)に更新
        if ($validated['status'] == 1) {
            ExamLog::where('code', $validated['code'])
                ->where('test_id', $exam->test_id)
                ->where('testparts_id', $validated['testparts_id'])
                ->where('exam_id', $exam->email)
                ->where('status', 1)
                ->update(['status' => 0]);
        }
        // データ登録
        $data = [
            'code' => $validated['code'],
            'test_id' => $exam->test_id,
            'testparts_id' => $validated['testparts_id'],
            'exam_id' => $exam->email,
            'status' => $validated['status'],
        ];

        // ステータスごとに日時を追加
        if ($validated['status'] == 1) {
            $data['started_at'] = now();
        } elseif ($validated['status'] == 2) {
            $data['finished_at'] = now();
        }

        $examLog = ExamLog::create($data);

        return response()->json([
           'result' => true,
           'message' => 'get lisence successfully',
           'data' =>  $examLog,
        ]);

    }
}
