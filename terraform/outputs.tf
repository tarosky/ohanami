# Terraform出力値

output "database_connection_name" {
  description = "Cloud SQL接続名（Cloud Runで使用）"
  value       = google_sql_database_instance.ohanami.connection_name
}

output "database_public_ip" {
  description = "Cloud SQL Public IP Address"
  value       = google_sql_database_instance.ohanami.public_ip_address
}

output "database_private_ip" {
  description = "Cloud SQL Private IP Address"
  value       = google_sql_database_instance.ohanami.private_ip_address
}

output "database_name" {
  description = "データベース名"
  value       = google_sql_database.ohanami.name
}

output "database_user" {
  description = "データベースユーザー名"
  value       = google_sql_user.ohanami.name
}

output "instance_name" {
  description = "Cloud SQLインスタンス名"
  value       = google_sql_database_instance.ohanami.name
}

# Cloud Run環境変数用の情報
output "cloud_run_env_vars" {
  description = "Cloud Runで使用する環境変数のサンプル"
  value = {
    DB_HOST                = google_sql_database_instance.ohanami.public_ip_address
    DB_PORT                = "3306"
    DB_NAME                = google_sql_database.ohanami.name
    DB_USER                = google_sql_user.ohanami.name
    CLOUD_SQL_CONNECTION_NAME = google_sql_database_instance.ohanami.connection_name
  }
}
