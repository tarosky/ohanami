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

// スキャン対象のディレクトリ
$scan_dir = $home_dir . '/www/';
$install_dir_array = ohanami_scan_directory( $scan_dir );

$ohanami_results = [];
foreach ( $install_dir_array as $install_dir ) {
	$version = ohanami_get_wp_version( $install_dir );
	$plugin_list = ohanami_get_wp_plugin_list( $install_dir );
	$ohanami_results[] = [
		'path'      => $install_dir,
		'version'   => $version,
		'plugin'    => $plugin_list,
		'timestamp' => date('Y-m-d H:i:s'),
		];
}

$text_output = [];
foreach ( $ohanami_results as $result ) {
	$text_output[] = $result['path'] . ' => ' . $result['version'] . ' Plugin_list: ' . $result['plugin'];
}

// すでにファイルが存在していれば上書き、存在しない場合は新規作成する。
file_put_contents( $output_dir . $output_file, implode( PHP_EOL, $text_output ) );


/**
 * ディレクトリを再帰的にスキャンしてWordPressのインストールディレクトリを取得する
 *
 * @param $dir string スキャン対象ディレクトリ
 *
 * @return array
 */
function ohanami_scan_directory( string $dir ) {
	$install_dir_array = [];
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
				$install_dir_array[] = $sub;
			}
		} else {
			// ファイルがwp-load.phpかどうかチェックする
			if ( 'wp-load.php' === $file ) {
				// 同階層にwp-config.phpがあるかどうか wp-config.php は「一つ上の階層でもオッケー」というルールがあるためチェックする
				if ( file_exists( $dir . '/wp-config.php' ) || file_exists( dirname( $dir ) . '/wp-config.php' ) ) {
					// wp-load.phpとwp-config.phpが同階層もしくはwp-config.phpが一つ上の階層にあるならば、ここはWordPressのインストールディレクトリ
					$install_dir_array[] = $dir;
				}
			}
		}
	}

	return $install_dir_array;
}

/**
 * WordPressディレクトリでwp core versionコマンドを実行してバージョンを取得する
 *
 * @param string $dir WordPressディレクトリのパス
 * @return string WordPressのバージョン情報
 */
function ohanami_get_wp_version( string $dir ) {
	// エラーが出た場合は 2>&1 で標準エラー出力（stderr）を標準出力（stdout）にリダイレクトすることで $output に格納する
	$command = "cd " . escapeshellarg( $dir ) . " && wp core version 2>&1";
	// shell_exec()は引数＝コマンドをシェルで実行して標準出力を文字列として返す。失敗時はnullを返す
	$output = shell_exec( $command );

	if ( $output === null ) {
		return 'コマンド実行失敗';
	}

	return trim( $output );
}

/**
 * WordPressディレクトリでwp plugin listコマンドを実行してプラグインリストを取得する
 *
 * @param string $dir WordPressディレクトリのパス
 * @return array WordPressのプラグインリスト
 */
function ohanami_get_wp_plugin_list( string $dir ) {
	$command = "cd " . escapeshellarg( $dir ) . " && wp plugin list --format=json 2>&1";
	$output = shell_exec( $command );

	if ( $output === null ) {
		return [ 'error' => 'コマンド実行失敗' ];
	}

	return json_decode( trim( $output ), true );
}
