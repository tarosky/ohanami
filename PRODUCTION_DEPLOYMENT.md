# Ohanami プロダクション環境デプロイ手順

WordPress健康管理ツールのプロダクション環境への展開手順です。

## 📋 事前確認

### 完了済みインフラ
✅ **Cloud SQL**: `ohanami-prod` (34.85.84.60)
✅ **データベース**: `ohanami` with 6 tables  
✅ **ユーザー**: `ohanami_user`
✅ **認証**: Bearer token システム

## 🚀 デプロイ手順

### 1. プロダクション環境設定

```bash
# 環境設定ファイル作成
cp .env.production.template .env.production

# 必須項目を編集
vim .env.production
```

**必須設定項目**:
```bash
DB_PASSWORD=ohanami-secure-2025  # 実際のパスワード
AUTH_SECRET=your-strong-production-key
SLACK_WEBHOOK_URL=https://hooks.slack.com/services/YOUR/WEBHOOK
```

### 2. Cloud Runデプロイ

```bash
# プロジェクト設定確認
gcloud config set project taroskyweb

# Cloud Runにデプロイ
gcloud run deploy ohanami \
  --source=. \
  --region=asia-northeast1 \
  --cpu=1 \
  --memory=1Gi \
  --min-instances=0 \
  --max-instances=10 \
  --port=8080 \
  --env-vars-file=.env.production \
  --allow-unauthenticated \
  --execution-environment=gen2
```

### 3. カスタムドメイン設定（オプション）

```bash
# カスタムドメイン登録
gcloud run domain-mappings create \
  --service=ohanami \
  --domain=ohanami.yourdomain.com \
  --region=asia-northeast1
```

### 4. 動作確認

デプロイ完了後のURL例: `https://ohanami-xxx-an.a.run.app`

```bash
# ヘルスチェック
curl -H "Authorization: Bearer your-production-secret" \
  https://ohanami-xxx-an.a.run.app/api/health/database

# 期待されるレスポンス
{"success":true,"message":"データベース接続OK","database":"MySQL"}
```

## 🔧 Reporter設定更新

### サーバー側設定ファイル

各WordPressサーバーで環境変数を設定：

```bash
# .bashrc または .zshrc に追加
export OHANAMI_ENDPOINT="https://ohanami-xxx-an.a.run.app/api/wordpress-report"
export OHANAMI_AUTH_TOKEN="your-production-secret-key"

# 設定反映
source ~/.bashrc
```

### Cronによる定期実行設定

```bash
# crontabを編集
crontab -e

# 毎日午前2時に実行（例）
0 2 * * * /usr/local/bin/php /path/to/ohanami/reporter/ohanami.php >/dev/null 2>&1
```

## 📊 監視・アラート設定

### Slack通知設定

1. Slack Appを作成
2. Incoming Webhookを有効化  
3. Webhook URLを`.env.production`に設定

### Cloud Monitoring設定

```bash
# Cloud Runメトリクス監視
gcloud alpha monitoring policies create \
  --policy-from-file=monitoring-policy.yaml
```

## 🔒 セキュリティ設定

### Cloud SQLセキュリティ強化

```bash
# IPアドレス制限（本番推奨）
gcloud sql instances patch ohanami-prod \
  --authorized-networks=YOUR_OFFICE_IP/32,CLOUD_RUN_IP_RANGE
```

### 認証トークンローテーション

定期的にAUTH_SECRETを更新：

```bash
# 新しいトークン生成
openssl rand -hex 32

# Cloud Runサービス更新
gcloud run services update ohanami \
  --update-env-vars=AUTH_SECRET=new-token \
  --region=asia-northeast1
```

## 🧪 テストスイート

### 統合テスト

```bash
# ローカル→本番環境テスト
OHANAMI_ENDPOINT="https://ohanami-xxx-an.a.run.app/api/wordpress-report" \
OHANAMI_AUTH_TOKEN="your-production-secret" \
php reporter/ohanami.php --test
```

### 負荷テスト

```bash
# Apache Benchでテスト  
ab -n 100 -c 10 \
  -H "Authorization: Bearer your-token" \
  -H "Content-Type: application/json" \
  -p reporter.sample.json \
  https://ohanami-xxx-an.a.run.app/api/wordpress-report
```

## 📈 運用・保守

### ログ監視

```bash
# Cloud Runログ確認
gcloud run logs tail ohanami --region=asia-northeast1

# エラーログフィルタ
gcloud run logs read ohanami \
  --filter='severity>=ERROR' \
  --region=asia-northeast1
```

### データベースバックアップ確認

```bash
# バックアップステータス確認
gcloud sql backups list --instance=ohanami-prod

# 手動バックアップ実行
gcloud sql backups create --instance=ohanami-prod
```

### スケーリング調整

```bash
# トラフィック増加時の調整
gcloud run services update ohanami \
  --cpu=2 \
  --memory=2Gi \
  --max-instances=20 \
  --region=asia-northeast1
```

## 🚨 トラブルシューティング

### よくある問題

1. **データベース接続エラー**
   ```bash
   # Cloud SQLの状態確認
   gcloud sql instances describe ohanami-prod
   ```

2. **認証エラー**
   ```bash
   # トークン確認
   echo $OHANAMI_AUTH_TOKEN
   ```

3. **メモリ不足エラー**
   ```bash
   # メモリ使用量確認
   gcloud run services describe ohanami --region=asia-northeast1
   ```

## 💰 コスト最適化

### Cloud SQL
- **現在**: db-f1-micro (月額$9)
- **スケールアップ時**: db-n1-standard-1 (月額$50程度)

### Cloud Run  
- **最小**: 月額$0（リクエストなし時）
- **想定**: 月額$10-30（中規模利用時）

合計想定コスト: **月額$20-80**

## 📞 サポート

問題が発生した場合は以下を確認：

1. Cloud Runログ
2. Cloud SQLメトリクス  
3. Slackアラート履歴
4. GitHub Issues（開発チーム向け）
