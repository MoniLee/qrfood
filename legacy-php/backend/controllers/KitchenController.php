<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\Order;

final class KitchenController
{
    public function index(): void
    {
        \App\Middleware\require_role('admin', 'kitchen');
        require dirname(__DIR__, 2) . '/frontend/pages/kitchen.php';
    }

    public function apiOrders(): void
    {
        \App\Middleware\require_role('admin', 'kitchen');
        $orders = Order::listForKitchen();
        $out = [];
        foreach ($orders as $o) {
            $out[] = [
                'order' => $o,
                'items' => Order::itemsForOrder((int) $o['id']),
            ];
        }
        json_response(['success' => true, 'data' => $out, 'server_time' => date('c')]);
    }

    public function apiUpdateStatus(): void
    {
        \App\Middleware\require_role('admin', 'kitchen');
        require_method('POST');
        $body = input_json();
        $id = (int) ($body['order_id'] ?? 0);
        $status = $body['status'] ?? '';
        $allowed = ['pending', 'preparing', 'ready', 'completed'];
        if ($id < 1 || !in_array($status, $allowed, true)) {
            json_response(['success' => false, 'error' => 'Invalid order or status'], 400);
        }
        Order::updateStatus($id, $status);
        json_response(['success' => true]);
    }
}
