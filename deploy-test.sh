#!/bin/bash
set -e

# SSH経由でpharファイルをテスト実行するスクリプト
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
CONFIG_FILE="${SCRIPT_DIR}/deploy-config.json"

# 色付きメッセージ用
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

print_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# 使用方法表示
show_usage() {
    echo "使用方法: $0 [server_name]"
    echo ""
    echo "利用可能なサーバー:"
    if [[ -f "$CONFIG_FILE" ]]; then
        jq -r '.servers | keys[]' "$CONFIG_FILE" | sed 's/^/  - /'
    else
        echo "  (設定ファイルが見つかりません: $CONFIG_FILE)"
    fi
    echo ""
    echo "例: $0 fumiki.sakura"
}

# 設定ファイル確認
if [[ ! -f "$CONFIG_FILE" ]]; then
    print_error "設定ファイルが見つかりません: $CONFIG_FILE"
    print_info "deploy-config.json.sample をコピーして deploy-config.json を作成してください"
    exit 1
fi

# jq コマンド確認
if ! command -v jq &> /dev/null; then
    print_error "jq コマンドが見つかりません。インストールしてください: brew install jq"
    exit 1
fi

# サーバー名取得
SERVER_NAME="$1"
if [[ -z "$SERVER_NAME" ]]; then
    SERVER_NAME=$(jq -r '.default_server' "$CONFIG_FILE")
    if [[ "$SERVER_NAME" == "null" || -z "$SERVER_NAME" ]]; then
        print_error "サーバー名が指定されておらず、default_serverも設定されていません"
        show_usage
        exit 1
    fi
    print_info "デフォルトサーバーを使用: $SERVER_NAME"
fi

# サーバー設定取得
SSH_HOST=$(jq -r ".servers.\"$SERVER_NAME\".ssh_config_host" "$CONFIG_FILE")
REMOTE_PATH=$(jq -r ".servers.\"$SERVER_NAME\".remote_path" "$CONFIG_FILE")
PHP_COMMAND=$(jq -r ".servers.\"$SERVER_NAME\".php_command" "$CONFIG_FILE")

if [[ "$SSH_HOST" == "null" || "$REMOTE_PATH" == "null" || "$PHP_COMMAND" == "null" ]]; then
    print_error "サーバー '$SERVER_NAME' の設定が見つかりません"
    show_usage
    exit 1
fi

print_info "=== Ohanami SSH Deploy & Test ==="
print_info "サーバー: $SERVER_NAME"
print_info "SSH Host: $SSH_HOST"
print_info "リモートパス: $REMOTE_PATH"

# Pharファイル存在確認
PHAR_PATH="${SCRIPT_DIR}/reporter/ohanami.phar"
if [[ ! -f "$PHAR_PATH" ]]; then
    print_warn "Pharファイルが見つかりません。ビルドします..."
    cd "${SCRIPT_DIR}/reporter"
    if ! ./vendor/bin/box compile; then
        print_error "Pharファイルのビルドに失敗しました"
        exit 1
    fi
    cd "$SCRIPT_DIR"
fi

# リモートディレクトリ作成
print_info "リモートディレクトリを準備中..."
if ! ssh "$SSH_HOST" "mkdir -p '$REMOTE_PATH'"; then
    print_error "リモートディレクトリの作成に失敗しました"
    exit 1
fi

# 一意なファイル名生成
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
REMOTE_FILE="${REMOTE_PATH}ohanami_${TIMESTAMP}.phar"

# Pharファイルアップロード
print_info "Pharファイルをアップロード中..."
if ! scp "$PHAR_PATH" "${SSH_HOST}:${REMOTE_FILE}"; then
    print_error "ファイルのアップロードに失敗しました"
    exit 1
fi

# リモート実行
print_info "リモートで実行中..."
echo "----------------------------------------"
if ssh "$SSH_HOST" "$PHP_COMMAND '$REMOTE_FILE'"; then
    EXEC_STATUS=$?
    echo "----------------------------------------"
    print_success "実行完了 (終了コード: $EXEC_STATUS)"
else
    EXEC_STATUS=$?
    echo "----------------------------------------"
    print_error "実行失敗 (終了コード: $EXEC_STATUS)"
fi

# 一時ファイル削除
print_info "一時ファイルを削除中..."
if ! ssh "$SSH_HOST" "rm -f '$REMOTE_FILE'"; then
    print_warn "一時ファイルの削除に失敗しました: $REMOTE_FILE"
fi

print_success "テスト完了"
exit $EXEC_STATUS
