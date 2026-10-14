<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\MenuItem;
use App\Models\Order;
use App\Models\TableModel;

final class DashboardController
{
    public function index(): void
    {
        \App\Middleware\require_role('admin');
        $stats = [
            'total_orders' => Order::countAll(),
            'revenue' => Order::revenueCompleted(),
            'orders_today' => Order::countToday(),
            'revenue_today' => Order::revenueToday(),
            'tables_occupied' => TableModel::countActiveOccupied(),
            'tables_available' => TableModel::countAvailable(),
        ];
        $popular = MenuItem::mostOrdered(8);
        $statusCounts = Order::countsByStatus();
        $chartRevenue = Order::revenueLastDays(7);
        require dirname(__DIR__, 2) . '/frontend/pages/dashboard.php';
    }

    public function apiStats(): void
    {
        \App\Middleware\require_role('admin');
        json_response([
            'success' => true,
            'data' => [
                'total_orders' => Order::countAll(),
                'revenue' => Order::revenueCompleted(),
                'orders_today' => Order::countToday(),
                'revenue_today' => Order::revenueToday(),
                'tables_occupied' => TableModel::countActiveOccupied(),
                'popular' => MenuItem::mostOrdered(8),
                'status_counts' => Order::countsByStatus(),
                'revenue_by_day' => Order::revenueLastDays(7),
            ],
        ]);
    }
}
