<?php
/**
 * app/orders 用: Google OAuth トークンファイルの絶対パスを返す。
 * .env の TOKEN_STORAGE_PATH を優先（Web 公開ディレクトリ外を推奨）。
 * 未設定時は従来どおり app/orders 直下。
 */
function weaver_google_token_path() {
	$token_file = isset($_SESSION['user_name']) ? 'token_' . $_SESSION['user_name'] . '.json' : 'token.json';
	$dir = getenv('TOKEN_STORAGE_PATH');
	if ($dir !== false && $dir !== '') {
		return rtrim($dir, "/\\") . '/' . $token_file;
	}
	return (defined('APP_ORDERS_ROOT') ? APP_ORDERS_ROOT : __DIR__) . '/' . $token_file;
}
