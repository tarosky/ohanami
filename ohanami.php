<?php
/**
 * さくらのレンタルサーバーのWordPress情報を出力する（ワンライナー実行用）
 *
 * 本番版：GitHub Releasesから最新のPharアーカイブをダウンロードして実行
 *
 * @author TAROSKY
 * @version 1.0.0
 * @see https://github.com/tarosky/ohanami
 */

echo "=== Ohanami ワンライナー実行 ===\n";
echo "最新リリース情報を取得中...\n";

// GitHub Releases APIから最新リリースを取得
$releasesApiUrl = 'https://api.github.com/repos/tarosky/ohanami/releases/latest';
$context = stream_context_create([
    'http' => [
        'header' => 'User-Agent: ohanami-php-client',
        'timeout' => 30
    ]
]);

$releaseJson = file_get_contents($releasesApiUrl, false, $context);
if ($releaseJson === false) {
    echo "❌ GitHub Releases APIへのアクセスに失敗しました。\n";
    echo "フォールバック: ブランチ版を使用します...\n";
    $pharUrl = 'https://raw.githubusercontent.com/tarosky/ohanami/feature/retrieve-wordpress/reporter/ohanami.phar';
} else {
    $release = json_decode($releaseJson, true);
    if (!$release || empty($release['assets'])) {
        echo "❌ リリース情報の解析に失敗しました。\n";
        echo "フォールバック: ブランチ版を使用します...\n";
        $pharUrl = 'https://raw.githubusercontent.com/tarosky/ohanami/feature/retrieve-wordpress/reporter/ohanami.phar';
    } else {
        // ohanami.pharアセットを検索
        $pharAsset = null;
        foreach ($release['assets'] as $asset) {
            if ($asset['name'] === 'ohanami.phar') {
                $pharAsset = $asset;
                break;
            }
        }
        
        if (!$pharAsset) {
            echo "❌ PHARファイルが見つかりませんでした。\n";
            echo "フォールバック: ブランチ版を使用します...\n";
            $pharUrl = 'https://raw.githubusercontent.com/tarosky/ohanami/feature/retrieve-wordpress/reporter/ohanami.phar';
        } else {
            $pharUrl = $pharAsset['browser_download_url'];
            echo "✅ 最新リリース " . $release['tag_name'] . " を使用します。\n";
        }
    }
}

$tempFile = '/tmp/ohanami-' . uniqid() . '.phar';

echo "Pharアーカイブをダウンロード中...\n";

// Pharファイルをダウンロード
$pharContent = file_get_contents($pharUrl, false, $context);
if ($pharContent === false) {
    echo "❌ Pharファイルのダウンロードに失敗しました。\n";
    echo "URL: $pharUrl\n";
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
