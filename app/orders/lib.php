<?php

// 楽天API
function curl_api($curl, $params, $headers, $bln_xml = false, $bln_post = true){
	if($bln_post){
		curl_setopt($curl, CURLOPT_POST, TRUE);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $params); // パラメータをセット
	}
	// SSL証明書検証を有効化（セキュリティ強化）
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); // レスポンスを文字列で受け取る
	curl_setopt($curl, CURLOPT_HEADER, true);
	curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

	$response = curl_exec($curl);

	$header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
	$request_body = substr($response, $header_size);
	
	if($bln_xml){
		$response_data = simplexml_load_string($request_body);
	} else {
		$response_data = json_decode($request_body);
	}
	return $response_data;
}

// 楽天APIで注文情報を取得
function get_order_info($secret_key, $license_key, $sample_flg = TRUE) {
	global $setting_month;

    $order_info = []; // 戻り値にする注文情報の配列。１配列に１文字列の注文情報。
	
    $headers = array(
        "Authorization: ESA " . base64_encode($secret_key . ':' . $license_key),
        'Content-Type: application/json; charset=utf-8'
    );
	
	$order = [];
	for ($i=1; $i<=10; $i++) {
		// API
		$curl = curl_init('https://api.rms.rakuten.co.jp/es/2.0/purchaseItem/searchOrderItem/');
		if ($sample_flg) {
			// サンプル商品
			$params = json_encode([
				'orderProgressList' => [100], // 100 注文確認待ち, 200: 楽天処理中, 300: 発送待ち
				'dateType' => 1,
				'startDatetime' => date('Y-m-01', strtotime($setting_month . ' month')) . "T00:00:00+0900",
				'endDatetime' => date('Y-m-01', strtotime(($setting_month + 1) . ' month')) . "T00:00:00+0900",
				'PaginationRequestModel' => [
					"requestRecordsAmount" => 30,
					"requestPage" => $i,
					"SortModelList" => [
						[
							"sortColumn" => 1,
							"sortDirection" => 1
						]
					],
				],
				'searchKeywordType' => 2,
				'searchKeyword' => 'm-sample',
			]);
		} else {
			// 通常商品
			$params = json_encode([
				// 100 注文確認待ち, 200: 楽天処理中, 300: 発送待ち
				'orderProgressList' => [100],
				'dateType' => 1,
				'startDatetime' => date('Y-m-01', strtotime($setting_month . ' month')) . "T00:00:00+0900",
				'endDatetime' => date('Y-m-01', strtotime(($setting_month + 1) . ' month')) . "T00:00:00+0900",
				'PaginationRequestModel' => [
					"requestRecordsAmount" => 30,
					"requestPage" => $i,
					"SortModelList" => [
						[
							"sortColumn" => 1,
							"sortDirection" => 1 // 1 昇順 古い方から, 2 降順 新しい方から。
						]
					],
				]
			]);
		}
	
		$json_order_number = curl_api($curl, $params, $headers);
		curl_close($curl);
		
		if ($json_order_number === null) {
			echo "楽天APIの応答が不正です。ネットワークまたはAPIの状態を確認してください。";
			die();
		}
		if (($json_order_number->Results->message ?? null) === 'Un-Authorised') {
			echo "楽天APIの認証に失敗しました。ライセンスキー・シークレットキーの組み合わせが違います。";
			die();
		}
		$order_number_list = $json_order_number->orderNumberList ?? [];
		if (empty($order_number_list)) {
			break;
		}
		$curl = curl_init('https://api.rms.rakuten.co.jp/es/2.0/order/getOrder/');
		$params = json_encode([
			'orderNumberList' => $order_number_list,
			'version' => 7
		]);
		
		$json = curl_api($curl, $params, $headers);

		if ($json === null || count($json->OrderModelList ?? []) == 0) {
			break;
		}

		$order = array_merge($order, $json->OrderModelList);
		
		foreach ($order as $o) {
			//echo "注文ID: ".$o->orderNumber."<br />\n";
			//if ($o->orderNumber == '244394-20230726-0143341951') { print_r($json); die(); }
			
			if ($sample_flg) {
				$o_text = convert_sample_order_info($o);
			} else {
				$o_text = convert_order_info($o);
			}
			if (!is_null($o_text)) {
				$order_info[$o->orderNumber] = $o_text;
			}
		}
	}

	return [$order_info, $order];
/*
ステータス	サブステータス名	サブステータスID
注文確認待ち	保留	28604
*/
}



