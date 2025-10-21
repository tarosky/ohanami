import express from 'express';
import { authenticateToken } from '../middleware/auth.js';
import databaseService from '../services/databaseService.js';
import { saveJsonToStorage } from '../services/cloudStorage.js';
import { sendSlackNotification } from '../services/notification.js';

const router = express.Router();

/**
 * WordPressレポートをCloud SQLに保存するエンドポイント
 * reporter.sample.json形式のデータを受信してデータベースに保存
 */
router.post('/api/wordpress-report', authenticateToken, async (req, res) => {
  const reportData = req.body;
  
  try {
    // リクエストデータの基本検証
    if (!reportData.report || !reportData.report.metadata || !reportData.report.wordpress) {
      return res.status(400).json({
        error: '無効なレポート形式です',
        expected: 'report.metadata, report.wordpress が必要です'
      });
    }

    // データベースに保存
    const result = await databaseService.saveWordPressReport(reportData);

    // Slack通知（既存の関数を再利用）
    const notificationMessage = 
      `WordPress健康レポートを保存しました:\n` +
      `サーバー: ${result.serverInfo}\n` +
      `サイト数: ${result.sitesCount}\n` +
      `レポートID: ${result.reportId}`;

    if (process.env.SLACK_WEBHOOK_URL) {
      await sendSlackNotification(notificationMessage);
    }

    // Cloud Storageバックアップ（既存の関数を再利用）
    let storageFileName = null;
    if (process.env.BUCKET_NAME) {
      try {
        storageFileName = await saveJsonToStorage(reportData);
      } catch (storageError) {
        console.warn('Cloud Storageバックアップに失敗:', storageError.message);
        // Storageエラーは致命的でないため処理続行
      }
    }

    // 成功レスポンス
    res.status(200).json({
      success: true,
      message: 'WordPressレポートをデータベースに保存しました',
      data: {
        reportId: result.reportId,
        serverId: result.serverId,
        serverInfo: result.serverInfo,
        sitesCount: result.sitesCount,
        sites: result.sites,
        storageBackup: storageFileName ? true : false
      }
    });

  } catch (error) {
    console.error('❌️ データベース保存エラー:', error);

    // エラー通知
    const errorMessage = 
      `WordPress健康レポート保存に失敗しました:\n` +
      `エラー: ${error.message}\n` +
      `サーバー: ${reportData.report?.metadata?.hostname || 'unknown'}:${reportData.report?.metadata?.user || 'unknown'}`;

    if (process.env.SLACK_WEBHOOK_URL) {
      try {
        await sendSlackNotification(errorMessage);
      } catch (notificationError) {
        console.error('Slack通知エラー:', notificationError.message);
      }
    }

    res.status(500).json({
      success: false,
      error: 'データベース保存に失敗しました',
      message: error.message
    });
  }
});

/**
 * 最新のレポート一覧を取得するエンドポイント（デバッグ用）
 */
router.get('/api/reports', authenticateToken, async (req, res) => {
  try {
    const limit = parseInt(req.query.limit) || 10;
    const reports = await databaseService.getLatestReports(limit);

    res.status(200).json({
      success: true,
      data: reports,
      count: reports.length
    });

  } catch (error) {
    console.error('❌️ レポート取得エラー:', error);
    res.status(500).json({
      success: false,
      error: 'レポート取得に失敗しました',
      message: error.message
    });
  }
});

/**
 * データベース接続テスト用エンドポイント（デバッグ用）
 */
router.get('/api/health/database', authenticateToken, async (req, res) => {
  try {
    // 簡単なクエリでDB接続をテスト
    const reports = await databaseService.getLatestReports(1);
    
    res.status(200).json({
      success: true,
      message: 'データベース接続OK',
      database: 'MySQL',
      latestReportsCount: reports.length
    });

  } catch (error) {
    console.error('❌️ データベース接続エラー:', error);
    res.status(500).json({
      success: false,
      error: 'データベース接続に失敗しました',
      message: error.message
    });
  }
});

export default router;
