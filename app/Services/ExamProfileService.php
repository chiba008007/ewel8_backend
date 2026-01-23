<?php
namespace App\Services;

use App\Models\Exam;
use Illuminate\Support\Facades\DB;

class ExamProfileService
{
    /**
     * 試験ユーザーのプロフィール情報を更新する
     *
     * - 同一メールアドレス
     * - 同一 param（k）
     * に紐づく Exam を一括更新する
     */
    public function updateProfile(
        int $userId,
        string $email,
        string $param,
        array $params
    ): void {
        DB::transaction(function () use ($email, $param, $params) {

            // 更新対象の Exam ID を取得
            $examIds = Exam::where('param', $param)
                ->where('email', $email)
                ->pluck('id');

            if ($examIds->isEmpty()) {
                // 想定外ケース（更新対象なし）
                throw new \RuntimeException('Exam not found');
            }

            // 対象 Exam を一括更新
            Exam::whereIn('id', $examIds)->update($params);
        });
    }
}
