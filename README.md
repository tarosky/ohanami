<div><img src="./docs/images/main.webp" /></div>
<div align="center">さくらのレンタルサーバーにインストールしたWordPressの情報を記録するツール</div>

# ohanami

WordPress環境の横断的監視・管理ツール

## 🚀 クイックスタート

### ワンライナー実行（本番環境）
```bash
curl -L https://raw.githubusercontent.com/tarosky/ohanami/main/ohanami.php | php
```

### SSH経由開発テスト（開発環境）
```bash
# 設定ファイル作成
cp deploy-config.json.sample deploy-config.json
# 設定を編集後、テスト実行
./deploy-test.sh
```

## 📋 目的

レンラルサーバーなど複数の環境で動作するWordPressを管理している場合、それらを横断的に管理する必要があります。

- プラグインの脆弱性が報告されたとき、そのプラグインがインストールされているすべてのサイトでプラグインを更新する。
- 標準化目的で特定の目的（例・サイトマップ生成）をサポートするプラグインをプラグインAからプラグインBに変更したい。
- ライセンスを管理するためにプラグインを利用している数を調べたい。

これらを横断的かつレンタルサーバーという制約のある環境で動作させるために、以下の機能が必要になります。

## 🏗 アーキテクチャ

### 1. 報告機能
- **実行方式**: ワンライナーでのPharアーカイブ実行
- **情報収集**: wp-cli活用でWordPressコア・プラグイン・テーマ情報を取得
- **環境情報**: PHP、MySQL、サーバー環境の詳細を収集

### 2. 保存機能
- **エンドポイント**: Google Cloud Run上のNode.js API
- **データベース**: Cloud SQL (MySQL)
- **通知**: Slack連携

### 3. 集計機能
- **Phase 1**: Looker Studio（基本統計・レポート）
- **Phase 2**: カスタムダッシュボード（高度な分析・顧客データ連携）

## 🔧 開発環境

### プロジェクト構成
```
├── ohanami.php              # ワンライナー実行用ダウンローダー
├── deploy-test.sh           # SSH経由開発テストスクリプト  
├── deploy-config.json       # SSH設定ファイル（gitignore対象）
├── reporter/                # 情報収集ツール本体
│   ├── src/                 # モジュラーソースコード
│   ├── composer.json        # 依存関係管理
│   ├── box.json            # Pharビルド設定
│   └── ohanami.phar        # ビルド済み実行ファイル
└── src/                    # Google Cloud Run API
```

### 開発フロー

#### 1. ローカル開発
```bash
# ソースコード編集
cd reporter/src

# ローカルテスト
cd reporter && php src/main.php

# Pharビルド
cd reporter && ./vendor/bin/box compile
```

#### 2. SSH経由テスト
```bash
# 設定ファイル準備
cp deploy-config.json.sample deploy-config.json
# SSH設定を編集

# 自動デプロイ・実行・結果取得
./deploy-test.sh [サーバー名]
```

#### 3. 本番デプロイ
```bash
# プルリクエストでレビュー
git checkout -b feature/new-feature
git commit -m "feat: 新機能追加"
git push origin feature/new-feature

# マージ後、ワンライナーで利用可能
```

## 📁 各ディレクトリの役割

- `docs/` 文書を保管するファイル
- `reporter/` 情報収集ツール（Box + Phar）
- `src/` Google Cloud Runにデプロイされるエンドポイント

## ⚡ 実行例

### ローカル環境
```
=== Ohanami Reporter (Prototype) ===
実行日時: 2025-10-17 13:03:10
実行場所: /Users/guy/Documents/GitHub/ohanami

=== サーバー情報 ===
PHP_VERSION    : 8.2.29
PHP_SAPI       : cli
OS             : Darwin
MYSQL_VERSION  : mysql Ver 14.14 Distrib 5.7.44
WPCLI_VERSION  : N/A
```

### さくらレンタルサーバー
```
=== Ohanami Reporter (Prototype) ===
実行日時: 2025-10-17 22:10:59
実行場所: /home/fumiki

=== サーバー情報 ===
PHP_VERSION    : 8.2.20
PHP_SAPI       : cli
OS             : FreeBSD
MYSQL_VERSION  : 8.0.35
WPCLI_VERSION  : 2.12.0
```

## 🛠 インフラ

インフラはGoogle Cloudを利用する。プロジェクトはtarosky-web。リソースにはタグとしてohanamiをつける。

## 📈 ロードマップ

### Phase 1 (完了)
- [x] Box + Pharビルドシステム
- [x] プロトタイプ版サーバー情報収集
- [x] ワンライナー実行
- [x] SSH経由開発フロー

### Phase 2 (予定)
- [ ] WordPress詳細情報収集（wp-cli活用）
- [ ] Cloud SQL連携
- [ ] Looker Studioダッシュボード

### Phase 3 (将来)
- [ ] 顧客データベース連携
- [ ] カスタムダッシュボード
- [ ] 脆弱性アラート機能
