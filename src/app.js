import express from 'express';
import storageRoutes from './routes/storage.js';
import spreadsheetRoutes from './routes/spreadsheet.js';
import databaseRoutes from './routes/database.js';

// expressの初期化
const app = express();

// ミドルウェアの設定
app.use(express.json());

// ルートの設定
app.use(storageRoutes);
app.use(spreadsheetRoutes);
app.use(databaseRoutes);

export default app;
