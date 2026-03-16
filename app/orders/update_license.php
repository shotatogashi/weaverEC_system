<?php
/**
 * ライセンスキー更新（セキュリティ強化版）
 * - 認証情報は環境変数から
 * - CSRF対策
 * - セッションによるアクセス制御
 */
session_start();
require_once __DIR__ . '/setting.php';
// ライセンス更新画面ではエラー表示を無効化（設定を上書き）
ini_set('display_errors', 0);

// セッションにCSRFトークンがなければ生成
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$result_msg = '';
$show_form = true;

// セッションタイムアウト（30分）
$session_timeout = 1800;
if (!empty($_SESSION['admin_login_time']) && (time() - $_SESSION['admin_login_time']) > $session_timeout) {
    unset($_SESSION['admin_authenticated'], $_SESSION['admin_login_time']);
}

// POST処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRFトークン検証
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $result_msg = '不正なリクエストです。';
    } else {
        // 環境変数から認証情報を取得
        $valid_username = getenv('ADMIN_USERNAME') ?: '';
        $valid_password = getenv('ADMIN_PASSWORD') ?: '';

        if ($_POST['username'] === $valid_username && $_POST['password'] === $valid_password) {
            // 認証成功：セッションに記録
            $_SESSION['admin_authenticated'] = true;
            $_SESSION['admin_login_time'] = time();
            session_regenerate_id(true);

            // license_keyの更新
            $license_key = preg_replace('/[^a-zA-Z0-9_]/', '', trim($_POST['license_key'] ?? ''));
            $license_file = APP_ORDERS_ROOT . '/data/license_key.txt';
            $data_dir = dirname($license_file);
            if (!is_dir($data_dir)) {
                mkdir($data_dir, 0755, true);
            }
            if (file_put_contents($license_file, $license_key) !== false) {
                $result_msg = '更新しました。';
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // トークン再生成
            } else {
                $result_msg = 'ファイルの書き込みに失敗しました。';
            }
            $show_form = false;
        } else {
            $result_msg = 'ユーザ名またはパスワードが間違っています。';
        }
    }
}

// 認証済みでない場合、ログインフォームを表示
if ($show_form && empty($_SESSION['admin_authenticated'])) {
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>キーの更新フォーム - ログイン</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <link rel="stylesheet" type="text/css" href="../css/main.css">
</head>
<body>
    <?php if ($result_msg): ?>
    <p class="error"><?= htmlspecialchars($result_msg) ?></p>
    <?php endif; ?>
    <form action="" method="post">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
        <label for="username">ユーザ名:</label>
        <input type="text" id="username" name="username" required><br>
        <label for="password">パスワード:</label>
        <input type="password" id="password" name="password" required><br>
        <label for="license_key">license_key:</label>
        <input type="text" id="license_key" name="license_key" required><br>
        <button type="submit">ログインして更新</button>
    </form>
    <p><a href="order_book.php" class="button1">通常注文処理</a></p>
    <p><a href="./" class="button1">サンプル注文処理</a></p>
</body>
</html>
<?php
    exit;
}

// 認証済み：更新フォームまたは結果を表示
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !empty($result_msg)) {
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>キーの更新フォーム</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <link rel="stylesheet" type="text/css" href="../css/main.css">
</head>
<body>
    <?php if ($result_msg): ?>
    <p><?= htmlspecialchars($result_msg) ?></p>
    <?php endif; ?>
    <?php if ($show_form): ?>
    <form action="" method="post">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
        <label for="username">ユーザ名:</label>
        <input type="text" id="username" name="username" required><br>
        <label for="password">パスワード:</label>
        <input type="password" id="password" name="password" required><br>
        <label for="license_key">license_key:</label>
        <input type="text" id="license_key" name="license_key" required><br>
        <button type="submit">更新</button>
    </form>
    <?php endif; ?>
    <p><a href="order_book.php" class="button1">通常注文処理</a></p>
    <p><a href="./" class="button1">サンプル注文処理</a></p>
</body>
</html>
<?php
}
