<?php
/**
 * app/orders 用設定（セキュリティ強化版）
 * 認証情報は環境変数から読み込み
 */

define('APP_ORDERS_ROOT', __DIR__);
// app/orders の2階層上 = プロジェクトルート（www/）
define('APP_ORDERS_PARENT', dirname(__DIR__, 2));

require_once APP_ORDERS_PARENT . '/inc/load_env.php';

// エラー表示：本番では無効化（環境変数で制御）
$display_errors = filter_var(getenv('DEBUG_DISPLAY_ERRORS'), FILTER_VALIDATE_BOOLEAN)
    || (getenv('APP_ENV') === 'development');
ini_set('display_errors', $display_errors ? 1 : 0);

$config['write_drive_flg'] = TRUE;
// デバッグモード：本番では無効化（環境変数で制御）
$config['debug_flg'] = filter_var(getenv('DEBUG_FLG'), FILTER_VALIDATE_BOOLEAN)
    || (getenv('APP_ENV') === 'development');

// テストモード：TRUE なら weaver-rakuten-test、FALSE なら weaver-rakuten を使用
$config['test_mode_flg'] = filter_var(getenv('DRIVE_TEST_MODE'), FILTER_VALIDATE_BOOLEAN)
    || (getenv('APP_ENV') === 'development');

// Google OAuth リダイレクト用ベースURL（空なら自動検出。redirect_uri_mismatch 時は明示指定を推奨）
// $config['google_redirect_base_url'] = 'https://weaver-ec.sakura.ne.jp/';

// 楽天API - 環境変数から読み込み（公開ディレクトリに置かない）
$license_key = '';
if (getenv('RAKUTEN_LICENSE_KEY')) {
    $license_key = preg_replace('/[^a-zA-Z0-9_]/', '', getenv('RAKUTEN_LICENSE_KEY'));
}
if ($license_key === '' && getenv('RAKUTEN_LICENSE_KEY_PATH')) {
    $license_key_path = trim((string) getenv('RAKUTEN_LICENSE_KEY_PATH'));
    if ($license_key_path !== '' && is_readable($license_key_path)) {
        $license_key = preg_replace('/[^a-zA-Z0-9_]/', '', file_get_contents($license_key_path));
    }
}
if ($license_key === '') {
    die('楽天ライセンスキーが取得できません。.env に RAKUTEN_LICENSE_KEY または RAKUTEN_LICENSE_KEY_PATH（DocumentRoot 外のファイル）を設定してください。');
}

$secret_key = getenv('RAKUTEN_SECRET_KEY') ?: '';
if (empty($secret_key)) {
    die('RAKUTEN_SECRET_KEY が環境変数に設定されていません。.env ファイルを確認してください。');
}
