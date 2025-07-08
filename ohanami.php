<?php
/**
 * さくらのレンタルサーバーのWordPress情報を出力する
 *
 * さくらの ~/www をスキャンして一覧を安全な場所にテキストファイルとして出力する
 *
 * @author TAROSKY
 * @version 0.1.0
 * @see https://github.com/tarosky/ohanami
 */

// ホームディレクトリを取得
$info = posix_getpwuid( posix_getuid() );
$home_dir = rtrim( $info['dir'], DIRECTORY_SEPARATOR );

// 出力先ディレクトリ もしもディレクトリを直で指定する場合、始まりが home/ だと失敗するので要注意。 正：/home/ 誤：home/
$output_dir = $home_dir . '/ohanami-data/';
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
 * ディレクトリを再帰的にスキャンしてWordPressのインストールディレクトリを取得する
 *
 * @param $dir string スキャン対象ディレクトリ
 *
 * @return array
 */
function ohanami_scan_directory( string $dir ) {
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
			// サブディレクトリを再帰的にスキャンする
			$sub_results = ohanami_scan_directory( $path );
			foreach ( $sub_results as $sub ) {
				$result[] = $sub;
			}
		} else {
			// ファイルがwp-load.phpかどうかチェックする
			if ( 'wp-load.php' === $file ) {
				// 同階層にwp-config.phpがあるかどうか wp-config.php は「一つ上の階層でもオッケー」というルールがあるためチェックする
				if ( file_exists( $dir . '/wp-config.php' ) || file_exists( dirname( $dir ) . '/wp-config.php' ) ) {
					// wp-load.phpとwp-config.phpが同階層もしくはwp-config.phpが一つ上の階層にあるならば、ここはWordPressのインストールディレクトリ
					$result[] = $dir;
				}
			}
		}
	}

	return $result;
}

// スキャン対象のディレクトリ
$scan_dir = $home_dir . '/www/';
$result = ohanami_scan_directory( $scan_dir );

// すでにファイルが存在していれば上書き、存在しない場合は新規作成する。
file_put_contents( $output_dir . $output_file, implode( PHP_EOL, $result ) );
