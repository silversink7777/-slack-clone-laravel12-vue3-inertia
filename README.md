# Slack Clone

Laravel + Vue.js + Inertia.jsで構築されたSlackクローンアプリケーション

## 機能

- リアルタイムチャット
- チャンネル管理
- ユーザー招待システム
- オンライン状態表示
- 管理者ダッシュボード

## 技術スタック

- **バックエンド**: Laravel 12, PHP 8.2+
- **フロントエンド**: Vue.js 3, Inertia.js, Tailwind CSS
- **データベース**: MySQL
- **認証**: Laravel Jetstream

## セットアップ

### 必要条件
- PHP 8.2以上
- Composer
- Node.js 18以上
- MySQL

### インストール

```bash
# リポジトリをクローン
git clone <repository-url>
cd slack-clone

# 依存関係をインストール
composer install
npm install

# 環境設定
cp .env.example .env
php artisan key:generate

# データベース設定（.envファイルを編集）
# DB_DATABASE=slack_clone
# DB_USERNAME=your_username
# DB_PASSWORD=your_password

# マイグレーション実行
php artisan migrate

# アセットビルド
npm run dev

# サーバー起動
php artisan serve
```

## 使用方法

1. ユーザー登録・ログイン
2. チャンネルを作成または参加
3. メッセージを送信
4. 他のユーザーを招待

## 開発


# 管理画面
http://127.0.0.1:8000/admin/login

## ライセンス

MIT License
