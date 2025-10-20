# Ohanami 開発ガイド

WordPressサイト健康管理ツール「Ohanami」のローカル開発環境の使い方を説明します。

## 🚀 クイックスタート

### 1. 完全な開発環境立ち上げ
```bash
npm run dev:setup
```
このコマンドで以下が自動実行されます：
- Docker MySQLコンテナ起動
- 10秒待機（DB初期化時間）
- Node.jsサーバーをバックグラウンドで起動

### 2. APIテスト
```bash
# データベース接続確認
npm run test:health

# WordPress報告API（実データ）テスト
npm run test:report
```

### 3. 開発環境の停止
```bash
npm run dev:teardown
```

## 📋 使用可能なスクリプト

### サーバー管理
- `npm run start:dev` - Node.jsサーバーをバックグラウンドで起動
- `npm run stop:dev` - バックグラウンドサーバーを停止
- `npm run dev` - フォアグラウンドで起動（ファイル監視付き）

### Docker管理
- `npm run docker:up` - MySQLコンテナを起動
- `npm run docker:down` - MySQLコンテナを停止
- `npm run docker:logs` - MySQLログを表示

### テスト
- `npm run test:health` - データベース接続テスト
- `npm run test:report` - WordPress報告APIテスト（reporter.sample.json使用）

### 開発フロー
- `npm run dev:setup` - 完全な開発環境立ち上げ
- `npm run dev:teardown` - 完全な環境停止・クリーンアップ

## 🔧 手動操作

### 個別でのサーバー起動
```bash
# MySQLコンテナ起動
npm run docker:up

# Node.jsサーバー起動（バックグラウンド）
npm run start:dev

# サーバー状況確認
cat server.log
```

### トラブルシューティング
```bash
# サーバーログ確認
cat server.log

# MySQLログ確認
npm run docker:logs

# プロセス確認
ps aux | grep node
```

## 📡 API エンドポイント

### 健康チェック
- `GET /api/health/database` - データベース接続確認

### WordPress報告
- `POST /api/wordpress-report` - WordPressサイト情報保存
- `GET /api/reports` - 保存済みレポート一覧

### 認証
全APIで Bearer token認証が必要：
```
Authorization: Bearer dev-secret-key-change-in-production
```

## 🗄️ データベース構造

- **managers**: 管理者情報（デフォルト: タロスカイ）
- **servers**: サーバー情報（sakura + hostname + username で識別）
- **reports**: レポート実行履歴
- **wordpress_sites**: WordPressサイト基本情報
- **plugins**: プラグイン詳細情報
- **themes**: テーマ詳細情報

## 🐳 Docker環境

- **MySQL**: ポート3308
- **データベース**: ohanami_dev
- **ユーザー**: dev / password

## 📝 開発時の注意点

1. **ポートの競合**: MySQL標準ポート3306が使用中のため、3308を使用
2. **文字化け**: 日本語データはUTF-8で保存されています
3. **プロセス管理**: `server.pid` でプロセスIDを管理しています
