# Terraform変数定義

variable "project_id" {
  description = "Google Cloud Project ID"
  type        = string
  # 使用例: export TF_VAR_project_id="your-project-id"
}

variable "environment" {
  description = "環境名 (dev, staging, prod)"
  type        = string
  default     = "prod"
}

variable "region" {
  description = "Google Cloud Region"
  type        = string
  default     = "asia-northeast1"  # 東京リージョン
}

variable "zone" {
  description = "Google Cloud Zone"
  type        = string
  default     = "asia-northeast1-a"
}

variable "db_password" {
  description = "Database password for ohanami_user"
  type        = string
  sensitive   = true
  # 使用例: export TF_VAR_db_password="your-secure-password"
}
