import app from './app.js';
import config from './config/config.js';

const PORT = config.port;

// サーバー起動
app.listen(PORT, () => {
  console.log(`Server is running on port ${PORT}`);
});
