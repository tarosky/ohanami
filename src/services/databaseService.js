import mysql from 'mysql2/promise';
import config from '../config/config.js';

class DatabaseService {
  constructor() {
    this.pool = mysql.createPool({
      host: config.dbHost,
      port: config.dbPort,
      user: config.dbUser,
      password: config.dbPassword,
      database: config.dbName,
      waitForConnections: true,
      connectionLimit: 10,
      queueLimit: 0
    });
  }

  /**
   * サーバーを特定または新規作成
   * @param {Object} metadata - report.metadata
   * @returns {number} server_id
   */
  async findOrCreateServer(metadata) {
    const { hostname, user: username } = metadata;
    const serviceProvider = 'sakura'; // 当面固定

    // 既存サーバーを検索
    const [servers] = await this.pool.execute(
      'SELECT id FROM servers WHERE service_provider = ? AND hostname = ? AND username = ?',
      [serviceProvider, hostname, username]
    );

    if (servers.length > 0) {
      return servers[0].id;
    }

    // 新規サーバー作成
    const displayName = `${serviceProvider}:${hostname}:${username}`;
    const [result] = await this.pool.execute(
      `INSERT INTO servers 
       (service_provider, hostname, username, display_name, description) 
       VALUES (?, ?, ?, ?, ?)`,
      [serviceProvider, hostname, username, displayName, `自動作成: ${displayName}`]
    );

    return result.insertId;
  }

  /**
   * レポート情報を保存
   * @param {number} serverId
   * @param {Object} reportData - 完全なreporter.sample.json
   * @returns {number} report_id
   */
  async saveReport(serverId, reportData) {
    const { metadata, environment, wordpress } = reportData.report;

    const reportData_insert = {
      server_id: serverId,
      manager_id: 1, // デフォルト値

      // metadata情報
      ohanami_version: metadata.version,
      execution_time: new Date(metadata.timestamp),
      hostname: metadata.hostname,
      username: metadata.user,
      working_directory: metadata.working_directory,

      // environment情報
      php_version: environment.php?.version,
      php_sapi: environment.php?.sapi,
      os_name: environment.os,
      server_software: environment.server_software,
      mysql_version: environment.mysql?.version,
      wpcli_version: environment.wpcli?.version,
      wpcli_available: environment.wpcli?.available || false,
      wpcli_path: environment.wpcli?.path,

      sites_count: wordpress.sites?.length || 0,
      status: 'success'
    };

    const columns = Object.keys(reportData_insert).join(', ');
    const placeholders = Object.keys(reportData_insert).map(() => '?').join(', ');
    const values = Object.values(reportData_insert);

    const [result] = await this.pool.execute(
      `INSERT INTO reports (${columns}) VALUES (${placeholders})`,
      values
    );

    return result.insertId;
  }

  /**
   * WordPressサイト情報を保存
   * @param {number} reportId
   * @param {number} serverId
   * @param {Array} sites - wordpress.sites配列
   * @returns {Array} 保存されたサイト情報
   */
  async saveSites(reportId, serverId, sites) {
    const savedSites = [];

    for (const site of sites) {
      // サイト基本情報を保存（undefinedをnullに変換）
      const siteData = {
        report_id: reportId,
        server_id: serverId,
        manager_id: 1,
        site_path: site.path,
        database_version: site.database?.version || null,
        core_version: site.core?.version || null,
        is_multisite: site.core?.is_multisite || false,
        language: site.core?.language || 'unknown',
        core_error: site.core?.error || null,
        errors: JSON.stringify(site.errors || [])
      };

      const columns = Object.keys(siteData).join(', ');
      const placeholders = Object.keys(siteData).map(() => '?').join(', ');
      const values = Object.values(siteData);

      const [siteResult] = await this.pool.execute(
        `INSERT INTO wordpress_sites (${columns}) VALUES (${placeholders})`,
        values
      );

      const siteId = siteResult.insertId;

      // プラグイン情報を保存
      if (site.plugins && site.plugins.length > 0) {
        await this.savePlugins(siteId, site.plugins);
      }

      // テーマ情報を保存
      if (site.themes && site.themes.length > 0) {
        await this.saveThemes(siteId, site.themes);
      }

      savedSites.push({
        site_id: siteId,
        path: site.path,
        plugins_count: site.plugins?.length || 0,
        themes_count: site.themes?.length || 0
      });
    }

    return savedSites;
  }

  /**
   * プラグイン情報を保存
   * @param {number} siteId
   * @param {Array} plugins
   */
  async savePlugins(siteId, plugins) {
    if (!plugins || plugins.length === 0) return;

    const values = plugins.map(plugin => [
      siteId,
      plugin.name,
      plugin.version || null,
      plugin.status || 'inactive',
      plugin.update === 'available' ? 'available' : 'none',
      plugin.update === false ? false : true, // update_value
      plugin.auto_update || 'off'
    ]);

    await this.pool.query(
      `INSERT INTO plugins 
       (site_id, name, version, status, update_status, update_value, auto_update) 
       VALUES ?`,
      [values]
    );
  }

  /**
   * テーマ情報を保存
   * @param {number} siteId
   * @param {Array} themes
   */
  async saveThemes(siteId, themes) {
    if (!themes || themes.length === 0) return;

    const values = themes.map(theme => [
      siteId,
      theme.name,
      theme.version || null,
      theme.status || 'inactive',
      theme.update === 'available' ? 'available' : 'none',
      theme.auto_update || 'off'
    ]);

    await this.pool.query(
      `INSERT INTO themes 
       (site_id, name, version, status, update_status, auto_update) 
       VALUES ?`,
      [values]
    );
  }

  /**
   * WordPressレポートの完全な保存処理
   * @param {Object} reportData - 完全なreporter.sample.json
   * @returns {Object} 保存結果
   */
  async saveWordPressReport(reportData) {
    const connection = await this.pool.getConnection();

    try {
      await connection.beginTransaction();

      // 1. サーバー特定・作成
      const serverId = await this.findOrCreateServer(reportData.report.metadata);

      // 2. レポート保存
      const reportId = await this.saveReport(serverId, reportData);

      // 3. サイト情報保存
      const sites = await this.saveSites(reportId, serverId, reportData.report.wordpress.sites);

      await connection.commit();

      return {
        success: true,
        reportId,
        serverId,
        serverInfo: `${reportData.report.metadata.hostname}:${reportData.report.metadata.user}`,
        sitesCount: sites.length,
        sites
      };

    } catch (error) {
      await connection.rollback();
      throw error;
    } finally {
      connection.release();
    }
  }

  /**
   * 最新のレポート一覧を取得（デバッグ用）
   * @param {number} limit
   * @returns {Array}
   */
  async getLatestReports(limit = 10) {
    const limitValue = parseInt(limit) || 10;
    
    // レポートが存在しない場合の対応
    const [reports] = await this.pool.execute(
      `SELECT 
         r.id, r.execution_time, r.sites_count, r.status,
         s.hostname, s.username, s.display_name
       FROM reports r
       JOIN servers s ON r.server_id = s.id
       ORDER BY r.execution_time DESC
       LIMIT ?`,
      [limitValue]
    );

    return reports;
  }

  /**
   * コネクションプールを閉じる
   */
  async close() {
    await this.pool.end();
  }
}

// シングルトンとしてエクスポート
const databaseService = new DatabaseService();
export default databaseService;
