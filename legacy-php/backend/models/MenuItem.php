<?php
declare(strict_types=1);

namespace App\Models;

final class MenuItem
{
    /** @return list<array<string,mixed>> */
    public static function allWithCategory(bool $activeOnly = false): array
    {
        $sql = 'SELECT m.*, c.name AS category_name FROM menu_items m
                JOIN categories c ON c.id = m.category_id';
        if ($activeOnly) {
            $sql .= ' WHERE m.is_active = 1';
        }
        $sql .= ' ORDER BY c.sort_order, m.name';
        return Database::pdo()->query($sql)->fetchAll() ?: [];
    }

    public static function find(int $id): ?array
    {
        $st = Database::pdo()->prepare(
            'SELECT m.*, c.name AS category_name FROM menu_items m
             JOIN categories c ON c.id = m.category_id WHERE m.id = ? LIMIT 1'
        );
        $st->execute([$id]);
        $row = $st->fetch();
        return $row ?: null;
    }

    public static function create(array $data): int
    {
        $st = Database::pdo()->prepare(
            'INSERT INTO menu_items (category_id, name, description, price, image_path, is_active)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $st->execute([
            (int) $data['category_id'],
            $data['name'],
            $data['description'] ?? '',
            (float) $data['price'],
            $data['image_path'] ?? null,
            (int) ($data['is_active'] ?? 1),
        ]);
        return (int) Database::pdo()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $st = Database::pdo()->prepare(
            'UPDATE menu_items SET category_id = ?, name = ?, description = ?, price = ?, image_path = ?, is_active = ? WHERE id = ?'
        );
        $st->execute([
            (int) $data['category_id'],
            $data['name'],
            $data['description'] ?? '',
            (float) $data['price'],
            $data['image_path'] ?? null,
            (int) ($data['is_active'] ?? 1),
            $id,
        ]);
    }

    public static function delete(int $id): void
    {
        $st = Database::pdo()->prepare('DELETE FROM menu_items WHERE id = ?');
        $st->execute([$id]);
    }

    /** @return list<array{menu_item_id:int,total_qty:int,name:string}> */
    public static function mostOrdered(int $limit = 5): array
    {
        $st = Database::pdo()->prepare(
            'SELECT oi.menu_item_id, SUM(oi.quantity) AS total_qty, m.name
             FROM order_items oi
             JOIN menu_items m ON m.id = oi.menu_item_id
             JOIN orders o ON o.id = oi.order_id
             WHERE o.status = \'completed\'
             GROUP BY oi.menu_item_id, m.name
             ORDER BY total_qty DESC
             LIMIT ?'
        );
        $st->bindValue(1, $limit, \PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll() ?: [];
    }
}
