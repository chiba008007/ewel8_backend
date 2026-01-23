<?php
namespace App\Services;
use App\Models\ExamLog;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class ExamLogService
{
    public function record(array $data): ExamLog
    {
        $token = PersonalAccessToken::findToken($data['tokenExam']);
        if (!$token) {
            throw new UnauthorizedHttpException('', 'トークンが無効です。');
        }

        $exam = $token->tokenable;

        return DB::transaction(function () use ($data, $exam) {

            if ($data['status'] === ExamLog::STATUS_STARTED) {
                ExamLog::where('code', $data['code'])
                    ->where('test_id', $exam->test_id)
                    ->where('testparts_id', $data['testparts_id'])
                    ->where('exam_id', $exam->email)
                    ->where('status', ExamLog::STATUS_STARTED)
                    ->update(['status' => 0]);
            }

            return ExamLog::create([
                'code' => $data['code'],
                'test_id' => $exam->test_id,
                'testparts_id' => $data['testparts_id'],
                'exam_id' => $exam->email,
                'status' => $data['status'],
                'started_at' => $data['status'] === ExamLog::STATUS_STARTED ? now() : null,
                'finished_at' => $data['status'] === ExamLog::STATUS_FINISHED ? now() : null,
            ]);
        });
    }
}