// 無料サンプルの注文情報をテキストで出力
function convert_sample_order_info($order_info) {
	if (empty($order_info->PackageModelList[0]) || empty($order_info->PackageModelList[0]->SenderModel)
		|| empty($order_info->OrdererModel)) {
		return null;
	}

    // お届け先情報
    $delivery_info = $order_info->PackageModelList[0]->SenderModel;
    $delivery_output = "[お届け先]   {$delivery_info->familyName} {$delivery_info->firstName} ({$delivery_info->familyNameKana} {$delivery_info->firstNameKana}) 様\n";
    $delivery_output .= "          〒{$delivery_info->zipCode1}-{$delivery_info->zipCode2} {$delivery_info->prefecture}{$delivery_info->city}{$delivery_info->subAddress}\n";
    $delivery_output .= "{$delivery_info->phoneNumber1}-{$delivery_info->phoneNumber2}-{$delivery_info->phoneNumber3}\n\n";

	// 1注文複数商品 対応
	$item_output = "[商品]\n";
	foreach ($order_info->PackageModelList as $package_info) {
		if (empty($package_info->ItemModelList)) continue;
		foreach ($package_info->ItemModelList as $item_info) {
			$item_output .= "{$item_info->itemName}\n";
			$item_output .= "{$item_info->selectedChoice}\n\n";
		}
	}
	
	// 受注情報
	$order_output = "---------------------------------------------------------------------\n\n";
	$order_output .= "[受注番号] {$order_info->orderNumber}\n";
	$order_output .= "[日時]     {$order_info->orderDatetime}\n";
	$order_output .= "[注文者]   {$order_info->OrdererModel->familyName} {$order_info->OrdererModel->firstName} ({$order_info->OrdererModel->familyNameKana} {$order_info->OrdererModel->firstNameKana}) 様\n";
	
	return $delivery_output."\n".$item_output."\n".$order_output;
}

