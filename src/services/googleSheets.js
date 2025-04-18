import { google } from 'googleapis';
import config from '../config/config.js';

// Google認証設定
const getGoogleAuth = () => {
  const googleCredentials = Buffer.from(config.googleCredentialsBase64, 'base64').toString();

  return new google.auth.GoogleAuth({
    credentials: JSON.parse(googleCredentials),
    scopes: ['https://www.googleapis.com/auth/spreadsheets']
  });
};

// Google Sheetsクライアントの初期化
const getSheetsClient = () => {
  const auth = getGoogleAuth();
  return google.sheets({ version: 'v4', auth });
};

/**
 * スプレッドシートの情報を取得
 * @param {string} sheetName シート名
 * @returns {Promise<Object>} シート情報
 */
async function getSheetInfo(sheetName) {
  const sheets = getSheetsClient();
  const sheetBaseUrl = 'https://docs.google.com/spreadsheets/d/';

  // シート情報取得
  const spreadsheet = await sheets.spreadsheets.get({
    spreadsheetId: config.spreadsheetId
  });

  const targetSheet = spreadsheet.data.sheets.find(s => s.properties.title === sheetName);
  const sheetId = targetSheet ? targetSheet.properties.sheetId : null;

  return {
    sheetId,
    url: sheetId
      ? `${sheetBaseUrl}${config.spreadsheetId}/edit#gid=${sheetId}`
      : `${sheetBaseUrl}${config.spreadsheetId}`
  };
}

/**
 * スプレッドシートのヘッダーをチェックし、必要に応じて作成
 * @param {string} sheetName シート名
 * @param {Array<string>} expectedHeaders 期待されるヘッダー
 * @returns {Promise<boolean>} ヘッダーを新規作成したかどうか
 */
async function checkAndCreateHeader(sheetName, expectedHeaders) {
  const sheets = getSheetsClient();

  // ヘッダーが存在するかチェック
  const headerCheck = await sheets.spreadsheets.values.get({
    spreadsheetId: config.spreadsheetId,
    range: `${sheetName}!1:1` // 1行目だけを全て取得
  });

  const headerRow = headerCheck.data.values ? headerCheck.data.values[0] : [];
  const isHeaderMismatch =
    headerRow.length !== expectedHeaders.length ||
    !expectedHeaders.every((val, idx) => val === headerRow[idx]);

  if (!headerRow || headerRow.length === 0 || isHeaderMismatch) {
    // ヘッダーが存在しないか、想定と異なる場合は作成
    await sheets.spreadsheets.values.update({
      spreadsheetId: config.spreadsheetId,
      range: `${sheetName}!A1:${String.fromCharCode(65 + expectedHeaders.length - 1)}1`,
      valueInputOption: 'RAW',
      requestBody: {
        values: [expectedHeaders]
      }
    });
    console.log('✅ ヘッダーが存在しないため、1行目に追加しました');
    return true;
  }

  console.log('❌️ ヘッダーは既に存在しています');
  return false;
}

/**
 * スプレッドシートにデータを追加
 * @param {string} sheetName シート名
 * @param {Array<string>} keys データのキー
 * @param {Object} data 追加するデータ
 * @returns {Promise<void>}
 */
async function appendRowToSheet(sheetName, keys, data) {
  const sheets = getSheetsClient();
  const values = keys.map(key => data[key] || '');

  await sheets.spreadsheets.values.append({
    spreadsheetId: config.spreadsheetId,
    range: `${sheetName}!A1`,
    valueInputOption: 'RAW',
    requestBody: {
      values: [values]
    }
  });
}

export { getSheetInfo, checkAndCreateHeader, appendRowToSheet };
