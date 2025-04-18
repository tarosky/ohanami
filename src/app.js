import express from 'express';
import storageRoutes from './routes/storage.js';
import spreadsheetRoutes from './routes/spreadsheet.js';

// expressの初期化
const app = express();

// ミドルウェアの設定
app.use(express.json());

// ルートの設定
app.use(storageRoutes);
app.use(spreadsheetRoutes);

export default app;
