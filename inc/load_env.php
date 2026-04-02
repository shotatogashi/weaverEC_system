<?php
/**
 * プロジェクトルートの .env を読み込む（setting.php より前に require）
 */
if (defined('WEAVER_DOTENV_LOADED')) {
    return;
}
define('WEAVER_DOTENV_LOADED', true);

$env_file = dirname(__DIR__) . '/.env';
if (file_exists($env_file)) {
    foreach (file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) {
            continue;
        }
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value, " \t\n\r\0\x0B\"'");
            putenv($name . '=' . $value);
            $_ENV[$name] = $value;
        }
    }
}
