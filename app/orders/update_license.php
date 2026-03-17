<?php
/**
 * ライセンスキー更新（セキュリティ強化版）
 * - 認証は auth.php で共通管理（login.php でログイン）
 * - CSRF対策
 */
session_start();
require_once __DIR__ . '/setting.php';
require_once __DIR__ . '/inc/auth.php';
require_admin_auth();

// ライセンス更新画面ではエラー表示を無効化（設定を上書き）
ini_set('display_errors', 0);

// セッションにCSRFトークンがなければ生成
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$result_msg = '';
$show_form = true;

// POST処理（ライセンスキー更新）
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRFトークン検証
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $result_msg = '不正なリクエストです。';
    } else {
        $license_key = preg_replace('/[^a-zA-Z0-9_]/', '', trim($_POST['license_key'] ?? ''));
        $license_file = APP_ORDERS_ROOT . '/data/license_key.txt';
        $data_dir = dirname($license_file);
        if (!is_dir($data_dir)) {
            mkdir($data_dir, 0755, true);
        }
        if (file_put_contents($license_file, $license_key) !== false) {
            $result_msg = '更新しました。';
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // トークン再生成
            $show_form = false;
        } else {
            $result_msg = 'ファイルの書き込みに失敗しました。';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>キーの更新フォーム</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <link rel="stylesheet" type="text/css" href="../../css/main.css">
    <style>
    .center { text-align: center; }
    .center h1 { color: #666; font-weight: 400; }
    </style>
</head>
<body>
    <div class="center">
    <h1>ライセンス更新</h1>
    <?php if ($result_msg): ?>
    <p><?= htmlspecialchars($result_msg, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
    <?php if ($show_form): ?>
    <form action="" method="post">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
        <label for="license_key">license_key:</label>
        <input type="text" id="license_key" name="license_key" required><br>
        <button type="submit">更新</button>
    </form>
    <?php endif; ?>
    <p><a href="index.php" class="button1">トップに戻る</a></p>
    </div>
</body>
</html>
