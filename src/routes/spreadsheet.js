import express from 'express';
import { authenticateToken } from '../middleware/auth.js';
import { getSheetInfo, checkAndCreateHeader, appendRowToSheet } from '../services/googleSheets.js';
import { saveJsonToStorage } from '../services/cloudStorage.js';
import { sendSlackNotification } from '../services/notification.js';

const router = express.Router();

/**
 * JSONデータを元に、Googleスプレッドシートを更新するエンドポイント
 */
router.post('/update-sheet', authenticateToken, async (req, res) => {
  const { data } = req.body;
  // NOTE: シート名は仮で文字列を指定、ヘッダーも仮で設定している
  const sheetName = 'ItoTest';
  const expectedKeys = [
    'プラグイン名',
    'バージョン情報',
    '著者名',
    '最終更新日',
    '変更履歴',
    'URL'
  ];

  try {
    // シート情報取得
    const sheetInfo = await getSheetInfo(sheetName);

    // ヘッダーをチェックして必要なら作成
    await checkAndCreateHeader(sheetName, expectedKeys);

    // スプレッドシートにデータ追加
    await appendRowToSheet(sheetName, expectedKeys, data);

    // Slack通知
    await sendSlackNotification(`スプレッドシートを更新しました: ${sheetInfo.url}`);

    // Cloud StorageにJSONデータを保存
    const fileName = await saveJsonToStorage(req.body);

    // クライアントに最終レスポンスを返す
    res.status(200).send('スプレッドシート＆Cloud Storageに保存しました');
  } catch (error) {
    console.error('❌️ エラーが発生しました:', error);

    await sendSlackNotification(
      `スプレッドシート or Cloud Storage 更新に失敗しました: ${error.message}`
    );

    res.status(500).send('スプレッドシート or Cloud Storage 更新に失敗しました');
  }
});

export default router;
