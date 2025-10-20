# Ohanami Cloud SQL デプロイメント

TerraformでCloud SQLインスタンス（最小構成）を作成します。

## 🚀 クイックスタート

### 1. 前提条件
```bash
# Terraformのインストール確認
terraform version

# Google Cloud CLIのセットアップ
gcloud auth login
gcloud config set project YOUR_PROJECT_ID

# Terraform用の認証
gcloud auth application-default login
```

### 2. 環境変数の設定
```bash
# 必須変数
export TF_VAR_project_id="your-google-cloud-project-id"
export TF_VAR_db_password="your-secure-database-password"

# オプション変数（デフォルト値あり）
export TF_VAR_environment="prod"
export TF_VAR_region="asia-northeast1"
```

### 3. Terraformの実行
```bash
cd terraform

# 初期化
terraform init

# 計画確認
terraform plan

# 適用
terraform apply
```

## 📋 作成されるリソース

### Cloud SQL インスタンス
- **インスタンス名**: `ohanami-prod`（環境により変動）
- **エンジン**: MySQL 8.0
- **構成**: db-f1-micro（最小構成）
- **ディスク**: 10GB SSD
- **リージョン**: asia-northeast1（東京）

### データベース
- **データベース名**: `ohanami`
- **ユーザー名**: `ohanami_user`
- **パスワード**: 環境変数で指定

### 作成後の手順
```bash
# 出力情報確認
terraform output

# 接続情報取得
terraform output cloud_run_env_vars
```

## 🗄️ データベース初期化

Cloud SQLインスタンス作成後、スキーマを適用します：

```bash
# Cloud SQL Proxyでローカル接続
cloud-sql-proxy $(terraform output -raw database_connection_name) &

# スキーマ適用
mysql -h 127.0.0.1 -u ohanami_user -p ohanami < ../database/init/01_schema.sql
```

## 🔧 Cloud Run環境変数設定

Terraformの出力を使用してCloud Runの環境変数を設定：

```bash
# 出力を確認
terraform output cloud_run_env_vars

# Cloud Runサービス更新例
gcloud run services update ohanami \
  --set-env-vars="DB_HOST=$(terraform output -raw database_public_ip)" \
  --set-env-vars="DB_PORT=3306" \
  --set-env-vars="DB_NAME=$(terraform output -raw database_name)" \
  --set-env-vars="DB_USER=$(terraform output -raw database_user)" \
  --set-env-vars="DB_PASSWORD=${TF_VAR_db_password}" \
  --region=asia-northeast1
```

## 💰 料金目安

**最小構成での月額料金目安**：
- db-f1-micro: ~$7/月
- 10GB SSD: ~$1.70/月
- **合計**: ~$9/月

## 🛡️ セキュリティ注意事項

**⚠️ 現在の設定は開発・テスト用です**

本番環境では以下を変更してください：

1. **IP制限**: `0.0.0.0/0`を適切なIPレンジに変更
2. **削除保護**: `deletion_protection = true`に変更
3. **SSL証明書**: SSL証明書を設定
4. **VPC**: プライベートIPでの接続に変更

## 🧹 リソース削除

```bash
# リソースの削除
terraform destroy

# 確認
terraform show
```

## 📝 トラブルシューティング

### よくあるエラー

1. **認証エラー**
```bash
gcloud auth application-default login
```

2. **API有効化エラー**
```bash
gcloud services enable sqladmin.googleapis.com
```

3. **権限エラー**
```bash
# Cloud SQL Admin権限が必要
gcloud projects add-iam-policy-binding $TF_VAR_project_id \
  --member="user:$(gcloud config get-value account)" \
  --role="roles/cloudsql.admin"
```

### 接続テスト

```bash
# Cloud SQL Proxyでテスト接続
cloud-sql-proxy $(terraform output -raw database_connection_name) &
mysql -h 127.0.0.1 -u ohanami_user -p ohanami -e "SHOW TABLES;"
