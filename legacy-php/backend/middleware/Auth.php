<?php
declare(strict_types=1);

namespace App\Middleware;

function require_login(): void
{
    if (empty($_SESSION['user_id'])) {
        if (is_ajax_request()) {
            json_response(['success' => false, 'error' => 'Unauthorized', 'redirect' => base_url('login')], 401);
        }
        redirect('login');
    }
}

function require_role(string ...$roles): void
{
    require_login();
    $role = $_SESSION['role'] ?? '';
    if (!in_array($role, $roles, true)) {
        if (is_ajax_request()) {
            json_response(['success' => false, 'error' => 'Forbidden'], 403);
        }
        redirect('');
    }
}

function is_ajax_request(): bool
{
    $xhr = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
    if (strtolower($xhr) === 'xmlhttprequest') {
        return true;
    }
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    return strpos($accept, 'application/json') !== false;
}

function guest_only(): void
{
    if (!empty($_SESSION['user_id'])) {
        $role = $_SESSION['role'] ?? '';
        if ($role === 'admin') {
            redirect('dashboard');
        }
        if ($role === 'staff') {
            redirect('pos');
        }
        if ($role === 'customer') {
            redirect('menu');
        }
        redirect('kitchen');
    }
}
