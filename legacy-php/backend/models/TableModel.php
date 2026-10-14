<?php
declare(strict_types=1);

namespace App\Models;

final class TableModel
{
    /** @return list<array<string,mixed>> */
    public static function all(): array
    {
        $st = Database::pdo()->query('SELECT * FROM `tables` ORDER BY label');
        return $st->fetchAll() ?: [];
    }

    public static function find(int $id): ?array
    {
        $st = Database::pdo()->prepare('SELECT * FROM `tables` WHERE id = ? LIMIT 1');
        $st->execute([$id]);
        $row = $st->fetch();
        return $row ?: null;
    }

    public static function create(string $label, int $capacity, string $status = 'available'): int
    {
        $st = Database::pdo()->prepare('INSERT INTO `tables` (label, capacity, status) VALUES (?, ?, ?)');
        $st->execute([$label, $capacity, $status]);
        return (int) Database::pdo()->lastInsertId();
    }

    public static function update(int $id, string $label, int $capacity, string $status): void
    {
        $st = Database::pdo()->prepare('UPDATE `tables` SET label = ?, capacity = ?, status = ? WHERE id = ?');
        $st->execute([$label, $capacity, $status, $id]);
    }

    public static function delete(int $id): void
    {
        $st = Database::pdo()->prepare('DELETE FROM `tables` WHERE id = ?');
        $st->execute([$id]);
    }

    public static function setStatus(int $id, string $status): void
    {
        $st = Database::pdo()->prepare('UPDATE `tables` SET status = ? WHERE id = ?');
        $st->execute([$status, $id]);
    }

    public static function countActiveOccupied(): int
    {
        $st = Database::pdo()->query("SELECT COUNT(*) FROM `tables` WHERE status = 'occupied'");
        return (int) $st->fetchColumn();
    }

    public static function countAvailable(): int
    {
        $st = Database::pdo()->query("SELECT COUNT(*) FROM `tables` WHERE status = 'available'");
        return (int) $st->fetchColumn();
    }
}
