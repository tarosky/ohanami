<?php
/**
 * さくらのレンタルサーバーのWordPress情報を出力する（ワンライナー実行用）
 *
 * プロトタイプ版：Pharアーカイブをダウンロードして実行
 *
 * @author TAROSKY
 * @version 0.2.0
 * @see https://github.com/tarosky/ohanami
 */

// GitHubのraw URLでPharファイルを取得
$pharUrl = 'https://raw.githubusercontent.com/tarosky/ohanami/main/reporter/ohanami.phar';
$tempFile = '/tmp/ohanami-' . uniqid() . '.phar';

echo "=== Ohanami ワンライナー実行 ===\n";
echo "Pharアーカイブをダウンロード中...\n";

// Pharファイルをダウンロード
$pharContent = file_get_contents($pharUrl);
if ($pharContent === false) {
    echo "❌ Pharファイルのダウンロードに失敗しました。\n";
    exit(1);
}

// 一時ファイルに保存
if (file_put_contents($tempFile, $pharContent) === false) {
    echo "❌ 一時ファイルの作成に失敗しました。\n";
    exit(1);
}

echo "✅ ダウンロード完了\n";
echo "実行中...\n\n";

// Pharファイルを実行
system("php {$tempFile}", $exitCode);

// 一時ファイルを削除
if (file_exists($tempFile)) {
    unlink($tempFile);
    echo "\n✅ 一時ファイルを削除しました。\n";
}

echo "終了コード: {$exitCode}\n";
exit($exitCode);
