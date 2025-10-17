#!/usr/bin/env php
<?php
/**
 * ohanami - WordPress monitoring tool for rental servers
 *
 * @author TAROSKY
 * @version 0.2.0
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

use Ohanami\Collector\ServerCollector;

// プロトタイプ版: PHPバージョン検出のテスト
echo "=== Ohanami Reporter (Prototype) ===\n";
echo "実行日時: " . date('Y-m-d H:i:s') . "\n";
echo "実行場所: " . getcwd() . "\n\n";

// サーバー情報収集
$serverCollector = new ServerCollector();
$serverInfo = $serverCollector->collect();

// 結果表示
echo "=== サーバー情報 ===\n";
foreach ($serverInfo as $key => $value) {
    echo sprintf("%-15s: %s\n", $key, $value);
}

echo "\n=== 収集完了 ===\n";
echo "プロトタイプ版での情報収集が完了しました。\n";