// 通常注文の注文情報をテキストで出力
function convert_order_info($order_info) {
	if (empty($order_info->PackageModelList[0]) || empty($order_info->PackageModelList[0]->SenderModel)
		|| empty($order_info->OrdererModel) || empty($order_info->PackageModelList[0]->ItemModelList)) {
		return null;
	}
	$list_settlement = [
		1 => 'クレジットカード',
		2 => '代金引換',
		4 => 'ショッピングクレジット／ローン',
		5 => 'オートローン',
		6 => 'リース',
		7 => '請求書払い',
		8 => 'ポイント',
		9 => '銀行振込',
		12 => 'Apple Pay',
		13 => 'セブンイレブン（前払）',
		14 => 'ローソン、郵便局ATM等（前払）',
		16 => 'Alipay',
		17 => 'PayPal',
		21 => '後払い決済',
		27 => 'Alipay（支付宝）'
	];
	
	// お届け先
	$delivery_info = $order_info->PackageModelList[0]->SenderModel;
	$zip_code_1 = $delivery_info->zipCode1;
	$zip_code_2 = $delivery_info->zipCode2;
	$prefecture = $delivery_info->prefecture;
	$city = $delivery_info->city;
	$sub_address = $delivery_info->subAddress;
	$family_name = $delivery_info->familyName;
	$first_name = $delivery_info->firstName;
	$family_name_kana = $delivery_info->familyNameKana;
	$first_name_kana = $delivery_info->firstNameKana;
	$phone_number_1 = $delivery_info->phoneNumber1;
	$phone_number_2 = $delivery_info->phoneNumber2;
	$phone_number_3 = $delivery_info->phoneNumber3;

	$output = "==========\n";
	$output .= "[お届け先]   {$family_name} {$first_name} ({$family_name_kana} {$first_name_kana}) 様\n";
	$output .= "        〒{$zip_code_1}-{$zip_code_2} {$prefecture}{$city}{$sub_address}\n";
	$output .= "{$phone_number_1}-{$phone_number_2}-{$phone_number_3}\n\n";
	
	// 注文者
	$order_number = $order_info->orderNumber;
	$order_datetime = $order_info->orderDatetime;
	$zip_code_1 = $order_info->OrdererModel->zipCode1;
	$zip_code_2 = $order_info->OrdererModel->zipCode2;
	$prefecture = $order_info->OrdererModel->prefecture;
	$city = $order_info->OrdererModel->city;
	$sub_address = $order_info->OrdererModel->subAddress;
	$family_name = $order_info->OrdererModel->familyName;
	$first_name = $order_info->OrdererModel->firstName;
	$family_name_kana = $order_info->OrdererModel->familyNameKana;
	$first_name_kana = $order_info->OrdererModel->firstNameKana;
	$phone_number_1 = $order_info->OrdererModel->phoneNumber1;
	$phone_number_2 = $order_info->OrdererModel->phoneNumber2;
	$phone_number_3 = $order_info->OrdererModel->phoneNumber3;

	// 1注文複数商品 対応
	foreach($order_info->PackageModelList[0]->ItemModelList as $item){
		$item_name = $item->itemName ?? '';
		$selected_choice = $item->selectedChoice ?? '';
		$item_number = $item->itemNumber ?? '';
		$price = $item->price ?? 0;
		$units = $item->units ?? 0;
		$sku_id = !empty($item->SkuModelList[0]) ? ($item->SkuModelList[0]->variantId ?? '') : '';
		$sku_info = !empty($item->SkuModelList[0]) ? ($item->SkuModelList[0]->skuInfo ?? '') : '';
	
		$output .= "[商品]\n";
		$output .= "{$item_name} ({$item_number})\n";
		if (!empty($sku_id)){
			$output .= "SKU管理番号:{$sku_id}\n";
		}
		if (!empty($sku_info)){
			$output .= "SKU情報:{$sku_info}\n";
		}
		if (!empty($selected_choice)){
			$output .= "{$selected_choice}\n";
		}
		$output .= "価格  {$price}(円) x {$units}(個) = " . number_format($price * $units) . "(円) ※10%税込\n";
		$output .= "----------\n";
	}
	$shipping_price = $order_info->PackageModelList[0]->postagePrice ?? 0;
	$used_point = !empty($order_info->PointModel) ? ($order_info->PointModel->usedPoint ?? 0) : 0;

	$output .= "送料(税込)      " . number_format($shipping_price) . "(円) \n";
	$output .= "クーポン利用 -" . number_format($order_info->couponAllTotalPrice ?? 0) . "(円)\n";
	$output .= "ポイント利用 -" . number_format($used_point) . "(円)\n";
	$output .= "支払い金額     " . number_format($order_info->requestPrice) . "(円)\n";
	$output .= "=====\n";
	$output .= "[受注番号] {$order_number}\n";
	$output .= "[日時]     " . date('Y-m-d H:i:s', strtotime($order_datetime)) . "\n";
	$output .= "[注文者様]   {$family_name} {$first_name} ({$family_name_kana} {$first_name_kana}) 様\n";
	$output .= "        〒{$zip_code_1}-{$zip_code_2} {$prefecture}{$city}{$sub_address}\n";
	$output .= "{$phone_number_1}-{$phone_number_2}-{$phone_number_3}\n\n";

	$settlement_code = $order_info->SettlementModel->settlementMethodCode ?? 0;
	$output .= "[決済方法] ". ($list_settlement[$settlement_code] ?? 'その他') ."\n";
	$output .= "[配送方法] 宅配便\n";
	$output .= "[お届け日時] ".($order_info->deliveryDate ?? '')."\n\n";
	
	// 備考はお客様が修正・追加した場合に限り表示
	// $order_info->remarksの3行目以降を取得
	$remarks = explode("\n", (string)($order_info->remarks ?? ''));
	$remarks_from_third_line = array_slice($remarks, 2);
	$remarks_string = trim(implode("\n", $remarks_from_third_line));
	
	// remarks.txtの内容を取得（app/orders用パス）
	$remarks_path = defined('APP_ORDERS_PARENT') ? APP_ORDERS_PARENT . '/remarks.txt' : './remarks.txt';
	$file_contents = file_exists($remarks_path) ? trim(file_get_contents($remarks_path)) : '';
	
	// 改行コードを取り除く
	$rs_no_newline = str_replace(["\r", "\n"], '', $remarks_string);
	$fc_no_newline = str_replace(["\r", "\n"], '', $file_contents);

	// 備考とファイル内容が一致するか確認
	if ($rs_no_newline === $fc_no_newline) {
		//echo "備考に変更がないので追記しない<br />\n";
	} else {
		echo "備考が変更されたので追記する。注文ID:".htmlspecialchars($order_number, ENT_QUOTES, 'UTF-8')."<br />\n";
		$output .= "[備考]\n".((string)($order_info->remarks ?? ''))."\n";
	}
	//$output .= "[備考]\n".$order_info->remarks."\n";
	
	$output .= date("Y/m/d")."\n";
	$output .= "●●●●●●●●\n";
	//echo $output; die();
	return $output;
}


