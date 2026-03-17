<?php

// 楽天API
echo "<hr>\n";
echo "<h2 class=\"section-title\">楽天からデータを取得します</h2>\n";
list($order_info, $order) = get_order_info($secret_key, $license_key, $sample_flg);

// Google クライアントの設定
$client = get_google_token($google_redirect_uri);

if ($client === null) {
	$auth_url = $GLOBALS['google_auth_url'] ?? '#';
	echo "<hr>\n";
	echo "<h2 class=\"section-title\">Google Driveにデータを書き込みます</h2>\n";
	echo "トークンは期限切れです。<a href='".htmlspecialchars($auth_url, ENT_QUOTES, 'UTF-8')."' class='button1'>再認証</a><br /><br />\n";
	$service = null;
	$folder_id = null;
	return;
}

// Driveサービスオブジェクトの作成
$service = new Google_Service_Drive($client);

// 20240902以前
//$_SESSION['user_name'] = strtolower(preg_replace('/[^a-zA-Z]/', '', $service->about->get(['fields' => 'user'])->user->displayName));

// 20240902以降。トークン期限切れへの対応。
// エラー：Fatal error: Uncaught Google\Service\Exception: { "error": { "code": 401, "message": "Request had invalid authentication credentials. Expected OAuth 2 access token, login cookie or other valid authentication credential.
try {
    $_SESSION['user_name'] = strtolower(preg_replace('/[^a-zA-Z]/', '', $service->about->get(['fields' => 'user'])->user->displayName));
} catch (Google\Service\Exception $e) {
    if ($e->getCode() == 401 && strpos($e->getMessage(), 'Invalid Credentials') !== false) {
        header('Location: ./logout.php');
        exit;
    } else {
        echo 'An error occurred: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        exit;
    }
}

// フォルダID取得
$folder_id = get_folder_id($service, 'weaver-rakuten');
if (empty($folder_id)) {
	echo "フォルダID取得失敗。終了します";
	die();
}

// 30件 ファイルアップロード
echo "ヒットした注文件数は".count($order_info)."件です。<br />\n";
if (count($order_info) == 0) {
	echo "終了します<br />\n";
}

?>
