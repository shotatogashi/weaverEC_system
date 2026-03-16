<?php

// 設定
// 月の設定と文字列を生成（-12〜12の整数のみ許可）
if (isset($_GET['month'])) {
	$month_val = (int)$_GET['month'];
	if ($month_val >= -12 && $month_val <= 12) {
		$str_month = strtotime($month_val . " month");
		$setting_month = $month_val;
	} else {
		$str_month = strtotime("0 month");
		$setting_month = 0;
	}
} else {
	$str_month = strtotime("0 month");
	$setting_month = 0;
}

// 先月分処理のURL作成
$uri_last_month = $_SERVER['REQUEST_URI'];
if (strpos($uri_last_month, '?') !== false) {
    $query = parse_url($uri_last_month, PHP_URL_QUERY); // 現在のクエリ文字列を取得
    parse_str($query, $params); // クエリ文字列を連想配列に変換

    if (isset($params['month']) && $params['month'] !== '') {
       $prev_month = (int)$params['month'] - 1;
       $prev_month = max(-12, min(12, $prev_month)); // 範囲内に収める
       $uri_last_month = '?month=' . $prev_month;
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
