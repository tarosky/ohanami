<?php

namespace Ohanami\Network;

/**
 * 外部送信機能（スタブ実装）
 */
class Sender
{
    private bool $enabled;
    
    public function __construct(bool $enabled = true)
    {
        $this->enabled = $enabled;
    }
    
    /**
     * データを外部エンドポイントに送信
     *
     * @param array $data
     * @return bool
     */
    public function send(array $data): bool
    {
        if (!$this->enabled) {
            return true; // 送信無効時は成功として扱う
        }
        
        // TODO: 将来的にCloud Run APIエンドポイントに送信
        $endpoint = $this->getEndpointUrl();
        
        if (!$endpoint) {
            // エンドポイントが設定されていない場合はスキップ
            return true;
        }
        
        return $this->sendToEndpoint($endpoint, $data);
    }
    
    /**
     * エンドポイントURL取得
     *
     * @return string|null
     */
    private function getEndpointUrl(): ?string
    {
        // 環境変数からエンドポイントURLを取得
        $endpoint = $_ENV['OHANAMI_ENDPOINT'] ?? $_SERVER['OHANAMI_ENDPOINT'] ?? null;
        
        if (!$endpoint) {
            // デフォルトエンドポイント（未実装）
            return null; // 'https://ohanami-api.example.com/report';
        }
        
        return $endpoint;
    }
    
    /**
     * エンドポイントにデータ送信
     *
     * @param string $endpoint
     * @param array $data
     * @return bool
     */
    private function sendToEndpoint(string $endpoint, array $data): bool
    {
        // スタブ実装：実際の送信は行わない
        // TODO: 将来的にcURLまたはHTTPクライアントでPOST送信
        
        // ログに送信予定のデータ情報を記録（デバッグ用）
        error_log(sprintf(
            '[Ohanami] Would send data to %s (Size: %d bytes, Sites: %d)', 
            $endpoint,
            strlen(json_encode($data)),
            count($data['report']['wordpress']['sites'] ?? [])
        ));
        
        return true; // スタブなので常に成功
    }
    
    /**
     * 送信のテスト（接続チェック）
     *
     * @return bool
     */
    public function testConnection(): bool
    {
        if (!$this->enabled) {
            return true;
        }
        
        $endpoint = $this->getEndpointUrl();
        if (!$endpoint) {
            return false; // エンドポイント未設定
        }
        
        // TODO: 将来的にHealth Checkエンドポイントに接続テスト
        // 現在はスタブなので常にtrue
        return true;
    }
    
    /**
     * 送信統計情報の取得（将来拡張用）
     *
     * @return array
     */
    public function getStats(): array
    {
        return [
            'enabled' => $this->enabled,
            'endpoint' => $this->getEndpointUrl(),
            'last_send_time' => null, // TODO: 実装時に追加
            'total_sends' => 0,       // TODO: 実装時に追加
            'success_rate' => 1.0     // スタブなので100%
        ];
    }
}
