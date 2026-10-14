<?php
declare(strict_types=1);

/**
 * PHP built-in server front controller (XAMPP PHP: php -S localhost:8080 router.php).
 * Apache continues to use .htaccess + index.php as documented in README.
 */
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$uri = rawurldecode((string) $uri);
$path = __DIR__ . $uri;
if ($uri !== '/' && $uri !== '' && is_file($path)) {
    return false;
}

$_GET['route'] = trim($uri, '/');
require __DIR__ . '/index.php';