// Google Clientオブジェクト生成
function create_google_client($redirect_uri = '') {
    $client = new Google_Client();
    $client_secret_path = defined('APP_ORDERS_PARENT') ? APP_ORDERS_PARENT . '/client_secret.json' : 'client_secret.json';
    $client->setAuthConfig($client_secret_path);
    $client->addScope(Google_Service_Drive::DRIVE);
    // 現在のサーバーのURLを動的に使用（旧サーバーへの転送を防ぐ）
    if (!empty($GLOBALS['config']['google_redirect_base_url'])) {
        $base_url = rtrim($GLOBALS['config']['google_redirect_base_url'], '/') . '/';
    } else {
        // さくら共有SSLはプロキシ経由のため $_SERVER['HTTPS'] が未設定になる場合がある
        $is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || !empty($_SERVER['HTTP_X_SAKURA_FORWARDED_FOR'])  // さくら共有SSL
            || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
        $protocol = $is_https ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'weaver-ec.sakura.ne.jp';
        // app/orders 配下ではリダイレクトURIにディレクトリパスを含める
        $path = defined('APP_ORDERS_ROOT') && !empty($_SERVER['SCRIPT_NAME'])
            ? rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/'
            : '/';
        $base_url = $protocol . '://' . $host . $path;
    }
    $client->setRedirectUri($base_url . $redirect_uri);
    $client->setAccessType('offline');
    $client->setApprovalPrompt('force');
    
    return $client;
}

// テスト前
// トークン取得
function get_google_token($redirect_uri = '') {
    $access_token = [];
	
	$token_file = isset($_SESSION['user_name']) ? 'token_'.$_SESSION['user_name'].'.json' : 'token.json';
	$token_path = defined('APP_ORDERS_ROOT') ? APP_ORDERS_ROOT . '/' . $token_file : $token_file;

	// Google Clientオブジェクト生成    
	$client = create_google_client($redirect_uri);

	// 手動認証からの戻りの場合、codeパラメタからアクセストークンを入手しファイル書き込み
	if (isset($_GET['code'])) {
		// echo $_GET['code']; die(); // コードほしい時だけ使う
		$authCode = $_GET['code'];
		$accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
		file_put_contents($token_path, json_encode($accessToken));
		//echo "コード取得結果："; print_r($accessToken);
	}

	// アクセストークンのファイルがあれば読み込み
    if (file_exists($token_path)) {
        $access_token = json_decode(file_get_contents($token_path), true);
	} else {
		echo 'アクセストークンのファイルがありません'."<br />\n";
		if (!file_exists($token_path)) {
			touch($token_path);
			echo "アクセストークンファイルを作成しました<br />\n";
		}
		$GLOBALS['google_auth_url'] = $client->createAuthUrl();
		return null;
	}

	// アクセストークンの期限をチェック
    $created_timestamp = isset($access_token['created']) ? $access_token['created'] : 0;
    $expires_in = isset($access_token['expires_in']) ? $access_token['expires_in'] : 0;
    $current_time = time();
	
	// アクセストークンが期限切れなら再認証URLをセットして null を返す（die しない）
    if ($created_timestamp + $expires_in < $current_time) {
		$GLOBALS['google_auth_url'] = $client->createAuthUrl();
		return null;
    } else {
	// アクセストークンが期限内ならそのまま使う
        //echo "トークンは有効です。<br />\n";

        // 有効な場合に実行する処理
        $client->setAccessToken($access_token);
    }

    return $client;
}

