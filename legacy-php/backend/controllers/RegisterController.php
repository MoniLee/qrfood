<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\Database;

final class RegisterController
{
    public function showForm(): void
    {
        \App\Middleware\guest_only();
        require dirname(__DIR__, 2) . '/frontend/pages/register.php';
    }

    public function submitForm(): void
    {
        \App\Middleware\guest_only();
        require_method('POST');
        $email = sanitize_string($_POST['email'] ?? '', 128);
        $name = sanitize_string($_POST['full_name'] ?? '', 128);
        $password = $_POST['password'] ?? '';
        $csrf = $_POST['csrf_token'] ?? '';

        if (!verify_csrf($csrf)) {
            $_SESSION['flash_error'] = 'Invalid session. Please try again.';
            redirect('register');
        }

        if ($email === '' || $name === '' || strlen($password) < 6) {
            $_SESSION['flash_error'] = 'All fields are required. Password must be at least 6 characters.';
            redirect('register');
        }

        $pdo = Database::pdo();
        $st = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $st->execute([$email]);
        if ($st->fetch()) {
            $_SESSION['flash_error'] = 'Email is already registered.';
            redirect('register');
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $st = $pdo->prepare('INSERT INTO users (email, password_hash, role, full_name) VALUES (?, ?, ?, ?)');
        $st->execute([$email, $hash, 'customer', $name]);

        $newId = (int) $pdo->lastInsertId();
        
        $_SESSION['user_id'] = $newId;
        $_SESSION['email'] = $email;
        $_SESSION['role'] = 'customer';
        $_SESSION['full_name'] = $name;

        redirect('menu');
    }
}
