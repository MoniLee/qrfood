<?php
declare(strict_types=1);

require_once __DIR__ . '/backend/config/bootstrap.php';

use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\KitchenController;
use App\Controllers\MenuController;
use App\Controllers\OrderController;
use App\Controllers\TableController;
use App\Controllers\LocationController;

$route = isset($_GET['route']) ? (string) $_GET['route'] : '';
$route = trim($route, '/');

$parts = explode('/', $route);
$main = $parts[0] ?? '';

match (true) {
    $main === '' => (new \App\Controllers\HomeController())->index(),
    $main === 'login' && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' => (new AuthController())->showLogin(),
    $main === 'login' && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' => (new AuthController())->loginPost(),
    $main === 'register' && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' => (new \App\Controllers\RegisterController())->showForm(),
    $main === 'register' && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' => (new \App\Controllers\RegisterController())->submitForm(),
    $main === 'logout' => (new AuthController())->logout(),

    $main === 'dashboard' => (new DashboardController())->index(),
    $main === 'kitchen' => (new KitchenController())->index(),
    $main === 'pos' => (new OrderController())->posPage(),
    $main === 'orders' => (new OrderController())->ordersListPage(),
    $main === 'manage-menu' => (new MenuController())->managePage(),
    $main === 'menu' => (new \App\Controllers\PublicMenuController())->index(),
    $main === 'tables' => (new TableController())->page(),

    $main === 'invoice' && isset($parts[1]) && ctype_digit($parts[1]) => (new OrderController())->invoicePage((int) $parts[1]),

    $main === 'api' && ($parts[1] ?? '') === 'stats' => (new DashboardController())->apiStats(),
    $main === 'api' && ($parts[1] ?? '') === 'kitchen-orders' => (new KitchenController())->apiOrders(),
    $main === 'api' && ($parts[1] ?? '') === 'kitchen-status' => (new KitchenController())->apiUpdateStatus(),

    $main === 'api' && ($parts[1] ?? '') === 'menu' => (new MenuController())->apiList(),
    $main === 'api' && ($parts[1] ?? '') === 'categories' => (new MenuController())->apiCategories(),
    $main === 'api' && ($parts[1] ?? '') === 'menu-save' => (new MenuController())->apiSave(),
    $main === 'api' && ($parts[1] ?? '') === 'menu-delete' => (new MenuController())->apiDelete(),
    $main === 'api' && ($parts[1] ?? '') === 'menu-upload' => (new MenuController())->apiUpload(),
    $main === 'api' && ($parts[1] ?? '') === 'category-save' => (new MenuController())->apiSaveCategory(),
    $main === 'api' && ($parts[1] ?? '') === 'category-delete' => (new MenuController())->apiDeleteCategory(),

    $main === 'api' && ($parts[1] ?? '') === 'tables' => (new TableController())->apiList(),
    $main === 'api' && ($parts[1] ?? '') === 'table-save' => (new TableController())->apiSave(),
    $main === 'api' && ($parts[1] ?? '') === 'table-delete' => (new TableController())->apiDelete(),
    $main === 'api' && ($parts[1] ?? '') === 'table-status' => (new TableController())->apiSetStatus(),

    $main === 'api' && ($parts[1] ?? '') === 'order-place' => (new OrderController())->apiPlace(),
    $main === 'api' && ($parts[1] ?? '') === 'order-list' => (new OrderController())->apiList(),
    $main === 'api' && ($parts[1] ?? '') === 'order-status' => (new OrderController())->apiAdminUpdateStatus(),

    $main === 'api' && ($parts[1] ?? '') === 'location-addresses' => (new LocationController())->apiGetAddresses(),
    $main === 'api' && ($parts[1] ?? '') === 'location-save' => (new LocationController())->apiSaveAddress(),
    $main === 'api' && ($parts[1] ?? '') === 'location-check' => (new LocationController())->apiCheckServiceability(),
    $main === 'api' && ($parts[1] ?? '') === 'location-geocode' => (new LocationController())->apiReverseGeocode(),
    $main === 'api' && ($parts[1] ?? '') === 'zone-list' => (new LocationController())->apiListZones(),
    $main === 'api' && ($parts[1] ?? '') === 'zone-save' => (new LocationController())->apiSaveZone(),
    $main === 'api' && ($parts[1] ?? '') === 'zone-delete' => (new LocationController())->apiDeleteZone(),

    default => (function (): void {
        http_response_code(404);
        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Not found</title></head>';
        echo '<body style="font-family:system-ui,sans-serif;padding:2rem;">';
        echo '<h1>404 — Not found</h1><p><a href="' . htmlspecialchars(base_url('login'), ENT_QUOTES) . '">Go to login</a></p>';
        echo '</body></html>';
    })(),
};
