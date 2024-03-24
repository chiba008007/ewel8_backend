php artisan serve


### mysql実行
chiba@chiba:~/ewel8_backend/example-app$ sudo service mysql start
- 接続
chiba@chiba:~/ewel8_backend/example-app$ sudo mysql -u root

- 接続 パスワード設定後
chiba@chiba:~/ewel8_backend/example-app$ sudo mysql -u root -proot

- mysql接続失敗してmigrateが動かなかったとき
参考
https://supersoftware.jp/tech/20230901/18991/
- MySQL の認証プラグインを見直す
- mysql> SELECT user, host, plugin FROM mysql.user;
確認すると root ユーザーではauth_socketが適用されていることが分かりました。
auth_socketを使用すると、OS のユーザー名と同じユーザー名の MySQL アカウントはパスワードを入力せずにログインできるようになります。
しかし今回は、root ユーザーで DB に接続しようとしていたので、そのため受けつけてもらえなかったようです。

下記のコマンドでcaching_sha2_passwordを使用するように変更しました。

mysql> ALTER USER 'root'@'localhost' IDENTIFIED WITH 'caching_sha2_password';

```
ERROR 1820 (HY000): You must reset your password using ALTER USER statement before executing this statement.
```
mysql -u root -p
 ALTER USER 'root'@'localhost' IDENTIFIED BY '新パスワード';