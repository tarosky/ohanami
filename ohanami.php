<?php
/**
 * さくらのレンタルサーバーのWordPress情報を出力する
 *
 * ここに説明
 *
 * @author TAROSKY
 * @version 0.1.0
 * @see https://github.com/tarosky/ohanami
 */

// さくらのレンタルサーバーのアカウント名を取得する
$account_name = exec( 'whoami' );

// 出力先ディレクトリ
$output_dir = 'home/' . $account_name . '/ohanami-data/';
// 出力先ファイル
$output_file = 'ohanami-result.txt';

// 出力先ディレクトリが存在しない場合には作成し、作成に失敗したら以降のコードは実行されない。
if ( ! is_dir( $output_dir ) && ! mkdir( $output_dir, 0700, true ) ) {
	error_log( $output_dir . 'の作成に失敗しました' );
	exit;
}

// 念のため出力先ディレクトリのパーミッションを明示的に設定。すでに0700ならばchmod()はtrueを返すので何もしない
if ( ! chmod( $output_dir, 0700 ) ) {
	error_log( $output_dir . 'に適切なパーミッションを設定することができませんでした' );
	exit;
}

/**
 * ディレクトリを再帰的にスキャンしファイルの名前を取得する
 *
 * @param $dir string スキャン対象ディレクトリ
 *
 * @return array
 */
function ohanami_scan_directory( $dir ) {
	$result = [];
	// 末尾にスラッシュがついていたら後続の処理でスラッシュが重複するので取り除く
	$dir = rtrim( $dir, '/' );
	$files = scandir( $dir );

	foreach ( $files as $file ) {
		// ファイル名が「.」「..」の場合はスキップ
		if ( $file === '.' || $file === '..' ) {
			continue;
		}

		$path = $dir . '/' . $file;
		if ( is_dir( $path ) ) {
			// ディレクトリならば末尾に / をつけて格納する
			$result[] = $path . '/';
			// サブディレクトリを再帰的にスキャンする
			$sub_results = ohanami_scan_directory( $path );
			$result = array_merge( $result, $sub_results );
		} else {
			$result[] = $path;
		}
	}

	return $result;
}

// スキャン対象のディレクトリ
$scan_dir = 'home/' . $account_name . '/www/';
$result = ohanami_scan_directory( $scan_dir );
// すでにファイルが存在していれば上書き、存在しない場合は新規作成する。
file_put_contents( $output_dir . $output_file, implode( PHP_EOL, $result ) );

