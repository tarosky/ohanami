import axios from 'axios';
import config from '../config/config.js';

/**
 * Slackに通知を送信
 * @param {string} message 送信するメッセージ
 * @returns {Promise<void>}
 */
async function sendSlackNotification(message) {
  await axios.post(config.slackWebhookUrl, {
    text: message
  });
}

export { sendSlackNotification };
