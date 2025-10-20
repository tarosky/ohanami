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
            // デフォルトエンドポイント（新しいOhanami Cloud Runサービス）
            return 'https://ohanami-prod-1031617030980.asia-northeast1.run.app/api/wordpress-report';
        }
        
        return $endpoint;
    }
    
    /**
     * 認証トークン取得
     *
     * @return string
     */
    private function getAuthToken(): string
    {
        // 環境変数から認証トークンを取得
        return $_ENV['OHANAMI_AUTH_TOKEN'] ?? $_SERVER['OHANAMI_AUTH_TOKEN'] ?? 'dev-secret-key-change-in-production';
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
        $jsonData = json_encode($data);
        $authToken = $this->getAuthToken();
        
        // cURLでPOST送信
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $endpoint,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $jsonData,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($jsonData),
                'Authorization: Bearer ' . $authToken,
                'User-Agent: Ohanami-Reporter/1.0'
            ]
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            error_log(sprintf('[Ohanami] cURL Error: %s', $error));
            return false;
        }
        
        $success = $httpCode >= 200 && $httpCode < 300;
        
        // ログに送信結果を記録
        error_log(sprintf(
            '[Ohanami] %s to %s (HTTP %d, Size: %d bytes, Sites: %d)', 
            $success ? 'Successfully sent data' : 'Failed to send data',
            $endpoint,
            $httpCode,
            strlen($jsonData),
            count($data['report']['wordpress']['sites'] ?? [])
        ));
        
        if (!$success && $response) {
            error_log(sprintf('[Ohanami] Response: %s', $response));
        }
        
        return $success;
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
