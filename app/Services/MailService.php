<?php
namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\PdfDownloadMail;
use App\Mail\TwoFactorMail;

class MailService
{
  public function sendCompleteMail($user, $test, $uploadFileMail)
  {

    // メール配信
    Log::info('PDF一括ダウンロードファイル生成完了のお知らせ:'.$user->tanto_name);

    $mailbody = [];
    $mailbody[ 'title' ] = "PDF一括ダウンロードファイル生成完了のお知らせ";
    $mailbody[ 'name' ] = $user->name;
    $mailbody[ 'person' ] = $user->tanto_name;
    $mailbody[ 'testname' ] = $test->testname;
    $mailbody[ 'uploadFileMail' ] = $uploadFileMail;
    Mail::to($user->tanto_address)
        ->send(
            (new PdfDownloadMail($mailbody))
            ->from(config('mail.from.address'), config('mail.from.name'))
        );

    //標準出力&ログに出力するメッセージのフォーマット
    $message = '[' . date('Y-m-d h:i:s') . ']PDF Download end';
    //INFOレベルでメッセージを出力
    Log::info('INFOレベルでメッセージを出力 : '.$message);
  }

  public function twoFacterSend($user,$code){
    // 2段階認証配信
    Log::info('2段階認証メール配信:'.$user->tanto_name);
    $mailbody = [];
    $mailbody[ 'title' ] = "2段階認証メール配信のお知らせ";
    $mailbody[ 'name' ] = $user->name;
    $mailbody[ 'person' ] = $user->person;
    $mailbody[ 'code' ] = $code;
    $tanto_address = $user->person_address;
    Mail::to($tanto_address)
        ->send(
            (new TwoFactorMail($mailbody))
            ->from(config('mail.from.address'), config('mail.from.name'))
        );

    //標準出力&ログに出力するメッセージのフォーマット
    $message = '[' . date('Y-m-d h:i:s') . ']';
    //INFOレベルでメッセージを出力
    Log::info('INFOレベルでメッセージを出力 : '.$message);

  }
}
