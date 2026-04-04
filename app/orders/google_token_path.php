<?php
/**
 * app/orders 用: Google OAuth トークンファイルの絶対パスを返す。
 * .env の TOKEN_STORAGE_PATH を優先（例: /home/weaver-ec/storage/secure/tokens）。
 * 未設定時は従来どおり app/orders 直下。
 *
 * ファイル内容は JSON（access_token, refresh_token, expires_at ほか）。
 * 本番では public 配下に置かず、TOKEN_STORAGE_PATH で DocumentRoot 外を指定すること。
 */
function weaver_google_token_path() {
	$token_file = isset($_SESSION['user_name']) ? 'token_' . $_SESSION['user_name'] . '.json' : 'token.json';
	$dir = getenv('TOKEN_STORAGE_PATH');
	if ($dir !== false && $dir !== '') {
		return rtrim($dir, "/\\") . '/' . $token_file;
	}
	return (defined('APP_ORDERS_ROOT') ? APP_ORDERS_ROOT : __DIR__) . '/' . $token_file;
}

/**
 * 現在のセッションに対応する Google トークンファイルを削除する（存在しなければ何もしない）。
 */
function weaver_delete_google_token_file() {
	$path = weaver_google_token_path();
	if (is_file($path)) {
		@unlink($path);
	}
}
