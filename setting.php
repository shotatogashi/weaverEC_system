<?php

ini_set( 'display_errors', 1 );



$config['write_drive_flg'] = TRUE;

$config['debug_flg'] = TRUE;



// Google OAuth リダイレクト用ベースURL（空なら自動検出。redirect_uri_mismatch 時は明示指定を推奨）

// $config['google_redirect_base_url'] = 'https://weaver-ec.sakura.ne.jp/';



// 楽天API

$license_key_file = __DIR__ . '/data/license_key.txt';

$license_key = preg_replace('/[^a-zA-Z0-9_]/', '', file_get_contents($license_key_file));



//$license_key = "SL244394_iV3dbunLik56HaK7";



$secret_key = "SP244394_YXrXaGpjSSKIfxCh";

?>

