# Cloud Runにセットアップしたエンドポイントについて

## Google Cloudセットアップ方法

1. gcloud CLIをインストールします。 `brew install google-cloud-sdk`
2. gcloudを初期化 & 認証します。 `gcloud init`
   1. `gcloud auth login` や `gcloud auth activate-service-account` で個別に認証も出来ます。
3. ブラウザが立ち上がるので、会社で使っているGoogleアカウントでログインします。
4. 認証するとプロンプトに、どのプロジェクトを選択するかなど求められるので選択して、承認ステップを完了します。
5. 全て完了したら、 `gcloud config list` で正しい状態になっているか確認します。

## デプロイ方法

デプロイ方法は現状2種類あります。

### シェルスクリプトから実行

1. `.env.example` をコピーして、 `.env` を作成
2. `.env` に実際の設定値を入力 (社内向けREADME参照)
3. `chmod +x deploy_google_cloud.sh`
4. `./deploy_google_cloud.sh` を実行

### コマンド

`.env` ファイルがあるディレクトリで実行してください。

```
gcloud run deploy $SERVICE_NAME \
  --source . \
  --region=$REGION \
  --service-account=$SERVICE_ACCOUNT \
  --set-secrets=GOOGLE_SPREADSHEET_ID=GOOGLE_SPREADSHEET_ID:latest,BUCKET_NAME=BUCKET_NAME:latest,AUTH_SECRET=AUTH_SECRET:latest,SLACK_WEBHOOK_URL=SLACK_WEBHOOK_URL:latest
```
