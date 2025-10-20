-- Ohanami WordPress 健康管理ツール データベーススキーマ
-- MySQL 8.0対応

-- 管理者・組織情報テーブル
CREATE TABLE managers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL DEFAULT 'タロスカイ',
    organization VARCHAR(255) DEFAULT 'Tarosky Inc.',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- サーバー情報テーブル（サービス + ホスト名 + ユーザー名でユニーク識別）
CREATE TABLE servers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    manager_id INT NOT NULL DEFAULT 1,
    
    -- サーバー識別情報（ユニーク制約）
    service_provider VARCHAR(50) NOT NULL DEFAULT 'sakura',
    hostname VARCHAR(255) NOT NULL,
    username VARCHAR(100) NOT NULL,
    
    -- 管理用情報
    display_name VARCHAR(255),
    description TEXT,
    
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (manager_id) REFERENCES managers(id),
    UNIQUE KEY unique_server (service_provider, hostname, username),
    INDEX idx_provider_host (service_provider, hostname)
);

-- レポート実行履歴テーブル（1回のPOSTリクエスト = 1レコード）
CREATE TABLE reports (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    server_id INT NOT NULL,
    manager_id INT NOT NULL DEFAULT 1,
    
    -- metadata情報
    ohanami_version VARCHAR(50),
    execution_time TIMESTAMP,
    hostname VARCHAR(255),
    username VARCHAR(100),
    working_directory VARCHAR(500),
    
    -- environment情報
    php_version VARCHAR(50),
    php_sapi VARCHAR(50),
    os_name VARCHAR(100),
    server_software VARCHAR(255),
    mysql_version VARCHAR(100),
    wpcli_version VARCHAR(50),
    wpcli_available BOOLEAN DEFAULT FALSE,
    wpcli_path VARCHAR(500),
    
    sites_count INT DEFAULT 0,
    status ENUM('success', 'partial', 'failed') DEFAULT 'success',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (server_id) REFERENCES servers(id),
    FOREIGN KEY (manager_id) REFERENCES managers(id),
    INDEX idx_server_execution (server_id, execution_time),
    INDEX idx_hostname_time (hostname, execution_time)
);

-- WordPressサイト基本情報テーブル
CREATE TABLE wordpress_sites (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    report_id BIGINT NOT NULL,
    server_id INT NOT NULL,
    manager_id INT NOT NULL DEFAULT 1,
    
    -- サイト基本情報
    site_path VARCHAR(500) NOT NULL,
    
    -- database情報
    database_version VARCHAR(100),
    
    -- core情報
    core_version VARCHAR(50),
    is_multisite BOOLEAN DEFAULT FALSE,
    language VARCHAR(10) DEFAULT 'en_US',
    core_error TEXT,
    
    -- エラー情報（JSON形式）
    errors JSON,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (report_id) REFERENCES reports(id) ON DELETE CASCADE,
    FOREIGN KEY (server_id) REFERENCES servers(id),
    FOREIGN KEY (manager_id) REFERENCES managers(id),
    
    INDEX idx_site_path (server_id, site_path),
    INDEX idx_report_site (report_id),
    INDEX idx_core_version (core_version)
);

-- プラグイン情報テーブル
CREATE TABLE plugins (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    site_id BIGINT NOT NULL,
    name VARCHAR(255) NOT NULL,
    version VARCHAR(50),
    status ENUM('active', 'inactive', 'active-network', 'must-use', 'dropin') DEFAULT 'inactive',
    update_status ENUM('available', 'none') DEFAULT 'none',
    update_value BOOLEAN DEFAULT FALSE,
    auto_update ENUM('on', 'off') DEFAULT 'off',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (site_id) REFERENCES wordpress_sites(id) ON DELETE CASCADE,
    INDEX idx_site_plugin (site_id, name),
    INDEX idx_plugin_update (name, update_status)
);

-- テーマ情報テーブル
CREATE TABLE themes (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    site_id BIGINT NOT NULL,
    name VARCHAR(255) NOT NULL,
    version VARCHAR(50),
    status ENUM('active', 'inactive') DEFAULT 'inactive',
    update_status ENUM('available', 'none') DEFAULT 'none',
    auto_update ENUM('on', 'off') DEFAULT 'off',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (site_id) REFERENCES wordpress_sites(id) ON DELETE CASCADE,
    INDEX idx_site_theme (site_id, name),
    INDEX idx_theme_status (name, status)
);

-- 初期データの投入
INSERT INTO managers (id, name, organization) VALUES 
(1, 'タロスカイ', 'Tarosky Inc.');

-- テスト用サーバーデータ
INSERT INTO servers (service_provider, hostname, username, display_name, description) VALUES
('sakura', 'www3952.sakura.ne.jp', 'kunoichi-demo', 'さくらのレンタルサーバー（kunoichi-demo）', 'デモ用サーバー'),
('sakura', 'www1234.sakura.ne.jp', 'fumiki', 'さくらのレンタルサーバー（fumiki）', '高橋文樹様のサーバー');
