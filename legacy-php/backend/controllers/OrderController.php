<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\MenuItem;
use App\Models\Order;
use App\Models\TableModel;

final class OrderController
{
    public function posPage(): void
    {
        \App\Middleware\require_role('admin', 'staff');
        $tables = TableModel::all();
        $categories = \App\Models\Category::allOrdered();
        $menuInitial = MenuItem::allWithCategory(true);
        $stats = [
            'orders' => Order::countToday(),
            'revenue' => Order::revenueToday(),
            'tables_occ' => TableModel::countActiveOccupied()
        ];
        require dirname(__DIR__, 2) . '/frontend/pages/pos.php';
    }

    public function ordersListPage(): void
    {
        \App\Middleware\require_role('admin', 'staff', 'customer');
        $role = $_SESSION['role'] ?? '';
        if ($role === 'customer') {
            $orders = Order::listForUser((int) $_SESSION['user_id'], 50);
        } else {
            $orders = Order::listRecent(80);
        }
        require dirname(__DIR__, 2) . '/frontend/pages/orders.php';
    }

    public function invoicePage(int $id): void
    {
        \App\Middleware\require_login();
        $order = Order::find($id);
        if (!$order) {
            http_response_code(404);
            echo 'Order not found';
            return;
        }
        $role = $_SESSION['role'] ?? '';
        if ($role === 'customer') {
            $uid = (int) ($_SESSION['user_id'] ?? 0);
            $oid = isset($order['user_id']) ? (int) $order['user_id'] : 0;
            if ($uid < 1 || $oid !== $uid) {
                http_response_code(403);
                echo 'You do not have access to this invoice.';
                return;
            }
        } elseif (!in_array($role, ['admin', 'staff'], true)) {
            http_response_code(403);
            echo 'Forbidden';
            return;
        }
        $items = Order::itemsForOrder($id);
        require dirname(__DIR__, 2) . '/frontend/pages/invoice.php';
    }

    public function apiPlace(): void
    {
        \App\Middleware\require_role('admin', 'staff', 'customer');
        require_method('POST');
        $body = input_json();
        $type = $body['type'] ?? '';
        if (!in_array($type, ['DINE_IN', 'DELIVERY'], true)) {
            json_response(['success' => false, 'error' => 'Invalid order type'], 400);
        }
        $cart = $body['cart'] ?? [];
        if (!is_array($cart) || count($cart) < 1) {
            json_response(['success' => false, 'error' => 'Cart is empty'], 400);
        }

        $tableId = null;
        $customerName = null;
        $phone = null;
        $address = null;

        if ($type === 'DINE_IN') {
            $tableId = isset($body['table_id']) ? (int) $body['table_id'] : 0;
            if ($tableId < 1) {
                json_response(['success' => false, 'error' => 'Select a table for dine-in'], 400);
            }
            $t = TableModel::find($tableId);
            if (!$t) {
                json_response(['success' => false, 'error' => 'Invalid table'], 400);
            }
            // Optionally capture customer details for dine-in
            $customerName = sanitize_string($body['customer_name'] ?? '', 128) ?: null;
            $phone = sanitize_string($body['customer_phone'] ?? '', 32) ?: null;
        } else {
            $customerName = sanitize_string($body['customer_name'] ?? '', 128);
            $phone = sanitize_string($body['customer_phone'] ?? '', 32);
            $address = sanitize_string($body['delivery_address'] ?? '', 500);
            if ($customerName === '' || $phone === '' || $address === '') {
                json_response(['success' => false, 'error' => 'Name, phone, and address are required for delivery'], 400);
            }
        }

        $pdo = \App\Models\Database::pdo();
        $lines = [];
        $subtotal = 0.0;
        foreach ($cart as $row) {
            $mid = (int) ($row['menu_item_id'] ?? 0);
            $qty = (int) ($row['quantity'] ?? 0);
            if ($mid < 1 || $qty < 1) {
                json_response(['success' => false, 'error' => 'Invalid cart line'], 400);
            }
            $item = MenuItem::find($mid);
            if (!$item || !(int) $item['is_active']) {
                json_response(['success' => false, 'error' => 'Item unavailable: ' . $mid], 400);
            }
            $unit = (float) $item['price'];
            $lineTotal = round($unit * $qty, 2);
            $subtotal += $lineTotal;
            $lines[] = [
                'menu_item_id' => $mid,
                'quantity' => $qty,
                'unit_price' => $unit,
                'line_total' => $lineTotal,
            ];
        }
        $subtotal = round($subtotal, 2);
        $gstRate = (float) app_config('gst_default_rate', 18.0);
        $gstAmount = round($subtotal * ($gstRate / 100), 2);
        $total = round($subtotal + $gstAmount, 2);

        $code = generate_order_code($pdo);
        $userId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
        $orderId = Order::create(
            [
                'order_code' => $code,
                'type' => $type,
                'user_id' => $userId,
                'table_id' => $tableId,
                'customer_name' => $customerName,
                'customer_phone' => $phone,
                'delivery_address' => $address,
                'status' => 'pending',
                'subtotal' => $subtotal,
                'gst_rate' => $gstRate,
                'gst_amount' => $gstAmount,
                'total' => $total,
            ],
            $lines
        );

        json_response(['success' => true, 'order_id' => $orderId, 'order_code' => $code]);
    }

    public function apiList(): void
    {
        \App\Middleware\require_role('admin', 'staff');
        $limit = min(200, max(1, (int) ($_GET['limit'] ?? 100)));
        json_response(['success' => true, 'data' => Order::listRecent($limit)]);
    }

    public function apiAdminUpdateStatus(): void
    {
        \App\Middleware\require_role('admin', 'staff');
        require_method('POST');
        $body = input_json();
        $id = (int) ($body['order_id'] ?? 0);
        $status = $body['status'] ?? '';
        $allowed = ['pending', 'preparing', 'ready', 'completed'];
        if ($id < 1 || !in_array($status, $allowed, true)) {
            json_response(['success' => false, 'error' => 'Invalid request'], 400);
        }
        Order::updateStatus($id, $status);
        json_response(['success' => true]);
    }
}
