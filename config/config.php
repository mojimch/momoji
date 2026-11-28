<?php

define('APP_ENV', getenv('APP_ENV') ?: 'local');
define('APP_URL', rtrim(getenv('APP_URL') ?: 'http://localhost/momoji', '/'));
define('APP_BASE_PATH', (static function (): string {
    $path = rtrim((string) parse_url(APP_URL, PHP_URL_PATH), '/');
    return basename($path) === 'public' ? rtrim(dirname($path), '/') : $path;
})());
define('APP_NAME', getenv('APP_NAME') ?: 'Momoji');
$debugSetting = getenv('APP_DEBUG');
if ($debugSetting === false || $debugSetting === '') {
    $debugSetting = APP_ENV === 'production' ? 'false' : 'true';
}
define(
    'APP_DEBUG',
    filter_var($debugSetting, FILTER_VALIDATE_BOOLEAN)
);
define('APP_PATH', dirname(__DIR__) . '/app');
define('PUBLIC_PATH', dirname(__DIR__) . '/public');
define('STORAGE_PATH', dirname(__DIR__) . '/storage');
define('UPLOAD_PATH', STORAGE_PATH . '/uploads');

ini_set('session.cookie_httponly', '1');
ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_samesite', 'Lax');
if (APP_ENV === 'production') {
    ini_set('session.cookie_secure', '1');
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
} else {
    ini_set('display_errors', APP_DEBUG ? '1' : '0');
}
