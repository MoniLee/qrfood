<?php
declare(strict_types=1);

function app_config(string $key, mixed $default = null): mixed
{
    $c = APP_CONFIG;
    return $c[$key] ?? $default;
}

function base_url(string $path = ''): string
{
    $p = ltrim($path, '/');
    if ($p === '') {
        return BASE_PATH === '' ? '/' : BASE_PATH . '/';
    }
    return (BASE_PATH === '' ? '' : BASE_PATH) . '/' . $p;
}

function asset_url(string $path): string
{
    return base_url('frontend/' . ltrim($path, '/'));
}

/** Public URL for menu image path stored under frontend/ (e.g. assets/uploads/menu/file.jpg) */
function public_image_url(?string $imagePath): ?string
{
    if ($imagePath === null || $imagePath === '') {
        return null;
    }
    $t = trim($imagePath);
    if (preg_match('#^https?://#i', $t)) {
        return $t;
    }

    return asset_url(ltrim($t, '/'));
}

/** Resolve menu image for API/UI: external URL unchanged, local path via public_image_url */
function menu_item_image_url(?string $imagePath): ?string
{
    return public_image_url($imagePath);
}

/**
 * @param list<array<string,mixed>> $items
 * @return list<array<string,mixed>>
 */
function menu_items_with_resolved_images(array $items): array
{
    foreach ($items as &$row) {
        $row['image_url'] = menu_item_image_url($row['image_path'] ?? null);
    }
    unset($row);

    return $items;
}

function redirect(string $to): never
{
    if (str_starts_with($to, 'http')) {
        header('Location: ' . $to);
        exit;
    }
    header('Location: ' . base_url($to));
    exit;
}

function json_response(array $data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    exit;
}

function require_method(string $method): void
{
    if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== strtoupper($method)) {
        json_response(['success' => false, 'error' => 'Method not allowed'], 405);
    }
}

function input_json(): array
{
    $raw = file_get_contents('php://input') ?: '';
    if ($raw === '') {
        return [];
    }
    try {
        $d = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        return is_array($d) ? $d : [];
    } catch (Throwable) {
        return [];
    }
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function verify_csrf(?string $token): bool
{
    if ($token === null || $token === '') {
        return false;
    }
    return hash_equals($_SESSION['_csrf'] ?? '', $token);
}

function generate_order_code(PDO $pdo): string
{
    $prefix = app_config('order_code_prefix', 'ORD');
    for ($i = 0; $i < 20; $i++) {
        $code = $prefix . '-' . strtoupper(bin2hex(random_bytes(3)));
        $st = $pdo->prepare('SELECT id FROM orders WHERE order_code = ? LIMIT 1');
        $st->execute([$code]);
        if (!$st->fetch()) {
            return $code;
        }
    }
    return $prefix . '-' . (string) time();
}

function e(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Current request path from router (e.g. dashboard, invoice/12). */
function current_route(): string
{
    return trim((string) ($_GET['route'] ?? ''), '/');
}

/** First segment for active nav highlighting. */
function nav_segment(): string
{
    $r = current_route();
    if ($r === '') {
        return '';
    }
    $parts = explode('/', $r);

    return $parts[0] ?? '';
}

/** Human label for completed orders (dine-in vs delivery). */
function order_completed_label(array $order): string
{
    return ($order['type'] ?? '') === 'DELIVERY' ? 'Delivered' : 'Served';
}

function sanitize_string(?string $s, int $max = 500): string
{
    if ($s === null) {
        return '';
    }
    $s = trim($s);
    if (mb_strlen($s) > $max) {
        $s = mb_substr($s, 0, $max);
    }
    return $s;
}
