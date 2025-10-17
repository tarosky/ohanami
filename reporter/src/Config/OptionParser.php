<?php

namespace Ohanami\Config;

/**
 * コマンドライン引数解析クラス
 */
class OptionParser
{
    private array $options = [];
    
    /**
     * デフォルトオプション
     */
    private array $defaults = [
        'output' => 'none',        // none, json, human
        'send' => true,            // 外部送信フラグ
        'scan-dir' => null,        // スキャンディレクトリ（nullの場合は自動判別）
        'wp-cli' => null,          // wp-cliパス（nullの場合は自動判別）
        'help' => false,           // ヘルプ表示
    ];
    
    /**
     * 引数解析
     *
     * @param array $argv
     * @return array
     */
    public function parse(array $argv): array
    {
        $this->options = $this->defaults;
        
        // スクリプト名を除去
        array_shift($argv);
        
        for ($i = 0; $i < count($argv); $i++) {
            $arg = $argv[$i];
            
            if (str_starts_with($arg, '-')) {
                $i += $this->parseOption($arg, $argv, $i);
            }
        }
        
        return $this->options;
    }
    
    /**
     * オプション解析
     *
     * @param string $arg
     * @param array $argv
     * @param int $index
     * @return int 次にスキップする引数数
     */
    private function parseOption(string $arg, array $argv, int $index): int
    {
        if ($arg === '--help' || $arg === '-h') {
            $this->options['help'] = true;
            return 0;
        }
        
        // 値付きオプション解析
        if (str_contains($arg, '=')) {
            [$key, $value] = explode('=', $arg, 2);
            $this->setOptionValue($key, $value);
            return 0;
        } else {
            // 次の引数が値かチェック
            $nextIndex = $index + 1;
            if ($nextIndex < count($argv) && !str_starts_with($argv[$nextIndex], '-')) {
                // 次の引数が値
                $this->setOptionValue($arg, $argv[$nextIndex]);
                return 1; // 次の引数をスキップ
            } else {
                // フラグオプション（値なし）
                $this->setOptionValue($arg, true);
                return 0;
            }
        }
    }
    
    /**
     * オプション値設定
     *
     * @param string $key
     * @param mixed $value
     */
    private function setOptionValue(string $key, mixed $value): void
    {
        // ショートオプション変換
        $key = match($key) {
            '-o' => '--output',
            '-s' => '--send',  
            '-d' => '--scan-dir',
            '-w' => '--wp-cli',
            '-h' => '--help',
            default => $key
        };
        
        // -- を除去
        $key = ltrim($key, '-');
        
        switch ($key) {
            case 'output':
                if (in_array($value, ['none', 'json', 'human'])) {
                    $this->options['output'] = $value;
                }
                break;
                
            case 'send':
                $this->options['send'] = $this->parseBool($value);
                break;
                
            case 'scan-dir':
                $this->options['scan-dir'] = $value;
                break;
                
            case 'wp-cli':
                $this->options['wp-cli'] = $value;
                break;
        }
    }
    
    /**
     * 真偽値解析
     *
     * @param mixed $value
     * @return bool
     */
    private function parseBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        
        $value = strtolower((string)$value);
        return in_array($value, ['true', '1', 'yes', 'on'], true);
    }
    
    /**
     * ヘルプメッセージ表示
     */
    public function showHelp(): void
    {
        echo <<<HELP
Ohanami - WordPress monitoring tool for rental servers

使用方法: php ohanami.phar [OPTIONS]

オプション:
  -o, --output=FORMAT    出力形式: json|human|none (デフォルト: none)
  -s, --send=BOOL       外部送信: true|false (デフォルト: true)
  -d, --scan-dir=PATH   スキャンディレクトリ指定 (デフォルト: 自動判別)
  -w, --wp-cli=PATH     wp-cliバイナリパス (デフォルト: 自動判別)
  -h, --help           このヘルプを表示

使用例:
  php ohanami.phar                              # 出力なし、送信あり（本番用）
  php ohanami.phar -o human -s false           # 人間用表示、送信なし（開発用）
  php ohanami.phar -o json                     # JSON出力、送信あり
  php ohanami.phar -d ~/public_html             # 明示的なディレクトリ指定
  php ohanami.phar -w /usr/local/bin/wp-cli.phar  # wp-cli パス指定

HELP;
    }
}
