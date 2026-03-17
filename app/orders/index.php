<?php
session_start();
require_once __DIR__ . '/setting.php';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>注文処理 - リンク集</title>
<link rel="stylesheet" type="text/css" href="../../css/main.css">
<style>
.center { text-align: center; }
.center ul { list-style: none; padding-left: 0; }
.center ul li { margin-bottom: 12px; }
.center h1 { color: #666; font-weight: 400; }
</style>
</head>

<body>
<div class="center">
<h1>Weaver 注文処理システム</h1>
<ul>
<li><a href="order_book.php" class="button1">通常注文処理</a></li>
<li><a href="sample_order_book.php" class="button1">サンプル注文処理</a></li>
<li><a href="logout.php" class="button1">ログアウト</a></li>
<li><a href="update_license.php" class="button1">ライセンス更新</a></li>
</ul>
</div>
</body>
</html>
