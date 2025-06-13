#!/bin/bash

# 環境変数ファイルを読み込み
if [ -f .env ]; then
  export $(cat .env | grep -v '^#' | xargs)
else
  echo "❌ .envファイルが見つかりません"
  echo "💡 .env.exampleをコピーして.envを作成してください"
  exit 1
fi

# 色付きログ
info()  { echo -e "\033[1;34m[INFO]\033[0m $1"; }
error() { echo -e "\033[1;31m[ERROR]\033[0m $1"; }

info "設定値確認:"
info "  SERVICE_NAME: $SERVICE_NAME"
info "  REGION: $REGION"
info "  SERVICE_ACCOUNT: $SERVICE_ACCOUNT"

info "Cloud Run にデプロイ中..."
if ! gcloud run deploy $SERVICE_NAME \
  --source . \
  --region=$REGION \
  --service-account=$SERVICE_ACCOUNT \
  --set-secrets=GOOGLE_SPREADSHEET_ID=GOOGLE_SPREADSHEET_ID:latest,BUCKET_NAME=BUCKET_NAME:latest,AUTH_SECRET=AUTH_SECRET:latest,SLACK_WEBHOOK_URL=SLACK_WEBHOOK_URL:latest \
  --allow-unauthenticated; then
  error "デプロイ失敗"

  # Slack Webhook を Secret Manager から取得
  SLACK_WEBHOOK_URL=$(gcloud secrets versions access latest --secret=SLACK_WEBHOOK_URL 2>/dev/null)

  if [ -n "$SLACK_WEBHOOK_URL" ]; then
    curl -X POST -H "Content-Type: application/json" \
      -d "{\"text\": \"❌ Cloud Run デプロイに失敗しました: *${SERVICE_NAME}*\"}" \
      "$SLACK_WEBHOOK_URL"
  else
    error "Slack通知失敗：Webhook URL が取得できませんでした"
  fi

  exit 1
fi

info "デプロイ完了、URL取得中..."
SERVICE_URL=$(gcloud run services describe $SERVICE_NAME \
  --region=$REGION \
  --format='value(status.url)')

# Slack Webhook 取得
SLACK_WEBHOOK_URL=$(gcloud secrets versions access latest --secret=SLACK_WEBHOOK_URL 2>/dev/null)

if [ -n "$SLACK_WEBHOOK_URL" ]; then
  SLACK_TEXT="✅ *Cloud Run デプロイ完了*\nサービス名: *${SERVICE_NAME}*\nURL: ${SERVICE_URL}"

  curl -X POST -H "Content-Type: application/json" \
    -d "{\"text\": \"$SLACK_TEXT\"}" \
    "$SLACK_WEBHOOK_URL"

  info "Slackに通知しました ✅"
else
  error "Slack通知失敗：Webhook URL が取得できませんでした"
fi
