<?php
namespace App\Services\Crypto;

class PasswordCrypto
{
    private array $config;

    public function __construct()
    {
        /**
         * ※ 業務要件により可逆暗号を使用
         * - 問い合わせ時に登録済みパスワードを伝える必要がある
         * - Hash 化は将来対応予定
         */
        $this->config = config('const.consts.PASSWORD');
    }

    /**
     * パスワード暗号化（可逆）
     */
    public function encrypt(string $plain): string
    {
        return openssl_encrypt(
            $plain,
            'aes-256-cbc',
            $this->config['key'],
            0,
            $this->config['iv']
        );
    }

    /**
     * パスワード復号
     */
    public function decrypt(string $encrypted): string
    {
        return openssl_decrypt(
            $encrypted,
            'aes-256-cbc',
            $this->config['key'],
            0,
            $this->config['iv']
        );
    }

    /**
     * パスワード一致判定
     * ※ Hash 化時は実装差し替え予定
     */
    public function equals(string $encrypted, string $plain): bool
    {
        return $this->decrypt($encrypted) === $plain;
    }
}
