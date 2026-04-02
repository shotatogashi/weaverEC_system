<?php

// エラー表示あり

ini_set('display_errors', 1);



// フォームからPOSTされたデータがある場合の処理

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ユーザ名とパスワードの確認

    if ($_POST['username'] === 'weaver' && $_POST['password'] === 'shota2024$') {

        // license_keyの更新

        file_put_contents('data/license_key.txt', trim($_POST['license_key']));

        echo '更新しました。'; // メッセージの表示

    } else {

        echo 'ユーザ名またはパスワードが間違っています。'; // 認証エラーメッセージ

    }

}

?>



<!DOCTYPE html>

<html lang="ja">

<head>

    <meta charset="UTF-8">

    <title>キーの更新フォーム</title>

	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">

	<link rel="stylesheet" type="text/css" href="css/main.css">

</head>

<body>

    <form action="" method="post">

        <label for="username">ユーザ名:</label>

        <input type="text" id="username" name="username" required><br>

        <label for="password">パスワード:</label>

        <input type="password" id="password" name="password" required><br>

        <label for="license_key">license_key:</label>

        <input type="text" id="license_key" name="license_key" required><br>

        <button type="submit">更新</button>

    </form>

<p><a href="order_book.php" class="button1">通常注文処理</a></p>

<p><a href="./" class="button1">サンプル注文処理</a></p>

</body>

</html>

