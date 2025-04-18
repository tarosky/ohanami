import express from 'express';
import { authenticateToken } from '../middleware/auth.js';
import { saveJsonToStorage } from '../services/cloudStorage.js';
import { sendSlackNotification } from '../services/notification.js';

const router = express.Router();
/**
 * Cloud StorageにJSONデータを保存するエンドポイント
 */
router.post('/store-json', authenticateToken, async (req, res) => {
  try {
    const fileName = await saveJsonToStorage(req.body);

    // 成功したら、Slackに通知
    await sendSlackNotification(`JSONデータがCloud Storageに保存されました: ${fileName}`);

    res.status(200).send(`JSONデータがCloud Storageに保存されました: ${fileName}`);
  } catch (error) {
    console.error('Cloud Storage保存エラー:', error);
    res.status(500).send('Cloud Storageへの保存に失敗しました。');
  }
});

export default router;
