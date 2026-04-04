<?php
/**
 * 共通認証モジュール
 * - セッションタイムアウト: 7日
 * - 管理者: update_license.php のみ（ADMIN_USERNAME / ADMIN_PASSWORD）
 * - 一般ユーザ: index.php, order_book.php, sample_order_book.php（USER_USERNAME / USER_PASSWORD）
 * - 一般ユーザ 7 日タイムアウト後の再ログイン時: Google トークン JSON を削除（login.php）
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('AUTH_SESSION_TIMEOUT', 604800); // 7日
/** 一般ユーザが 7 日タイムアウトしたあと、次回ログイン成功時に Google トークン JSON を削除する */
define('SESSION_PENDING_GOOGLE_TOKEN_PURGE', 'pending_google_token_purge_after_user_timeout');

// セッションタイムアウトチェック
if (!empty($_SESSION['admin_login_time']) && (time() - $_SESSION['admin_login_time']) > AUTH_SESSION_TIMEOUT) {
    unset($_SESSION['admin_authenticated'], $_SESSION['admin_login_time']);
}
if (!empty($_SESSION['user_login_time']) && (time() - $_SESSION['user_login_time']) > AUTH_SESSION_TIMEOUT) {
    $_SESSION[SESSION_PENDING_GOOGLE_TOKEN_PURGE] = true;
    unset($_SESSION['user_authenticated'], $_SESSION['user_login_time']);
}

function require_admin_auth() {
    if (empty($_SESSION['admin_authenticated'])) {
        header('Location: login.php?redirect=update_license.php');
        exit;
    }
}

function require_user_auth() {
    // 管理者は全ページにアクセス可能
    if (!empty($_SESSION['admin_authenticated'])) {
        return;
    }
    if (empty($_SESSION['user_authenticated'])) {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        $redirect = urlencode(basename($path) ?: 'index.php');
        header('Location: login.php?redirect=' . $redirect);
        exit;
    }
}
