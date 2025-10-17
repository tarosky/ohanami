<?php

namespace Ohanami\Detector;

/**
 * スキャンディレクトリ検出クラス
 */
class DirectoryDetector
{
    /**
     * Webルートディレクトリ候補
     */
    private array $candidates = [
        '~/www',                    // さくらレンタルサーバー
        '~/public_html',            // ConoHa WING、一般的なcPanel
        '/var/www',                 // VPS/専用サーバー
        '~/htdocs',                 // XAMPP等のローカル開発環境
        '~/public',                 // Laravel等のフレームワーク
    ];
    
    /**
     * スキャンディレクトリ自動検出
     *
     * @return array 存在するディレクトリのリスト
     */
    public function detectWebRoots(): array
    {
        $validDirs = [];
        $homeDir = $_SERVER['HOME'] ?? '';
        
        foreach ($this->candidates as $candidate) {
            // チルダ展開
            $dir = str_replace('~', $homeDir, $candidate);
            
            if (is_dir($dir) && is_readable($dir)) {
                $validDirs[] = $dir;
            }
        }
        
        return $validDirs;
    }
    
    /**
     * 最適なスキャンディレクトリを推定
     *
     * @return string|null
     */
    public function detectBestWebRoot(): ?string
    {
        $validDirs = $this->detectWebRoots();
        
        if (empty($validDirs)) {
            return null;
        }
        
        // 優先順位に基づいて最適なディレクトリを選択
        $homeDir = $_SERVER['HOME'] ?? '';
        $priorities = [
            $homeDir . '/www',           // さくら最優先
            $homeDir . '/public_html',   // 一般的なレンタルサーバー
            '/var/www',                  // VPS
            $homeDir . '/htdocs',        // ローカル開発
            $homeDir . '/public',        // フレームワーク
        ];
        
        foreach ($priorities as $priority) {
            if (in_array($priority, $validDirs)) {
                return $priority;
            }
        }
        
        // 最初に見つかったものを返す
        return $validDirs[0];
    }
    
    /**
     * ディレクトリがWordPressサイトを含んでいるかチェック
     *
     * @param string $directory
     * @return bool
     */
    public function hasWordPressSites(string $directory): bool
    {
        if (!is_dir($directory) || !is_readable($directory)) {
            return false;
        }
        
        return $this->scanForWordPress($directory, 2); // 2階層まで検索
    }
    
    /**
     * WordPressサイト再帰検索
     *
     * @param string $directory
     * @param int $maxDepth 最大探索深度
     * @param int $currentDepth 現在の深度
     * @return bool
     */
    private function scanForWordPress(string $directory, int $maxDepth, int $currentDepth = 0): bool
    {
        if ($currentDepth > $maxDepth) {
            return false;
        }
        
        // wp-config.phpまたはwp-settings.phpが存在すればWordPressサイト
        if (file_exists($directory . '/wp-config.php') || 
            file_exists($directory . '/wp-settings.php')) {
            return true;
        }
        
        // サブディレクトリを検索
        $items = scandir($directory);
        if ($items === false) {
            return false;
        }
        
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            
            $fullPath = $directory . '/' . $item;
            if (is_dir($fullPath) && is_readable($fullPath)) {
                if ($this->scanForWordPress($fullPath, $maxDepth, $currentDepth + 1)) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * ディレクトリの統計情報を取得
     *
     * @param string $directory
     * @return array
     */
    public function getDirectoryStats(string $directory): array
    {
        if (!is_dir($directory) || !is_readable($directory)) {
            return [
                'exists' => false,
                'readable' => false,
                'size' => 0,
                'file_count' => 0,
                'subdirs' => 0
            ];
        }
        
        $items = scandir($directory);
        if ($items === false) {
            return [
                'exists' => true,
                'readable' => false,
                'size' => 0,
                'file_count' => 0,
                'subdirs' => 0
            ];
        }
        
        $fileCount = 0;
        $subdirs = 0;
        
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            
            $fullPath = $directory . '/' . $item;
            if (is_dir($fullPath)) {
                $subdirs++;
            } else {
                $fileCount++;
            }
        }
        
        return [
            'exists' => true,
            'readable' => true,
            'size' => disk_free_space($directory) ? disk_total_space($directory) : 0,
            'file_count' => $fileCount,
            'subdirs' => $subdirs
        ];
    }
}
