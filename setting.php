<?php
ini_set( 'display_errors', 1 );

$config['write_drive_flg'] = TRUE;
$config['debug_flg'] = TRUE;

// 楽天API
$license_key_file = __DIR__ . '/data/license_key.txt';
$license_key = trim(file_get_contents($license_key_file));

//$license_key = "SL244394_iV3dbunLik56HaK7";
$secret_key = "SP244394_YXrXaGpjSSKIfxCh";


?>