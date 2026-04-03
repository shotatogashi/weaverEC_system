<?php
/**
 * Google OAuth トークンの永続化（private 領域の JSON）。
 * 形式: access_token, refresh_token, expires_at (UNIX), expires_in（OAuth 応答の有効期間秒）
 */

/**
 * 旧形式（created + expires_in）および新形式（expires_at）を統合する。
 *
 * @param array<string, mixed> $data
 * @return array<string, mixed>
 */
function weaver_normalize_google_token_data(array $data) {
	if (!empty($data['expires_at']) && is_numeric($data['expires_at'])) {
		$data['expires_at'] = (int) $data['expires_at'];
		if (empty($data['expires_in']) && !empty($data['created']) && is_numeric($data['created'])) {
			$data['expires_in'] = max(1, $data['expires_at'] - (int) $data['created']);
		}
		return $data;
	}
	$created = isset($data['created']) ? (int) $data['created'] : 0;
	$expires_in = isset($data['expires_in']) ? (int) $data['expires_in'] : 0;
	if ($created > 0 && $expires_in > 0) {
		$data['expires_at'] = $created + $expires_in;
	} else {
		$data['expires_at'] = 0;
	}
	return $data;
}

/**
 * OAuth コード交換・リフレッシュ API の応答から保存行を作る。
 *
 * @param array<string, mixed> $response
 * @param array<string, mixed>|null $previous refresh_token 継承用
 * @return array<string, mixed>
 */
function weaver_google_token_from_oauth_response(array $response, $previous = null) {
	$expires_in = isset($response['expires_in']) ? (int) $response['expires_in'] : 3600;
	if ($expires_in < 1) {
		$expires_in = 3600;
	}
	$created = isset($response['created']) ? (int) $response['created'] : time();

	$out = [
		'access_token' => $response['access_token'] ?? '',
		'expires_in' => $expires_in,
		'expires_at' => $created + $expires_in,
	];
	if (!empty($response['refresh_token'])) {
		$out['refresh_token'] = $response['refresh_token'];
	} elseif (is_array($previous) && !empty($previous['refresh_token'])) {
		$out['refresh_token'] = $previous['refresh_token'];
	}
	return $out;
}

/**
 * Google_Client::setAccessToken に渡す配列（expires_at / expires_in と整合）
 *
 * @param array<string, mixed> $data weaver_normalize_google_token_data 済み
 * @return array<string, mixed>
 */
function weaver_google_token_for_client(array $data) {
	$data = weaver_normalize_google_token_data($data);
	$expires_at = (int) $data['expires_at'];
	if (isset($data['expires_in']) && (int) $data['expires_in'] > 0) {
		$expires_in = (int) $data['expires_in'];
	} else {
		// expires_in 欠損時は残り秒で補う（旧ファイル互換）
		$expires_in = max(60, $expires_at - time());
	}
	$created = $expires_at - $expires_in;
	$out = [
		'access_token' => $data['access_token'] ?? '',
		'expires_in' => $expires_in,
		'created' => $created,
	];
	if (!empty($data['refresh_token'])) {
		$out['refresh_token'] = $data['refresh_token'];
	}
	return $out;
}

/**
 * トークンを JSON で保存（refresh_token は空で上書きしない）
 *
 * @param array<string, mixed> $tokenData
 */
function weaver_save_google_token_file($path, array $tokenData) {
	$dir = dirname($path);
	if (!is_dir($dir)) {
		@mkdir($dir, 0700, true);
	}
	$json = json_encode($tokenData, JSON_UNESCAPED_UNICODE);
	if ($json === false) {
		return false;
	}
	return file_put_contents($path, $json, LOCK_EX) !== false;
}

/**
 * アクセストークンが期限切れか（expires_at は UNIX 秒）
 */
function weaver_google_access_token_expired(array $tokenData) {
	$tokenData = weaver_normalize_google_token_data($tokenData);
	return empty($tokenData['expires_at']) || (int) $tokenData['expires_at'] < time();
}
