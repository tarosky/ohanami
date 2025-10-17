<?php
/**
 * サーバー環境情報を収集するクラス
 */

namespace Ohanami\Collector;

class ServerCollector
{
    /**
     * サーバー情報を収集する
     *
     * @return array
     */
    public function collect(): array
    {
        $info = [];
        
        // PHPバージョン
        $info['PHP_VERSION'] = PHP_VERSION;
        
        // PHP SAPI
        $info['PHP_SAPI'] = php_sapi_name();
        
        // OS情報
        $info['OS'] = PHP_OS;
        
        // サーバーソフトウェア（もし利用可能なら）
        $info['SERVER_SOFTWARE'] = $_SERVER['SERVER_SOFTWARE'] ?? 'N/A';
        
        // ホスト名
        $info['HOSTNAME'] = gethostname() ?: 'Unknown';
        
        // 現在のユーザー
        if (function_exists('posix_getpwuid') && function_exists('posix_getuid')) {
            $userInfo = posix_getpwuid(posix_getuid());
            $info['USER'] = $userInfo['name'] ?? 'Unknown';
        } else {
            $info['USER'] = 'Unknown';
        }
        
        // MySQLバージョン（コマンドラインから取得）
        $mysqlVersion = $this->getMySQLVersion();
        $info['MYSQL_VERSION'] = $mysqlVersion ?: 'N/A';
        
        // wp-cliバージョン（もし利用可能なら）
        $wpcliVersion = $this->getWPCLIVersion();
        $info['WPCLI_VERSION'] = $wpcliVersion ?: 'N/A';
        
        return $info;
    }
    
    /**
     * MySQLバージョンを取得
     *
     * @return string|null
     */
    private function getMySQLVersion(): ?string
    {
        $output = [];
        $returnCode = 0;
        
        exec('mysql --version 2>/dev/null', $output, $returnCode);
        
        if ($returnCode === 0 && !empty($output[0])) {
            // "mysql  Ver 8.0.33-0ubuntu0.20.04.2 for Linux on x86_64 ((Ubuntu))" 
            // のような出力からバージョン部分を抽出
            if (preg_match('/Ver\s+([0-9]+\.[0-9]+\.[0-9]+)/', $output[0], $matches)) {
                return $matches[1];
            }
            return trim($output[0]);
        }
        
        return null;
    }
    
    /**
     * WP-CLIバージョンを取得
     *
     * @return string|null
     */
    private function getWPCLIVersion(): ?string
    {
        $output = [];
        $returnCode = 0;
        
        exec('wp --version 2>/dev/null', $output, $returnCode);
        
        if ($returnCode === 0 && !empty($output[0])) {
            // "WP-CLI 2.8.1" のような出力からバージョン部分を抽出
            if (preg_match('/WP-CLI\s+([0-9]+\.[0-9]+\.[0-9]+)/', $output[0], $matches)) {
                return $matches[1];
            }
            return trim($output[0]);
        }
        
        return null;
    }
}
