// 環境設定の読み込み
import dotenv from 'dotenv';

// NODE_ENVに応じて適切な.envファイルを読み込む
const envFile = process.env.NODE_ENV === 'development' ? '.env.development' : '.env';
dotenv.config({ path: envFile });

export default {
  // サーバー設定
  port: process.env.PORT || 8080,

  // 認証設定
  authSecret: process.env.AUTH_SECRET,

  // データベース設定
  dbHost: process.env.DB_HOST || 'localhost',
  dbPort: parseInt(process.env.DB_PORT) || 3306,
  dbName: process.env.DB_NAME || 'ohanami_dev',
  dbUser: process.env.DB_USER || 'dev',
  dbPassword: process.env.DB_PASSWORD || 'password',

  // Google API設定
  googleCredentialsBase64: process.env.GOOGLE_CREDENTIALS_BASE64,
  spreadsheetId: process.env.GOOGLE_SPREADSHEET_ID,

  // Cloud Storage 設定
  bucketName: process.env.BUCKET_NAME,

  // Slack 設定
  slackWebhookUrl: process.env.SLACK_WEBHOOK_URL
};
