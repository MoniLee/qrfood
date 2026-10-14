<?php
declare(strict_types=1);

$config = require __DIR__ . '/config.php';
$dbCfg = require __DIR__ . '/database.php';

$scriptName = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
$basePath = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
if ($basePath === '/' || $basePath === '.') {
    $basePath = '';
}
if (!empty($config['base_url'])) {
    $basePath = rtrim($config['base_url'], '/');
}

define('BASE_PATH', $basePath);
define('APP_CONFIG', $config);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name($config['session_name']);
    session_start();
}

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relative = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../middleware/Auth.php';
