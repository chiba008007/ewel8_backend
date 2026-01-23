<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\ExamLoginHistory;
use Illuminate\Http\Request;
use App\Services\Crypto\PasswordCrypto;

class ExamAuthService
{
  private array $passwd;
  private PasswordCrypto $crypto;

  public function __construct(PasswordCrypto $crypto)
  {
      $this->passwd = config('const.consts.PASSWORD');
      $this->crypto = $crypto;
  }
   /**
     * 試験ユーザー認証処理
     * - パスワード初期化判定
     * - 認証
     * - トークン発行
     * - ログイン履歴記録
     */
  public function authenticate(Request $request): array
  {
    // 対象ユーザー取得（論理削除は除外）
    $user = Exam::where('email', $request->email)
        ->where("test_id", $request->test_id)
        ->whereNull('deleted_at')
        ->firstOrFail();

    // 初期パスワード状態の場合のみ、初回ログイン時に更新
    $this->resetPasswordIfDefault($user, $request->password);

    // パスワード検証
    if (!$this->crypto->equals($user->password, $request->password)) {
      throw new \RuntimeException('Invalid password');
    }

    $token = $user->createToken('my-app-token')->plainTextToken;
    /**
     * ログイン履歴を記録する
     * - 監査・不正アクセス調査用
     * - 認証ロジックとは独立した副作用処理
     */
    ExamLoginHistory::create([
        'exam_id'      => $user->id,
        'ip_address'   => request()->ip(),
        'user_agent'   => request()->userAgent(),
        'logged_in_at' => now(),
    ]);

    return compact('user', 'token');
  }

  /**
   * 初期パスワード判定
   * - "password" または空文字は初期状態とみなす
   * - 初回ログイン時のみ更新する
   */
  private function resetPasswordIfDefault(Exam $user, string $newPassword): void
  {

      $current = $this->crypto->decrypt($user->password);
      if ($current === 'password' || $current === '') {
          $user->password = $this->crypto->encrypt($newPassword);

          $user->save();
      }
  }

}
