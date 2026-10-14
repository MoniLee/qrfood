<?php
declare(strict_types=1);

namespace App\Models;

final class Category
{
    /** @return list<array<string,mixed>> */
    public static function allOrdered(): array
    {
        $st = Database::pdo()->query('SELECT * FROM categories ORDER BY sort_order ASC, name ASC');
        return $st->fetchAll() ?: [];
    }

    public static function find(int $id): ?array
    {
        $st = Database::pdo()->prepare('SELECT * FROM categories WHERE id = ? LIMIT 1');
        $st->execute([$id]);
        $row = $st->fetch();
        return $row ?: null;
    }

    public static function create(string $name, int $sortOrder = 0): int
    {
        $st = Database::pdo()->prepare('INSERT INTO categories (name, sort_order) VALUES (?, ?)');
        $st->execute([$name, $sortOrder]);
        return (int) Database::pdo()->lastInsertId();
    }

    public static function update(int $id, string $name, int $sortOrder): void
    {
        $st = Database::pdo()->prepare('UPDATE categories SET name = ?, sort_order = ? WHERE id = ?');
        $st->execute([$name, $sortOrder, $id]);
    }

    public static function delete(int $id): void
    {
        $st = Database::pdo()->prepare('DELETE FROM categories WHERE id = ?');
        $st->execute([$id]);
    }
}
