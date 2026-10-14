<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;

final class AuthController
{
    public function showLogin(): void
    {
        \App\Middleware\guest_only();
        require dirname(__DIR__, 2) . '/frontend/pages/login.php';
    }

    public function loginPost(): void
    {
        \App\Middleware\guest_only();
        require_method('POST');
        $email = sanitize_string($_POST['email'] ?? '', 128);
        $password = $_POST['password'] ?? '';
        $csrf = $_POST['csrf_token'] ?? '';

        if (!verify_csrf($csrf)) {
            $_SESSION['flash_error'] = 'Invalid session. Please try again.';
            redirect('login');
        }

        if ($email === '' || $password === '') {
            $_SESSION['flash_error'] = 'Email and password are required.';
            redirect('login');
        }

        $user = User::findByEmail($email);
        if (!$user || !User::verifyPassword($password, $user['password_hash'])) {
            $_SESSION['flash_error'] = 'Invalid credentials.';
            redirect('login');
        }

        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['full_name'] = $user['full_name'];

        if ($user['role'] === 'admin') {
            redirect('dashboard');
        }
        if ($user['role'] === 'staff') {
            redirect('pos');
        }
        if ($user['role'] === 'customer') {
            redirect('menu');
        }
        redirect('kitchen');
    }

    public function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], (bool) $p['secure'], (bool) $p['httponly']);
        }
        session_destroy();
        redirect('login');
    }
}
