#!/usr/bin/env php
<?php
/**
 * ohanami - WordPress monitoring tool for rental servers
 *
 * @author TAROSKY
 * @version 0.3.0
 * @see https://github.com/tarosky/ohanami
 */

// Composerのオートローダーを読み込み
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} elseif (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
} elseif (file_exists('vendor/autoload.php')) {
    require_once 'vendor/autoload.php';
}

use Ohanami\Config\OptionParser;
use Ohanami\Detector\WpCliDetector;
use Ohanami\Detector\DirectoryDetector;
use Ohanami\Collector\ServerCollector;
use Ohanami\Collector\WordPressCollector;
use Ohanami\Output\OutputFormatter;
use Ohanami\Network\Sender;

// コマンドライン引数解析
$optionParser = new OptionParser();
$options = $optionParser->parse($argv);

// ヘルプ表示
if ($options['help']) {
    $optionParser->showHelp();
    exit(0);
}

// 初期化メッセージ（出力がhuman形式の場合のみ）
if ($options['output'] === 'human') {
    echo "=== Ohanami Reporter v0.3.0 ===\n";
    echo "実行日時: " . date('Y-m-d H:i:s') . "\n";
    echo "実行場所: " . getcwd() . "\n\n";
}

try {
    // wp-cli検出
    $wpCliDetector = new WpCliDetector();
    $wpCliPath = $options['wp-cli'] ?? $wpCliDetector->detect();
    
    if (!$wpCliPath) {
        if ($options['output'] === 'human') {
            echo "⚠️  wp-cliが見つかりません。WordPress情報の収集をスキップします。\n\n";
        }
    }
    
    // スキャンディレクトリ検出
    $directoryDetector = new DirectoryDetector();
    $scanDirs = [];
    
    if ($options['scan-dir']) {
        $scanDirs = [$options['scan-dir']];
    } else {
        $detectedDir = $directoryDetector->detectBestWebRoot();
        if ($detectedDir) {
            $scanDirs = [$detectedDir];
        } else {
            if ($options['output'] === 'human') {
                echo "⚠️  Webルートディレクトリが見つかりません。\n\n";
            }
        }
    }
    
    // データ収集開始
    $reportData = [
        'report' => [
            'metadata' => [
                'version' => '0.3.0',
                'timestamp' => date('c'),
                'hostname' => gethostname() ?: 'unknown',
                'user' => $_SERVER['USER'] ?? get_current_user() ?? 'unknown',
                'working_directory' => getcwd() ?: 'unknown'
            ],
            'environment' => [],
            'wordpress' => ['sites' => []]
        ]
    ];
    
    // サーバー環境情報収集
    $serverCollector = new ServerCollector();
    $serverInfo = $serverCollector->collect();
    
    // 環境情報をJSON構造に変換
    $reportData['report']['environment'] = [
        'php' => [
            'version' => $serverInfo['PHP_VERSION'] ?? null,
            'sapi' => $serverInfo['PHP_SAPI'] ?? null
        ],
        'os' => $serverInfo['OS'] ?? null,
        'server_software' => $serverInfo['SERVER_SOFTWARE'] !== 'N/A' ? $serverInfo['SERVER_SOFTWARE'] : null,
        'mysql' => [
            'version' => $serverInfo['MYSQL_VERSION'] ?? null
        ],
        'wpcli' => [
            'version' => $wpCliPath ? $wpCliDetector->getVersion($wpCliPath) : null,
            'available' => $wpCliPath !== null,
            'path' => $wpCliPath
        ]
    ];
    
    // WordPress情報収集
    if ($wpCliPath && !empty($scanDirs)) {
        $wordpressCollector = new WordPressCollector($wpCliPath);
        $sites = $wordpressCollector->collectSites($scanDirs);
        $reportData['report']['wordpress']['sites'] = $sites;
        
        if ($options['output'] === 'human') {
            $siteCount = count($sites);
            if ($siteCount > 0) {
                echo "✅ $siteCount 個のWordPressサイトが見つかりました。\n\n";
            } else {
                echo "ℹ️  WordPressサイトが見つかりませんでした。\n\n";
            }
        }
    }
    
    // 出力フォーマット
    $output = OutputFormatter::format($reportData, $options['output']);
    if ($output !== '') {
        echo $output;
    }
    
    // 外部送信
    if ($options['send']) {
        $sender = new Sender(true);
        $success = $sender->send($reportData);
        
        if ($options['output'] === 'human') {
            if ($success) {
                echo "\n✅ データの送信が完了しました。\n";
            } else {
                echo "\n❌ データの送信に失敗しました。\n";
            }
        }
    }
    
    if ($options['output'] === 'human') {
        echo "\n=== 収集完了 ===\n";
        echo "情報収集が完了しました。\n";
    }
    
} catch (Exception $e) {
    if ($options['output'] === 'human') {
        echo "❌ エラーが発生しました: " . $e->getMessage() . "\n";
    } else {
        error_log("Ohanami Error: " . $e->getMessage());
    }
    exit(1);
}
