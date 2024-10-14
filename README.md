php artisan serve

### mysql 実行

chiba@chiba:~/ewel8_backend/example-app$ sudo service mysql start

-   接続
    chiba@chiba:~/ewel8_backend/example-app$ sudo mysql -u root

-   接続 パスワード設定後
    chiba@chiba:~/ewel8_backend/example-app$ sudo mysql -u root -proot

-   mysql 接続失敗して migrate が動かなかったとき
    参考
    https://supersoftware.jp/tech/20230901/18991/
-   MySQL の認証プラグインを見直す
-   mysql> SELECT user, host, plugin FROM mysql.user;
    確認すると root ユーザーでは auth_socket が適用されていることが分かりました。
    auth_socket を使用すると、OS のユーザー名と同じユーザー名の MySQL アカウントはパスワードを入力せずにログインできるようになります。
    しかし今回は、root ユーザーで DB に接続しようとしていたので、そのため受けつけてもらえなかったようです。

下記のコマンドで caching_sha2_password を使用するように変更しました。

mysql> ALTER USER 'root'@'localhost' IDENTIFIED WITH 'caching_sha2_password';

```
ERROR 1820 (HY000): You must reset your password using ALTER USER statement before executing this statement.
```

mysql -u root -p
ALTER USER 'root'@'localhost' IDENTIFIED BY '新パスワード';

■cors の問題になった場合の解消
cors.php を編集

```
 return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => ['https://smp.uh-oh.jp'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true, // ここを追加

];
```

    protected $except = [
        //
        'api/*',
    ];

```
VerifyCsrfToken.php



```

php artisan config:cache
