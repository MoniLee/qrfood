<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

final class User
{
    public static function findByEmail(string $email): ?array
    {
        $st = Database::pdo()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $st->execute([$email]);
        $row = $st->fetch();
        return $row ?: null;
    }

    public static function findById(int $id): ?array
    {
        $st = Database::pdo()->prepare('SELECT id, email, role, full_name, created_at FROM users WHERE id = ? LIMIT 1');
        $st->execute([$id]);
        $row = $st->fetch();
        return $row ?: null;
    }

    public static function verifyPassword(string $plain, string $hash): bool
    {
        return password_verify($plain, $hash);
    }
}
