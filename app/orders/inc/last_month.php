<?php

// 設定
// 月の設定と文字列を生成
if (isset($_GET['month']) && is_numeric($_GET['month'])) {
	$str_month = strtotime($_GET['month']." month");
	$setting_month = $_GET['month'];
} else {
	$str_month = strtotime("0 month");
	$setting_month = 0;
}

// 先月分処理のURL作成
$uri_last_month = $_SERVER['REQUEST_URI'];
if (strpos($uri_last_month, '?') !== false) {
    $query = parse_url($uri_last_month, PHP_URL_QUERY); // 現在のクエリ文字列を取得
    parse_str($query, $params); // クエリ文字列を連想配列に変換

    if (isset($params['month']) && !empty($params['month'])) {
       $uri_last_month = '?month='.((int)$params['month'] - 1);
    } else {
        $uri_last_month = '?month=-1';
    }
} else {
    $uri_last_month = '?month=-1';
}
?>
<br />
<a href="<?= htmlspecialchars($uri_last_month) ?>" class="button1">先月分を処理する</a><br />
<br />
