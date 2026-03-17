<?php
/**
 * ログイン画面
 * - redirect=update_license.php → ADMIN_USERNAME / ADMIN_PASSWORD で認証
 * - その他 → USER_USERNAME / USER_PASSWORD で認証
 */
session_start();
require_once __DIR__ . '/setting.php';

// リダイレクト先の検証（安全なパスのみ許可）
$redirect = $_GET['redirect'] ?? $_POST['redirect'] ?? 'index.php';
$allowed = ['index.php', 'order_book.php', 'sample_order_book.php', 'update_license.php'];
$redirect = in_array($redirect, $allowed) ? $redirect : 'index.php';

$is_admin_page = ($redirect === 'update_license.php');

// セッションタイムアウトチェック（1日）
$timeout = 86400;
if (!empty($_SESSION['admin_login_time']) && (time() - $_SESSION['admin_login_time']) > $timeout) {
    unset($_SESSION['admin_authenticated'], $_SESSION['admin_login_time']);
}
if (!empty($_SESSION['user_login_time']) && (time() - $_SESSION['user_login_time']) > $timeout) {
    unset($_SESSION['user_authenticated'], $_SESSION['user_login_time']);
}

// 既に認証済みならリダイレクト
if ($is_admin_page && !empty($_SESSION['admin_authenticated'])) {
    header('Location: ' . $redirect);
    exit;
}
if (!$is_admin_page && (!empty($_SESSION['user_authenticated']) || !empty($_SESSION['admin_authenticated']))) {
    header('Location: ' . $redirect);
    exit;
}

$result_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($is_admin_page) {
        $valid_username = getenv('ADMIN_USERNAME') ?: '';
        $valid_password = getenv('ADMIN_PASSWORD') ?: '';
        if (isset($_POST['username']) && isset($_POST['password'])
            && $_POST['username'] === $valid_username && $_POST['password'] === $valid_password) {
            $_SESSION['admin_authenticated'] = true;
            $_SESSION['admin_login_time'] = time();
            session_regenerate_id(true);
            header('Location: ' . $redirect);
            exit;
        }
    } else {
        $valid_username = getenv('USER_USERNAME') ?: '';
        $valid_password = getenv('USER_PASSWORD') ?: '';
        if (isset($_POST['username']) && isset($_POST['password'])
            && $_POST['username'] === $valid_username && $_POST['password'] === $valid_password) {
            $_SESSION['user_authenticated'] = true;
            $_SESSION['user_login_time'] = time();
            session_regenerate_id(true);
            header('Location: ' . $redirect);
            exit;
        }
    }
    $result_msg = 'ユーザ名またはパスワードが間違っています。';
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>ログイン - Weaver 注文処理システム</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <link rel="stylesheet" type="text/css" href="../../css/main.css">
    <style>
    .center { text-align: center; }
    .center h1 { color: #666; font-weight: 400; }
    </style>
</head>
<body>
<div class="center">
<h1>Weaver 注文処理システム - ログイン</h1>
<?php if ($is_admin_page): ?>
<p>ライセンス更新には管理者認証が必要です。</p>
<?php endif; ?>
<?php if ($result_msg): ?>
<p class="error"><?= htmlspecialchars($result_msg, ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>
<form action="" method="post">
    <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect, ENT_QUOTES, 'UTF-8') ?>">
    <label for="username">ユーザ名:</label>
    <input type="text" id="username" name="username" required><br>
    <label for="password">パスワード:</label>
    <input type="password" id="password" name="password" required><br>
    <button type="submit">ログイン</button>
</form>
</div>
</body>
</html>
