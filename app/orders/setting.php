<?php
/**
 * app/orders 用設定（セキュリティ強化版）
 * 認証情報は環境変数から読み込み
 */

define('APP_ORDERS_ROOT', __DIR__);
// app/orders の2階層上 = プロジェクトルート（www/）
define('APP_ORDERS_PARENT', dirname(__DIR__, 2));

// .env ファイルの読み込み（プロジェクトルートの .env）
$env_file = APP_ORDERS_PARENT . '/.env';
if (file_exists($env_file)) {
    foreach (file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value, " \t\n\r\0\x0B\"'");
            putenv($name . '=' . $value);
            $_ENV[$name] = $value;
        }
    }
}

// エラー表示：本番では無効化（環境変数で制御）
$display_errors = filter_var(getenv('DEBUG_DISPLAY_ERRORS'), FILTER_VALIDATE_BOOLEAN)
    || (getenv('APP_ENV') === 'development');
ini_set('display_errors', $display_errors ? 1 : 0);

$config['write_drive_flg'] = TRUE;
// デバッグモード：本番では無効化（環境変数で制御）
$config['debug_flg'] = filter_var(getenv('DEBUG_FLG'), FILTER_VALIDATE_BOOLEAN)
    || (getenv('APP_ENV') === 'development');

// Google OAuth リダイレクト用ベースURL（空なら自動検出。redirect_uri_mismatch 時は明示指定を推奨）
// $config['google_redirect_base_url'] = 'https://weaver-ec.sakura.ne.jp/';

// 楽天API - 環境変数から読み込み（ハードコードしない）
$license_key = '';
if (getenv('RAKUTEN_LICENSE_KEY')) {
    $license_key = preg_replace('/[^a-zA-Z0-9_]/', '', getenv('RAKUTEN_LICENSE_KEY'));
}
if (empty($license_key)) {
    $license_key_file = APP_ORDERS_ROOT . '/data/license_key.txt';
    if (!file_exists($license_key_file)) {
        $license_key_file = APP_ORDERS_PARENT . '/data/license_key.txt';
    }
    if (file_exists($license_key_file)) {
        $license_key = preg_replace('/[^a-zA-Z0-9_]/', '', file_get_contents($license_key_file));
    }
}

$secret_key = getenv('RAKUTEN_SECRET_KEY') ?: '';
if (empty($secret_key)) {
    die('RAKUTEN_SECRET_KEY が環境変数に設定されていません。.env ファイルを確認してください。');
}
