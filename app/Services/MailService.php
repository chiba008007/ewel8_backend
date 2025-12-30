<?php
namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\PdfDownloadMail;

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

}
