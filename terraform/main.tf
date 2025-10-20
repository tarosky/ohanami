# Ohanami WordPress健康管理ツール - Cloud SQL設定
# 最小構成での本番環境

terraform {
  required_version = ">= 1.0"
  required_providers {
    google = {
      source  = "hashicorp/google"
      version = "~> 5.0"
    }
  }
}

# Google Cloud Provider設定
provider "google" {
  project = var.project_id
  region  = var.region
  zone    = var.zone
}

# Cloud SQL インスタンス（最小構成）
resource "google_sql_database_instance" "ohanami" {
  name             = "ohanami-${var.environment}"
  database_version = "MYSQL_8_0"
  region           = var.region
  deletion_protection = false  # 開発・テスト用

  settings {
    tier      = "db-f1-micro"  # 最小構成
    disk_size = 10             # 10GB（最小）
    disk_type = "PD_SSD"

    # バックアップ設定（最小）
    backup_configuration {
      enabled                        = true
      start_time                    = "03:00"
      point_in_time_recovery_enabled = false  # コスト削減
      location                      = var.region
    }

    # ネットワーク設定
    ip_configuration {
      ipv4_enabled = true
      # Cloud Runからのアクセスを許可
      authorized_networks {
        name  = "allow-all"  # 本番では適切なIPレンジに制限
        value = "0.0.0.0/0"
      }
    }

    # データベースフラグ
    database_flags {
      name  = "slow_query_log"
      value = "off"  # パフォーマンス向上
    }
  }
}

# データベース作成
resource "google_sql_database" "ohanami" {
  name     = "ohanami"
  instance = google_sql_database_instance.ohanami.name
}

# データベースユーザー作成
resource "google_sql_user" "ohanami" {
  name     = "ohanami_user"
  instance = google_sql_database_instance.ohanami.name
  password = var.db_password
}

# Cloud Runサービス用のIAMロール（将来用）
# 権限問題により一時的にコメントアウト
# resource "google_project_iam_member" "cloudsql_client" {
#   project = var.project_id
#   role    = "roles/cloudsql.client"
#   member  = "serviceAccount:${var.project_id}@appspot.gserviceaccount.com"
# }
