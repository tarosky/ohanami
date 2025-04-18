// import jwt from 'jsonwebtoken';
import config from '../config/config.js';

/**
 * トークン認証ミドルウェア
 * リクエストヘッダーから認証トークンを検証します
 */
function authenticateToken(req, res, next) {
  const token = req.headers['authorization'];
  const expected = `Bearer ${config.authSecret}`;

  // 単純なBearer token認証
  if (token !== expected) {
    return res.status(403).json({ error: '無効なトークンです' });
  }

  // Next関数を呼び出して、次のミドルウェアまたはルートハンドラに進む
  next();

  /*
  // TODO : 将来的なJWT実装のためのコメントアウト部分
  if (!token) return res.status(401).json({ error: "認証トークンが必要です" });
  jwt.verify(token, config.authSecret, (err, user) => {
    if (err) return res.status(403).json({ error: "無効なトークンです" });
    req.user = user;
    next();
  });
  */
}

export { authenticateToken };
