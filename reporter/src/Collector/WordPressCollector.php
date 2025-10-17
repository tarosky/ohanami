<?php

namespace Ohanami\Collector;

/**
 * WordPress情報収集クラス
 */
class WordPressCollector
{
    private string $wpCliPath;
    
    public function __construct(string $wpCliPath)
    {
        $this->wpCliPath = $wpCliPath;
    }
    
    /**
     * 指定ディレクトリ内のWordPressサイトを検索して情報収集
     *
     * @param array $scanDirs スキャン対象ディレクトリのリスト
     * @return array
     */
    public function collectSites(array $scanDirs): array
    {
        $sites = [];
        
        foreach ($scanDirs as $scanDir) {
            $foundSites = $this->findWordPressSites($scanDir);
            foreach ($foundSites as $sitePath) {
                $siteData = $this->collectSiteInfo($sitePath);
                if ($siteData !== null) {
                    $sites[] = $siteData;
                }
            }
        }
        
        return $sites;
    }
    
    /**
     * ディレクトリ内のWordPressサイトを検索
     *
     * @param string $directory
     * @param int $maxDepth
     * @param int $currentDepth
     * @return array
     */
    public function findWordPressSites(string $directory, int $maxDepth = 3, int $currentDepth = 0): array
    {
        $sites = [];
        
        if ($currentDepth > $maxDepth || !is_dir($directory) || !is_readable($directory)) {
            return $sites;
        }
        
        // 現在のディレクトリにWordPressがあるかチェック
        if ($this->isWordPressDirectory($directory)) {
            $sites[] = $directory;
        }
        
        // サブディレクトリを検索
        $items = scandir($directory);
        if ($items === false) {
            return $sites;
        }
        
        foreach ($items as $item) {
            if ($item === '.' || $item === '..' || str_starts_with($item, '.')) {
                continue;
            }
            
            $fullPath = $directory . '/' . $item;
            if (is_dir($fullPath)) {
                $subsites = $this->findWordPressSites($fullPath, $maxDepth, $currentDepth + 1);
                $sites = array_merge($sites, $subsites);
            }
        }
        
        return $sites;
    }
    
    /**
     * ディレクトリがWordPressサイトかチェック
     *
     * @param string $directory
     * @return bool
     */
    private function isWordPressDirectory(string $directory): bool
    {
        // wp-config.php または wp-settings.php が存在すればWordPressサイト
        return file_exists($directory . '/wp-config.php') || 
               file_exists($directory . '/wp-settings.php');
    }
    
    /**
     * 単一WordPressサイトの情報収集
     *
     * @param string $sitePath
     * @return array|null
     */
    public function collectSiteInfo(string $sitePath): ?array
    {
        // WordPressが正常にインストールされているかチェック
        if (!$this->execWpCli('core is-installed', $sitePath)) {
            return null;
        }
        
        $siteData = [
            'path' => $sitePath,
            'database' => $this->collectDatabaseInfo($sitePath),
            'core' => $this->collectCoreInfo($sitePath),
            'plugins' => $this->collectPlugins($sitePath),
            'themes' => $this->collectThemes($sitePath),
        ];
        
        return $siteData;
    }
    
    /**
     * データベース情報収集
     *
     * @param string $sitePath
     * @return array
     */
    private function collectDatabaseInfo(string $sitePath): array
    {
        // MySQLバージョン取得
        $version = $this->execWpCli('db query "SELECT VERSION();" --skip-column-names', $sitePath);
        
        return [
            'version' => $version ? trim($version) : null
        ];
    }
    
    /**
     * WordPressコア情報収集
     *
     * @param string $sitePath
     * @return array
     */
    private function collectCoreInfo(string $sitePath): array
    {
        $version = $this->execWpCli('core version', $sitePath);
        $isMultisite = $this->execWpCli('core is-installed --network', $sitePath) !== null;
        $language = $this->execWpCli('language core list --field=language --status=active', $sitePath);
        
        return [
            'version' => $version ? trim($version) : null,
            'is_multisite' => $isMultisite,
            'language' => $language ? trim($language) : 'en_US'
        ];
    }
    
    /**
     * プラグイン情報収集
     *
     * @param string $sitePath
     * @return array
     */
    private function collectPlugins(string $sitePath): array
    {
        $pluginsJson = $this->execWpCli('plugin list --format=json', $sitePath);
        if (!$pluginsJson) {
            return [];
        }
        
        $plugins = json_decode($pluginsJson, true);
        if (!is_array($plugins)) {
            return [];
        }
        
        return array_map(function($plugin) {
            return [
                'name' => $plugin['name'] ?? 'unknown',
                'version' => $plugin['version'] ?? null,
                'status' => $plugin['status'] ?? 'unknown',
                'update' => $plugin['update'] ?? 'none',
                'auto_update' => $plugin['auto_update'] ?? 'off'
            ];
        }, $plugins);
    }
    
    /**
     * テーマ情報収集
     *
     * @param string $sitePath
     * @return array
     */
    private function collectThemes(string $sitePath): array
    {
        $themesJson = $this->execWpCli('theme list --format=json', $sitePath);
        if (!$themesJson) {
            return [];
        }
        
        $themes = json_decode($themesJson, true);
        if (!is_array($themes)) {
            return [];
        }
        
        return array_map(function($theme) {
            return [
                'name' => $theme['name'] ?? 'unknown',
                'version' => $theme['version'] ?? null,
                'status' => $theme['status'] ?? 'unknown',
                'update' => $theme['update'] ?? 'none',
                'auto_update' => $theme['auto_update'] ?? 'off'
            ];
        }, $themes);
    }
    
    /**
     * wp-cliコマンド実行
     *
     * @param string $command
     * @param string $sitePath
     * @return string|null
     */
    private function execWpCli(string $command, string $sitePath): ?string
    {
        $fullCommand = sprintf('cd %s && %s %s 2>/dev/null', 
            escapeshellarg($sitePath), 
            $this->wpCliPath, 
            $command
        );
        
        $result = shell_exec($fullCommand);
        
        return $result !== null && trim($result) !== '' ? $result : null;
    }
}