function auth_manually($client) {
	// 新しいトークンを取得（呼び出し元で die せず再認証ボタンを表示するため、未使用）
	$auth_url = $client->createAuthUrl();
	$GLOBALS['google_auth_url'] = $auth_url;
	echo "トークンは期限切れです。<a href='".filter_var($auth_url, FILTER_SANITIZE_URL)."' class='button1'>再認証</a>";
}

// Google Drive API フォルダID取得
function get_folder_id($service, $folder_name) {
    $parameters = array(
        'q' => "mimeType='application/vnd.google-apps.folder' and name='$folder_name'",
        'fields' => 'files(id)'
    );

    $folders = $service->files->listFiles($parameters);

    if (count($folders->getFiles()) > 0) {
        $folder_id = $folders->getFiles()[0]->getId();
    } else {
        $folder_id = 0;
        echo '該当するフォルダが見つかりませんでした。';
    }

    return $folder_id;
}

// Google Drive API ファイル存在チェック
function is_file_exists_in_folder($service, $folder_id, $file_name) {
    $found = false;

    $files = $service->files->listFiles([
        'q' => "'" . $folder_id . "' in parents and trashed = false",
    ]);

    foreach ($files->getFiles() as $file) {
        if ($file->getName() === $file_name) {
            $found = true;
            break;
        }
    }

    return $found;
}


// ファイルID取得
function get_google_drive_file_id($service, $folder_id, $file_name) {
    $file_id = '';
    
    $query = "name = '$file_name' and '$folder_id' in parents and trashed = false";
    $files = $service->files->listFiles(['q' => $query])->getFiles();

    if (count($files) > 0) {
        $file_id = $files[0]->getId();
    } else {
        // ゴミ箱を含めて再検索
        $query = "name = '$file_name' and '$folder_id' in parents";
        $files = $service->files->listFiles(['q' => $query])->getFiles();

        if (count($files) > 0) {
            $file_id = null; // ゴミ箱に存在する
        }
    }
    
    return $file_id;
}


// Google Drive API ファイル生成
function create_file($service, $folder_id, $file_name, $customer_info) {
    $file_metadata = new Google_Service_Drive_DriveFile([
        'name' => $file_name,
        'parents' => [$folder_id]
    ]);

    $file = $service->files->create($file_metadata, [
        'data' => $customer_info,
        'mimeType' => 'text/plain',
        'uploadType' => 'multipart',
        'fields' => 'id'
    ]);

    echo "ファイル書き込みが完了しました。ファイル名: ".htmlspecialchars($file_name, ENT_QUOTES, 'UTF-8')."<br />\n";
	return $file->id;
}

// Google Drive API ファイル追記
function append_file($service, $file_id, $customer_info) {
	echo "ファイルID：".htmlspecialchars((string)$file_id, ENT_QUOTES, 'UTF-8')."<br />\n";
	
	echo "append_file()\n";
	
	try {
		$file = $service->files->get($file_id);
		$download_url = $file->getDownloadUrl();
		$request = new Google_Http_Request($download_url, 'GET', null, null);
		$http_request = $service->getClient()->getAuth()->authenticatedRequest($request);
		if ($http_request->getResponseHttpCode() == 200) {
			echo "ファイル内容<br>";
			echo htmlspecialchars($http_request->getResponseBody(), ENT_QUOTES, 'UTF-8');
		} else {
			echo "ダウンロードエラーです";
			return null;
		}

	} catch(Google_Exception $e) {
		echo htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
	}
}


?>
