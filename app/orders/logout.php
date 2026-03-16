<?php
session_start();
require_once __DIR__ . '/setting.php';

$token_file = isset($_SESSION['user_name']) ? 'token_'.$_SESSION['user_name'].'.json' : 'token.json';
$token_path = defined('APP_ORDERS_ROOT') ? APP_ORDERS_ROOT . '/' . $token_file : $token_file;
$result_msg = '';

$file_handle = fopen($token_path, 'w');
if ($file_handle === false) {
    $result_msg = "ファイルを開けませんでした。";
} else {
    if (fwrite($file_handle, '') === false) {
        $result_msg = "ログアウト失敗。ファイルの書き込みに失敗しました。";
    } else {
        $result_msg = "ログアウト成功。".$token_file."ファイルの内容を空にしました。";
    }
    fclose($file_handle);
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>ログアウト</title>
<link rel="stylesheet" type="text/css" href="../css/main.css">
</head>

<body>
<p>結果：
  <?= htmlspecialchars($result_msg) ?>
</p>
<p><a href="order_book.php" class="button1">通常注文処理</a></p>
<p><a href="./" class="button1">サンプル注文処理</a></p>
<p><a href="update_license.php" class="button1">ライセンス更新</a></p>
<p><br />
  <br />
  
  
  
</p>
</body>
</html>
