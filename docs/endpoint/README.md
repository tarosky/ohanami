# Cloud Runにセットアップしたエンドポイントについて

## デプロイ方法

デプロイ方法は現状2種類あります。

### シェルスクリプトから実行

1. `.env.example` をコピーして、 `.env` を作成
2. `.env` に実際の設定値を入力
3. `./deploy_google_cloud.sh` を実行

### コマンド

`.env` ファイルがあるディレクトリで実行してください。

```
gcloud run deploy $SERVICE_NAME \
  --source . \
  --region=$REGION \
  --service-account=$SERVICE_ACCOUNT \
  --set-secrets=GOOGLE_SPREADSHEET_ID=GOOGLE_SPREADSHEET_ID:latest,BUCKET_NAME=BUCKET_NAME:latest,AUTH_SECRET=AUTH_SECRET:latest,SLACK_WEBHOOK_URL=SLACK_WEBHOOK_URL:latest
```
