<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

final class Order
{
    public static function create(array $header, array $lines): int
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $st = $pdo->prepare(
                'INSERT INTO orders (order_code, type, user_id, table_id, customer_name, customer_phone, delivery_address, status, subtotal, gst_rate, gst_amount, total)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $st->execute([
                $header['order_code'],
                $header['type'],
                $header['user_id'] ?? null,
                $header['table_id'],
                $header['customer_name'],
                $header['customer_phone'],
                $header['delivery_address'],
                $header['status'],
                $header['subtotal'],
                $header['gst_rate'],
                $header['gst_amount'],
                $header['total'],
            ]);
            $orderId = (int) $pdo->lastInsertId();
            $li = $pdo->prepare(
                'INSERT INTO order_items (order_id, menu_item_id, quantity, unit_price, line_total) VALUES (?, ?, ?, ?, ?)'
            );
            foreach ($lines as $line) {
                $li->execute([
                    $orderId,
                    $line['menu_item_id'],
                    $line['quantity'],
                    $line['unit_price'],
                    $line['line_total'],
                ]);
            }
            if ($header['type'] === 'DINE_IN' && $header['table_id']) {
                $t = $pdo->prepare("UPDATE `tables` SET status = 'occupied' WHERE id = ?");
                $t->execute([(int) $header['table_id']]);
            }
            $pdo->commit();
            return $orderId;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function find(int $id): ?array
    {
        $st = Database::pdo()->prepare(
            'SELECT o.*, t.label AS table_label FROM orders o
             LEFT JOIN `tables` t ON t.id = o.table_id WHERE o.id = ? LIMIT 1'
        );
        $st->execute([$id]);
        $row = $st->fetch();
        return $row ?: null;
    }

    /** @return list<array<string,mixed>> */
    public static function itemsForOrder(int $orderId): array
    {
        $st = Database::pdo()->prepare(
            'SELECT oi.*, m.name AS item_name FROM order_items oi
             JOIN menu_items m ON m.id = oi.menu_item_id WHERE oi.order_id = ? ORDER BY oi.id'
        );
        $st->execute([$orderId]);
        return $st->fetchAll() ?: [];
    }

    public static function updateStatus(int $id, string $status): void
    {
        $pdo = Database::pdo();
        $order = self::find($id);
        if (!$order) {
            return;
        }
        $old = $order['status'];
        $st = $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?');
        $st->execute([$status, $id]);

        if ($old !== 'completed' && $status === 'completed' && $order['type'] === 'DINE_IN' && $order['table_id']) {
            $t = $pdo->prepare("UPDATE `tables` SET status = 'available' WHERE id = ?");
            $t->execute([(int) $order['table_id']]);
        }
    }

    /** @return list<array<string,mixed>> */
    public static function listForKitchen(): array
    {
        $sql = "SELECT o.*, t.label AS table_label FROM orders o
                LEFT JOIN `tables` t ON t.id = o.table_id
                WHERE o.status IN ('pending', 'preparing', 'ready')
                ORDER BY o.created_at ASC";
        return Database::pdo()->query($sql)->fetchAll() ?: [];
    }

    public static function listRecent(int $limit = 100): array
    {
        $st = Database::pdo()->prepare(
            'SELECT o.*, t.label AS table_label FROM orders o
             LEFT JOIN `tables` t ON t.id = o.table_id
             ORDER BY o.created_at DESC LIMIT ?'
        );
        $st->bindValue(1, $limit, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll() ?: [];
    }

    public static function listForUser(int $userId, int $limit = 100): array
    {
        $st = Database::pdo()->prepare(
            'SELECT o.*, t.label AS table_label FROM orders o
             LEFT JOIN `tables` t ON t.id = o.table_id
             WHERE o.user_id = ?
             ORDER BY o.created_at DESC LIMIT ?'
        );
        $st->bindValue(1, $userId, PDO::PARAM_INT);
        $st->bindValue(2, $limit, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll() ?: [];
    }

    public static function countAll(): int
    {
        return (int) Database::pdo()->query('SELECT COUNT(*) FROM orders')->fetchColumn();
    }

    public static function revenueCompleted(): float
    {
        $st = Database::pdo()->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status = 'completed'");
        return (float) $st->fetchColumn();
    }

    public static function countToday(): int
    {
        $st = Database::pdo()->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()");
        return (int) $st->fetchColumn();
    }

    public static function revenueToday(): float
    {
        $st = Database::pdo()->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE DATE(created_at) = CURDATE() AND status = 'completed'");
        return (float) $st->fetchColumn();
    }

    /** Revenue by day for last N days */
    /** @return list<array{day:string,total:float}> */
    public static function revenueLastDays(int $days = 7): array
    {
        $st = Database::pdo()->prepare(
            "SELECT DATE(created_at) AS day, COALESCE(SUM(total),0) AS total
             FROM orders
             WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY) AND status = 'completed'
             GROUP BY DATE(created_at)
             ORDER BY day ASC"
        );
        $st->bindValue(1, $days, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll() ?: [];
    }

    /** @return list<array{status:string,cnt:int}> */
    public static function countsByStatus(): array
    {
        $sql = "SELECT status, COUNT(*) AS cnt FROM orders GROUP BY status";
        return Database::pdo()->query($sql)->fetchAll() ?: [];
    }
}
