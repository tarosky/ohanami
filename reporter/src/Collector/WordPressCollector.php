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
     * ディレクトリ内のWordPressサイトを検索（wp-settings.phpベース）
     *
     * @param string $directory
     * @param int $maxDepth
     * @param int $currentDepth
     * @return array
     */
    public function findWordPressSites(string $directory, int $maxDepth = 3, int $currentDepth = 0): array
    {
        // 再帰的検索を使用（globの互換性問題を回避）
        return $this->findWordPressSitesRecursive($directory, $maxDepth, $currentDepth);
    }
    
    /**
     * 再帰的WordPressサイト検索（フォールバック用）
     *
     * @param string $directory
     * @param int $maxDepth
     * @param int $currentDepth
     * @return array
     */
    private function findWordPressSitesRecursive(string $directory, int $maxDepth = 3, int $currentDepth = 0): array
    {
        $sites = [];
        
        if ($currentDepth > $maxDepth || !is_dir($directory) || !is_readable($directory)) {
            return $sites;
        }
        
        // wp-settings.phpの存在でWordPressサイト判定
        if (file_exists($directory . '/wp-settings.php')) {
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
                $subsites = $this->findWordPressSitesRecursive($fullPath, $maxDepth, $currentDepth + 1);
                $sites = array_merge($sites, $subsites);
            }
        }
        
        return $sites;
    }
    
    /**
     * 単一WordPressサイトの情報収集（エラーハンドリング強化）
     *
     * @param string $sitePath
     * @return array|null
     */
    public function collectSiteInfo(string $sitePath): ?array
    {
        try {
            // 基本情報は必ず収集する（wp-settings.phpがあるのでWordPressサイト）
            $siteData = [
                'path' => $sitePath,
                'database' => [],
                'core' => [],
                'plugins' => [],
                'themes' => [],
                'errors' => []
            ];
            
            // WordPressが正常にインストールされているかチェック（終了コードベース）
            $coreInstalled = $this->checkWpCoreInstalled($sitePath);
            
            if (!$coreInstalled) {
                // wp-cliが使えない場合でも基本情報は記録
                $siteData['errors'][] = 'wp-cli core is-installed failed';
                
                // wp-config.phpから基本情報を取得試行
                $siteData['core'] = $this->collectCoreInfoFallback($sitePath);
            } else {
                // 通常の情報収集
                $siteData['database'] = $this->collectDatabaseInfoSafe($sitePath);
                $siteData['core'] = $this->collectCoreInfoSafe($sitePath);
                $siteData['plugins'] = $this->collectPluginsSafe($sitePath);
                $siteData['themes'] = $this->collectThemesSafe($sitePath);
            }
            
            return $siteData;
            
        } catch (\Exception $e) {
            // 完全にエラーの場合でも基本情報は返す
            return [
                'path' => $sitePath,
                'database' => [],
                'core' => [],
                'plugins' => [],
                'themes' => [],
                'errors' => ['Exception: ' . $e->getMessage()]
            ];
        }
    }
    
    /**
     * フォールバック用コア情報収集（wp-cliが使えない場合）
     *
     * @param string $sitePath
     * @return array
     */
    private function collectCoreInfoFallback(string $sitePath): array
    {
        $coreInfo = [
            'version' => null,
            'is_multisite' => false,
            'language' => 'unknown'
        ];
        
        // wp-includes/version.phpから情報取得を試行
        $versionFile = $sitePath . '/wp-includes/version.php';
        if (file_exists($versionFile)) {
            try {
                $content = file_get_contents($versionFile);
                if ($content && preg_match('/\$wp_version = [\'"]([^\'"]+)[\'"];/', $content, $matches)) {
                    $coreInfo['version'] = $matches[1];
                }
            } catch (\Exception $e) {
                // ファイル読み込みエラーは無視
            }
        }
        
        return $coreInfo;
    }
    
    /**
     * セーフなデータベース情報収集
     *
     * @param string $sitePath
     * @return array
     */
    private function collectDatabaseInfoSafe(string $sitePath): array
    {
        try {
            return $this->collectDatabaseInfo($sitePath);
        } catch (\Exception $e) {
            return [
                'version' => null,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * セーフなコア情報収集
     *
     * @param string $sitePath
     * @return array
     */
    private function collectCoreInfoSafe(string $sitePath): array
    {
        try {
            return $this->collectCoreInfo($sitePath);
        } catch (\Exception $e) {
            return [
                'version' => null,
                'is_multisite' => false,
                'language' => 'unknown',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * セーフなプラグイン情報収集
     *
     * @param string $sitePath
     * @return array
     */
    private function collectPluginsSafe(string $sitePath): array
    {
        try {
            return $this->collectPlugins($sitePath);
        } catch (\Exception $e) {
            return [];
        }
    }
    
    /**
     * セーフなテーマ情報収集
     *
     * @param string $sitePath
     * @return array
     */
    private function collectThemesSafe(string $sitePath): array
    {
        try {
            return $this->collectThemes($sitePath);
        } catch (\Exception $e) {
            return [];
        }
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
        $isMultisite = $this->checkWpMultisite($sitePath);
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
     * WordPress coreがインストールされているかチェック（終了コードベース）
     *
     * @param string $sitePath
     * @return bool
     */
    private function checkWpCoreInstalled(string $sitePath): bool
    {
        $fullCommand = sprintf('cd %s && %s core is-installed 2>/dev/null', 
            escapeshellarg($sitePath), 
            $this->wpCliPath
        );
        
        exec($fullCommand, $output, $exitCode);
        
        // wp core is-installedは成功時にexit code 0、失敗時に1を返す
        return $exitCode === 0;
    }
    
    /**
     * WordPressがマルチサイトかチェック（終了コードベース）
     *
     * @param string $sitePath
     * @return bool
     */
    private function checkWpMultisite(string $sitePath): bool
    {
        $fullCommand = sprintf('cd %s && %s core is-installed --network 2>/dev/null', 
            escapeshellarg($sitePath), 
            $this->wpCliPath
        );
        
        exec($fullCommand, $output, $exitCode);
        
        // wp core is-installed --networkは マルチサイトの場合にexit code 0、そうでなければ1を返す
        return $exitCode === 0;
    }
    
    /**
     * wp-cliコマンド実行（エラーキャッチ付き）
     *
     * @param string $command
     * @param string $sitePath
     * @return string|null
     * @throws \Exception エラー情報を含む例外
     */
    private function execWpCli(string $command, string $sitePath): ?string
    {
        $fullCommand = sprintf('cd %s && %s %s 2>&1', 
            escapeshellarg($sitePath), 
            $this->wpCliPath, 
            $command
        );
        
        exec($fullCommand, $output, $exitCode);
        $result = implode("\n", $output);
        
        // エラーチェック（Fatal error, Warning, etc.）
        if ($exitCode !== 0) {
            throw new \Exception("wp-cli exit code $exitCode: " . trim($result));
        }
        
        if ($this->containsPhpError($result)) {
            throw new \Exception("wp-cli PHP error: " . trim($result));
        }
        
        return trim($result) !== '' ? trim($result) : null;
    }
    
    /**
     * PHP Fatal Error等をチェック
     *
     * @param string $output
     * @return bool
     */
    private function containsPhpError(string $output): bool
    {
        $errorPatterns = [
            '/Fatal error:/',
            '/Parse error:/',
            '/Warning:.*in.*on line/',
            '/Notice:.*in.*on line/',
            '/Error:/'
        ];
        
        foreach ($errorPatterns as $pattern) {
            if (preg_match($pattern, $output)) {
                return true;
            }
        }
        
        return false;
    }
}
