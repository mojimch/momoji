<?php

$localConfigPath = __DIR__ . '/database.local.php';
$localConfig = is_file($localConfigPath) ? require $localConfigPath : [];
if (!is_array($localConfig)) {
    throw new RuntimeException('Konfigurasi database lokal harus mengembalikan array.');
}

$databaseConfigValue = static function (string $name, string $fallback) use ($localConfig): string {
    $environmentValue = getenv($name);
    if ($environmentValue !== false && $environmentValue !== '') {
        return $environmentValue;
    }
    if (isset($localConfig[$name]) && is_scalar($localConfig[$name])) {
        return (string) $localConfig[$name];
    }
    return $fallback;
};

define('DB_HOST', $databaseConfigValue('DB_HOST', '127.0.0.1'));
define('DB_PORT', $databaseConfigValue('DB_PORT', '3306'));
define('DB_NAME', $databaseConfigValue('DB_NAME', 'momoji_db'));
define('DB_USER', $databaseConfigValue('DB_USER', 'root'));
define('DB_PASS', $databaseConfigValue('DB_PASS', ''));
