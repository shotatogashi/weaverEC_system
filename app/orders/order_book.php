<?php 
session_start(); 
require_once('setting.php');
/*
composer require google/apiclient:^2.0

https://console.cloud.google.com/apis/dashboard?project=cosmic-inkwell-283508

無料サンプル請求(合計3品番まで)（営業日で2〜3日後出荷）
https://item.rakuten.co.jp/o-bear/sample/
商品管理番号（商品URL）: sample
商品番号:営業日で2〜3日後出荷
商品名:無料サンプル請求(合計3品番まで)

refresh token: https://qiita.com/chenglin/items/f2382898a8cf85bec8dd
*/

require_once APP_ORDERS_PARENT . '/vendor/autoload.php';

// 関数
require_once("lib.php");

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>通常注文処理</title>
<link rel="stylesheet" type="text/css" href="../css/main.css">
</head>

<body>
<p><a href="update_license.php" class="button1">ライセンス更新</a></p>
<?php
require_once('inc/last_month.php');
?>

<?php

// 事前処理
$sample_flg = FALSE;
$google_redirect_uri = 'order_book.php';

require_once('inc/preprocess.php');

// ファイル名取得
$year_month = date("Y_m", $str_month);

$file_name = $year_month.'～注文weaver.txt';
$csv_name = $year_month.'～注文weaver.csv';
echo "ファイル名：".htmlspecialchars($file_name, ENT_QUOTES, 'UTF-8').", ".htmlspecialchars($csv_name, ENT_QUOTES, 'UTF-8')."<br />\n";

// ファイルID取得
$file_id = get_google_drive_file_id($service, $folder_id, $file_name);
$csv_id = get_google_drive_file_id($service, $folder_id, $csv_name);
//if (!empty($file_id)) echo "ファイルID：".$file_id.", ".$csv_id."<br />\n";

if (empty($file_id)) { 
	echo "ファイルが存在しないので作成します<br />\n";
	$file_id = create_file($service, $folder_id, $file_name, "");
}
if (empty($csv_id)) { 
	echo "CSVファイルが存在しないので作成します<br />\n";
	$csv_id = create_file($service, $folder_id, $csv_name, "");
}

echo "ファイルを読み込みます<br />\n";
$existing_file = $service->files->get($file_id, array('alt' => 'media'));
$file_contents = $existing_file->getBody()->getContents();

$existing_csv = $service->files->get($csv_id, array('alt' => 'media'));
$csv_contents = $existing_csv->getBody()->getContents();
//echo "ファイル内容：".$file_contents."<br />\n";


//// CSVファイルのコンテンツ（改行コードを正規化して \r を除去）
$csv_contents_normalized = str_replace(["\r\n", "\r"], "\n", $csv_contents);
$r_order_num = array_keys($order_info);
$r_csv_contents = array_map('trim', explode("\n", $csv_contents_normalized));
$r_csv_merged = array_merge($r_csv_contents, $r_order_num);
$r_csv_merged = array_unique($r_csv_merged);
$r_csv_merged = array_diff($r_csv_merged, ['']);
$r_csv_merged = array_values($r_csv_merged);
$new_csv_contents = implode("\n", $r_csv_merged);
//echo $new_csv_contents."<br />\n";

//print_r($order_info);
//// 注文ファイルのコンテンツ

echo "楽天APIから".count($order_info)."件取得<br />\n";

// 除外処理
$r_excluded_sample_order = [];
$r_excluded_pending_order = [];
foreach ($order as $key => $o) {
	// PackageModelList[0] と ItemModelList の存在チェック
	if (empty($o->PackageModelList[0]) || empty($o->PackageModelList[0]->ItemModelList)) {
		continue;
	}
	// サンプル注文を削除
	// サンプル商品の除外を、複数商品の場合に対応。一つでも通常商品があれば除外しない。全てサンプルなら除外する
	$sample_flg = TRUE;
	foreach($o->PackageModelList[0]->ItemModelList as $item_model_list) {
		if ($item_model_list->itemNumber !== "m-sample") {
			$sample_flg = FALSE;
		}
	}
	if ($sample_flg) {
		//echo "サンプル注文のため".$o->orderNumber."は除外<br />\n";
		unset($order_info[$o->orderNumber]);
		$r_excluded_sample_order[] = $o->orderNumber;
	}
    
	/*if ($o->PackageModelList[0]->ItemModelList[0]->itemNumber === "m-sample") {
        unset($order_info[$o->orderNumber]);
		//echo "サンプル注文のため".$o->orderNumber."は除外<br />\n";
		$r_excluded_sample_order[] = $o->orderNumber;
    }*/

	// subStatusNameが保留の注文を除外
	if ($o->subStatusName == '保留') {
        unset($order_info[$o->orderNumber]);
		//echo "サブステータスが保留のため".$o->orderNumber."は除外<br />\n";
		$r_excluded_pending_order[] = $o->orderNumber;
	}
}
if (count($r_excluded_sample_order)) echo "サンプル注文のため除外：".htmlspecialchars(implode(',', $r_excluded_sample_order), ENT_QUOTES, 'UTF-8')."<br />\n";
if (count($r_excluded_pending_order)) echo "サブステータスが保留のため除外：".htmlspecialchars(implode(',', $r_excluded_pending_order), ENT_QUOTES, 'UTF-8')."<br />\n";

// 書き込み済み注文を削除
foreach ($r_csv_contents as $key => $order_num) {
	if (array_key_exists($order_num, $order_info)) {
		unset($order_info[$order_num]);
		echo "処理済み注文ID：".htmlspecialchars($order_num, ENT_QUOTES, 'UTF-8')."<br />\n";
	}
}



echo "除外処理後：".count($order_info)."件を追記<br />\n";
if (count($order_info) == 0) {
	echo "追記不要のため処理を終了します。<br />\n";
	die();
}

$new_contents = $file_contents."\n".implode("\n", $order_info);




echo "追記するデータの先頭40バイト：". htmlspecialchars(mb_substr($new_contents, 0, 40), ENT_QUOTES, 'UTF-8')."<br />\n";
echo "追記するCSVの先頭40バイト：". htmlspecialchars(mb_substr($new_csv_contents, 0, 40), ENT_QUOTES, 'UTF-8')."<br />\n";
echo "追記します<br />\n";

// 新規ファイルを作成（両方成功した場合のみ既存ファイルを削除）
try {
	$new_file_id = create_file($service, $folder_id, $file_name, $new_contents);
	$new_csv_id = create_file($service, $folder_id, $csv_name, $new_csv_contents);

	// 両方の作成に成功した場合のみ既存ファイルを削除
	try {
		$service->files->delete($file_id);
		$service->files->delete($csv_id);
	} catch (Exception $e) {
		echo "<p class=\"error\">警告: 新規ファイルは作成されましたが、既存ファイルの削除に失敗しました。手動で確認してください。エラー: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "</p>\n";
	}
} catch (Exception $e) {
	echo "<p class=\"error\">エラー: ファイルの作成に失敗しました。既存ファイルは変更されていません。エラー: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "</p>\n";
	die();
}

?>
<p><a href="update_license.php" class="button1">ライセンス更新</a></p>
</body>
</html>
