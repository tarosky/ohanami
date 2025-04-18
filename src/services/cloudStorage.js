import { Storage } from '@google-cloud/storage';
import config from '../config/config.js';

// Cloud Storageクライアントの初期化
const getStorageClient = () => {
  const googleCredentials = Buffer.from(config.googleCredentialsBase64, 'base64').toString();

  return new Storage({
    credentials: JSON.parse(googleCredentials)
  });
};

/**
 * Cloud StorageにJSONデータを保存
 * @param {Object} data 保存するJSONデータ
 * @returns {Promise<string>} 保存したファイル名
 */
async function saveJsonToStorage(data) {
  const storage = getStorageClient();
  const bucketName = config.bucketName;
  const fileName = `ohanami_json_${Date.now()}.json`;
  const file = storage.bucket(bucketName).file(fileName);

  await file.save(JSON.stringify(data), {
    contentType: 'application/json'
  });

  console.log(`JSONデータがCloud Storageに保存されました: ${fileName}`);
  return fileName;
}

export { saveJsonToStorage };
