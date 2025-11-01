# 勤怠 アプリケーション環境構築・操作手順

このリポジトリは **Docker + Laravel 10 + MySQL + Fortify** 環境で動作する Web アプリです。
ここでは **環境構築・テスト・メール認証** を含めて順を追って説明します。
このアプリでは **新規登録後のメール認証** を MailHog を使って確認できます。
認証が完了するとプロフィール登録画面に遷移します。

---

## 1. リポジトリのクローン

```bash
git clone git@github.com:ando625/kintai.git
cd kintai
```

---

## 2. Docker 環境起動

Docker Desktop を起動後、以下を実行：

```bash
docker compose up -d --build
```

コンテナの確認：

```bash
docker compose ps
```

`docker-compose.yml` にすでに MailHog が定義されています。
そのため、以下のコマンドで MailHog も自動で立ち上がります。

```
php         Up
mysql       Up
phpmyadmin  Up
mailhog     Up
```


---

## 3. Laravel 環境構築

### 3-1. PHP コンテナに入る

```bash
docker compose exec php bash
```

### 3-2. Composer で依存関係をインストール

```bash
composer install
```

- Fortify もこのときに自動でインストールされます。手動で入れる必要はありません。




### 3-3. `.env` ファイル作成

```bash
cp .env.example .env
```

.env に以下を設定：

```
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel_db
DB_USERNAME=laravel_user
DB_PASSWORD=laravel_pass
```



## メール認証機能（MailHog使用）

- 本アプリでは 新規会員登録時および初回ログイン時にメール認証 を行います。
- メール送信のテストは MailHog を使用します。




### 確認 ブラウザで MailHog の管理画面にアクセス

- http://localhost:8025

1. SMTPサーバー: localhost:1025
2. Web UI: http://localhost:8025   → 送信された認証メールを確認可能


### Laravel 側の設定（.env）

```
MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="example@furiapp.test"
MAIL_FROM_NAME="${APP_NAME}"
```

- 新規登録後、誘導画面の「認証はこちらから」ボタンを押すと押すと MailHog が開き、届いたメール内のリンクをクリックして認証を完了させる、そしてプロフィール画面へ遷移します。
- 新規登録後メール認証をせずにログインした場合、メール認証画面に飛びます、そしてプロフィール登録していただきます。

### メール認証の流れ

1.	新規ユーザーを登録（メールアドレス・パスワードを入力）
2.	登録完了後、メール認証誘導画面に遷移
3.	誘導画面で「認証はこちらから」ボタンをクリックすると、MailHog が開きます。
4.	MailHog で届いた認証メールの中の「メールアドレスはこちら」or 「Verify Email Address」リンク（ボタン）をクリックして初めてメール認証が完了し、プロフィール設定画面に遷移します。
5. 認証完了 → プロフィール設定画面に遷移



### 3-4. アプリケーションキー生成

```bash
php artisan key:generate
```

### 3-5. データベース準備（データベースの初期化・開発用）

開発環境で事前にダミーデータを入れるので以下を実行してください：

```bash
php artisan migrate:fresh --seed
```


---

## 4. テスト用データベースの準備

### ⚠️ 注意
本番データベースをテストで使うのは非常に危険です。
安全にテストを実行するために、**テスト専用データベース（`demo_test`）** を作成します。

---

### MySQLコンテナに入る

まず MySQL コンテナに接続します。
mysqlにcdで移動して↓

```bash
docker compose exec mysql bash
```

---

### MySQL に root ユーザーでログイン

```bash
mysql -u root -p
```

パスワードは `docker-compose.yml` の中にある

```yaml
MYSQL_ROOT_PASSWORD: root
```

で設定した `root` を入力します。

---

### テスト用データベースを作成

MySQL にログインできたら、以下を実行：

```sql
CREATE DATABASE demo_test;
SHOW DATABASES;
```

`demo_test` が一覧に表示されればOKです

---

### database.php の設定確認

`config/database.php` に以下のような **「mysql_test」設定** が追加されていることを確認してください。

（このプロジェクトではすでに設定済みです。追記する必要はありません）

