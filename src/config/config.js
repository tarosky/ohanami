// 環境設定の読み込み
import dotenv from 'dotenv';
dotenv.config();

export default {
  // サーバー設定
  port: process.env.PORT || 8080,

  // 認証設定
  authSecret: process.env.AUTH_SECRET,

  // Google API設定
  googleCredentialsBase64: process.env.GOOGLE_CREDENTIALS_BASE64,
  spreadsheetId: process.env.GOOGLE_SPREADSHEET_ID,

  // Cloud Storage 設定
  bucketName: process.env.BUCKET_NAME,

  // Slack 設定
  slackWebhookUrl: process.env.SLACK_WEBHOOK_URL
};
