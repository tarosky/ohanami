<?php

namespace Ohanami\Detector;

/**
 * wp-cli検出・判別クラス
 */
class WpCliDetector
{
    /**
     * wp-cli候補パス
     */
    private array $candidates = [
        '/usr/local/bin/wp-cli.phar',    // さくらレンタルサーバー
        'wp',                            // PATH通り
        '/usr/local/bin/wp',             // 一般的なLinux
        '/opt/homebrew/bin/wp',          // macOS Homebrew (ARM)
        '/usr/local/homebrew/bin/wp',    // macOS Homebrew (Intel)
    ];
    
    /**
     * wp-cli自動検出
     *
     * @return string|null 検出されたwp-cliパス、見つからない場合null
     */
    public function detect(): ?string
    {
        foreach ($this->candidates as $path) {
            if ($this->isValidWpCli($path)) {
                return $path;
            }
        }
        
        // ホームディレクトリのユーザーインストールもチェック
        $homeWpCli = $_SERVER['HOME'] . '/.wp-cli/bin/wp';
        if ($this->isValidWpCli($homeWpCli)) {
            return $homeWpCli;
        }
        
        return null;
    }
    
    /**
     * 指定パスのwp-cli有効性チェック
     *
     * @param string $path
     * @return bool
     */
    private function isValidWpCli(string $path): bool
    {
        // まずファイルの存在チェック（PATH通りの場合はwhichで確認）
        if ($path === 'wp') {
            $which = shell_exec('which wp 2>/dev/null');
            if (!$which || trim($which) === '') {
                return false;
            }
        } else {
            if (!file_exists($path) || !is_executable($path)) {
                return false;
            }
        }
        
        // wp-cliの動作確認
        $result = shell_exec("$path --info 2>/dev/null");
        if ($result === null) {
            return false;
        }
        
        // wp-cliの出力に'WP-CLI'が含まれていることを確認
        return strpos($result, 'WP-CLI') !== false;
    }
    
    /**
     * wp-cliバージョン取得
     *
     * @param string $wpCliPath
     * @return string|null
     */
    public function getVersion(string $wpCliPath): ?string
    {
        $result = shell_exec("$wpCliPath --version 2>/dev/null");
        if ($result === null) {
            return null;
        }
        
        // "WP-CLI 2.12.0" のような形式から数字部分を抽出
        if (preg_match('/WP-CLI\s+([0-9.]+)/', $result, $matches)) {
            return $matches[1];
        }
        
        return 'Unknown';
    }
}