```php
'mysql_test' => [
    'driver' => 'mysql',
    'url' => env('DATABASE_URL'),
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', '3306'),
    'database' => 'demo_test',
    'username' => 'root',
    'password' => 'root',
    'unix_socket' => env('DB_SOCKET', ''),
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
    'prefix_indexes' => true,
    'strict' => true,
    'engine' => null,
],
```


その後退出
```
exit
```
---

### `.env.testing` の作成

cdでphpに移動し、
PHP コンテナに入って、`.env` をコピーして `.env.testing` を作成します。

```bash
docker compose exec php bash
cp .env .env.testing
```

`.env.testing` を開いて、上部とDB接続部分を以下のように編集します。

```dotenv
APP_NAME=Laravel
APP_ENV=test
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql_test
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=demo_test
DB_USERNAME=root
DB_PASSWORD=root
```

✅ `APP_ENV` は `test` に変更


✅ `APP_KEY` は一旦空欄にしておきます


✅ `DB` も`demo_test`と`roo`に設定します

---

### テスト用アプリキーを生成
そして、先ほど「空」にしたAPP_KEYに新たなテスト用のアプリケーションキーを加えるために以下のコマンドを実行します

```bash
php artisan key:generate --env=testing
```

その後、キャッシュをクリアして反映：

```bash
php artisan config:clear
```

---

### テスト用マイグレーション実行

```bash
php artisan migrate --env=testing
```

これで `demo_test` にテーブルが作成されます

---

### PHPUnit の設定確認

このプロジェクトには、すでに **テスト環境用の設定済み `phpunit.xml`** が用意されています。
特に編集は不要です。内容を確認して、下記のように設定されていることを確認してください。

```xml
<php>
    <server name="APP_ENV" value="testing"/>
    <server name="BCRYPT_ROUNDS" value="4"/>
    <server name="CACHE_DRIVER" value="array"/>
    <server name="DB_CONNECTION" value="mysql_test"/>
    <server name="DB_DATABASE" value="demo_test"/>
    <server name="MAIL_MAILER" value="array"/>
    <server name="QUEUE_CONNECTION" value="sync"/>
    <server name="SESSION_DRIVER" value="array"/>
    <server name="TELESCOPE_ENABLED" value="false"/>
</php>
```

✅ `DB_CONNECTION="mysql_test"`
✅ `DB_DATABASE="demo_test"`


 この設定により、テスト実行時は  
- 環境：`testing`  
- 接続先DB：`mysql_test`  
- 使用DB名：`demo_test`  
が自動的に選ばれます。

---

### 設定確認コマンド

もし設定が正しく反映されているか不安な場合は、  
以下のコマンドで `.env.testing` と `phpunit.xml` の内容を確認できます。

```bash
docker compose exec php bash
cat .env.testing | grep DB_
grep DB_ phpunit.xml
```

結果が以下のようになっていればOKです

```
DB_CONNECTION=mysql_test
DB_DATABASE=demo_test
```
---

### これでテスト用DB環境の準備完了！

今後は以下のコマンドでテストを実行できます。

```bash
php artisan test
```



---
## トラブルシューティング（MailHogエラー）

他プロジェクトでMailHogコンテナが残っている場合、以下のようなエラーが出ることがあります。
その場合は、既存のMailHogを削除してから再度起動してください。

```bash
docker rm -f mailhog
docker compose up -d --build
```


---
## 5. テーブル




---
## 6. ED図

![ER図](./docs/ED.png)

---

## ７. phpMyAdmin

- URL: `http://localhost:8080/`
- ユーザー名・パスワードは `.env` と同じ
- DB: `laravel_db` を確認可能

---

## 8. 注意事項

- MySQL データは `.gitignore` により Git には含めない
- Mac M1/M2 ARM 環境では MySQL と phpMyAdmin に `platform: linux/amd64` が指定済み
- PHP 8.1 以降、`mbstring.internal_encoding` は廃止されているため、警告が出たらコメントアウト

---

## 9. テストユーザー

### 管理者ユーザー


### 一般ユーザー




---

## １０. 開発環境 URL

- 開発環境: http://localhost/
- phpMyAdmin: http://localhost:8080/

---
