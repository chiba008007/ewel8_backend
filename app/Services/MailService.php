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

    public function twoFacterSend($user, $code)
    {
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


    public function sendPasswordReminder(
        $user,
        string $email,
        string $resetUrl
    ): void {
        $name = $user->person ?: $user->tanto_name;
        $signature = config('const.consts.MAIL_FOOTER');

        $subject = 'パスワード再設定のご案内';

        $body = <<<TEXT
{$name} 様

いつもWeb適性検査をご利用いただき、誠にありがとうございます。
管理画面のパスワード再設定のお申し込みを受け付けました。

お手数ですが、下記のURLにアクセスし、新しいパスワードを設定してください。

▼パスワード再設定用URL
{$resetUrl}

※このURLの有効期限は、本メール送信から24時間です。
期限を過ぎた場合は、お手数ですが再度お手続きをお願いいたします。

―――――――――――――――――――――――
本メールにお心当たりのない場合
―――――――――――――――――――――――
第三者が誤ってお客様のログインIDを入力した可能性があります。
このメールは破棄していただいて構いません。URLにアクセスしない限り、
パスワードが変更されることはありませんのでご安心ください。

ご不明な点がございましたら、下記までお問い合わせください。

※本メールは送信専用アドレスから送信しています。
ご返信いただいてもお答えできませんので、あらかじめご了承ください。

{$signature}

TEXT;

        Mail::raw($body, function ($message) use ($email, $subject) {
            $message->to($email)
                ->subject($subject)
                ->from(
                    config('mail.from.address'),
                    config('mail.from.name')
                );
        });

        Log::info('パスワード再設定メール送信', [
            'user_id' => $user->id,
        ]);
    }

}
