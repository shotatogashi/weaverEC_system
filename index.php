<?php

ini_set( 'display_errors', 1 );
session_start();
require_once('setting.php');
/*
http://wkimono.tokyo/app/rakuten_api/

環境セットアップ
composer require google/apiclient:^2.0

https://console.cloud.google.com/apis/dashboard?project=cosmic-inkwell-283508

無料サンプル請求(合計3品番まで)（営業日で2〜3日後出荷）
https://item.rakuten.co.jp/o-bear/sample/
商品管理番号（商品URL）: sample
商品番号:営業日で2〜3日後出荷
商品名:無料サンプル請求(合計3品番まで)

refresh token: https://qiita.com/chenglin/items/f2382898a8cf85bec8dd
*/

require_once 'vendor/autoload.php'; // Google API PHPクライアントライブラリの読み込み

// 関数
require_once("lib.php");

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>サンプル注文処理</title>
<link rel="stylesheet" type="text/css" href="css/main.css">
</head>

<body>
<p><a href="update_license.php" class="button1">ライセンス更新</a></p>
<?php
require_once('inc/last_month.php');
?>

<?php

// 事前処理
$sample_flg = TRUE;
$google_redirect_uri = '';

require_once('inc/preprocess.php');

// 30件 ファイルアップロード
echo "ヒットした注文件数は".count($order_info)."件です。<br />\n";
if (count($order_info) == 0) {
	echo "終了します";
	die();
}

foreach ($order_info as $order_num => $customer_info) {

	// 上書き確認
	$file_name = 'weaver_sample_' . $order_num . '.txt';
	echo "ファイル：".$file_name."<br />\n";

	if (!is_file_exists_in_folder($service, $folder_id, $file_name)) {
	
		// ファイル作成
		echo "書き込みます<br />\n";

		// 書き込むか設定で決める
		if ($config['write_drive_flg']) { 
			create_file($service, $folder_id, 'weaver_sample_'.$order_num.'.txt', $customer_info);
		} else {
			echo "Googleドライブへの書き込み不可設定中です。<br />\n";
		}
		
	} else {
		echo "ファイルが既に存在しています。書き込みません。<br />\n";
	}
	echo "<br />\n";
}
?>
</body>
</html>
