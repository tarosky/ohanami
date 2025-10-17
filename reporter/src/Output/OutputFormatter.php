<?php

namespace Ohanami\Output;

/**
 * 出力フォーマッター（統合型）
 */
class OutputFormatter
{
    /**
     * 指定形式での出力フォーマット
     *
     * @param array $data
     * @param string $format
     * @return string
     */
    public static function format(array $data, string $format): string
    {
        return match($format) {
            'json' => self::formatJson($data),
            'human' => self::formatHuman($data),
            'none' => '',
            default => self::formatHuman($data)
        };
    }
    
    /**
     * JSON形式での出力
     *
     * @param array $data
     * @return string
     */
    private static function formatJson(array $data): string
    {
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    
    /**
     * 人間が読みやすい形式での出力
     *
     * @param array $data
     * @return string
     */
    private static function formatHuman(array $data): string
    {
        $output = '';
        
        // 1. ルートプロパティでループ
        foreach ($data['report'] as $section => $content) {
            if ($section === 'wordpress') {
                // 3. WordPressはフラット表記
                $output .= self::formatWordPressSection($content);
            } else {
                // 2. wordpress以外はテーブル表記
                $output .= self::formatTableSection($section, $content);
            }
        }
        
        return $output;
    }
    
    /**
     * テーブル形式でのセクション表示
     *
     * @param string $title
     * @param array $data
     * @return string
     */
    private static function formatTableSection(string $title, array $data): string
    {
        $output = "\n=== " . ucfirst($title) . " ===\n";
        
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if (is_array($value)) {
                    // ネストしたデータの場合
                    $output .= sprintf("%-15s: %s\n", $key, self::formatNestedValue($value));
                } else {
                    $output .= sprintf("%-15s: %s\n", $key, $value ?? 'N/A');
                }
            }
        }
        
        return $output;
    }
    
    /**
     * ネストした値のフォーマット
     *
     * @param mixed $value
     * @return string
     */
    private static function formatNestedValue($value): string
    {
        if (is_array($value)) {
            // シンプルな配列の場合は横に並べる
            if (self::isSimpleArray($value)) {
                return implode(', ', array_map(fn($v) => is_scalar($v) ? (string)$v : 'Array', $value));
            }
            // 複雑な配列の場合はJSONで表示
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }
        
        return (string)$value;
    }
    
    /**
     * 配列がシンプルかチェック（スカラー値のみ）
     *
     * @param array $array
     * @return bool
     */
    private static function isSimpleArray(array $array): bool
    {
        foreach ($array as $value) {
            if (!is_scalar($value) && $value !== null) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * WordPressセクションのフラット表記
     *
     * @param array $wordpressData
     * @return string
     */
    private static function formatWordPressSection(array $wordpressData): string
    {
        $output = "\n=== WordPress Sites ===\n";
        
        if (empty($wordpressData['sites'])) {
            $output .= "WordPressサイトが見つかりませんでした。\n";
            return $output;
        }
        
        foreach ($wordpressData['sites'] as $index => $site) {
            $output .= "\n--- Site " . ($index + 1) . " ---\n";
            $output .= "Path: " . ($site['path'] ?? 'Unknown') . "\n";
            
            // コア情報
            if (isset($site['core'])) {
                $output .= "WordPress: " . ($site['core']['version'] ?? 'Unknown');
                if ($site['core']['is_multisite'] ?? false) {
                    $output .= " (Multisite)";
                }
                $output .= "\n";
                $output .= "Language: " . ($site['core']['language'] ?? 'Unknown') . "\n";
            }
            
            // データベース情報
            if (isset($site['database']['version'])) {
                $output .= "Database: " . $site['database']['version'] . "\n";
            }
            
            // プラグイン情報
            if (!empty($site['plugins'])) {
                $output .= "Plugins: " . count($site['plugins']) . " installed\n";
                foreach ($site['plugins'] as $plugin) {
                    $status = $plugin['status'] === 'active' ? '✓' : '○';
                    $update = $plugin['update'] !== 'none' ? ' [UPDATE]' : '';
                    $output .= "  $status " . $plugin['name'] . " (" . ($plugin['version'] ?? 'Unknown') . ")$update\n";
                }
            }
            
            // テーマ情報
            if (!empty($site['themes'])) {
                $activeThemes = array_filter($site['themes'], fn($theme) => $theme['status'] === 'active');
                $output .= "Themes: " . count($site['themes']) . " installed";
                if (!empty($activeThemes)) {
                    $activeTheme = reset($activeThemes);
                    $output .= " (Active: " . $activeTheme['name'] . ")";
                }
                $output .= "\n";
            }
        }
        
        return $output;
    }
}
