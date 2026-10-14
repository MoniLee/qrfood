<?php
declare(strict_types=1);

namespace App\Controllers;

final class PublicMenuController
{
    public function index(): void
    {
        require dirname(__DIR__, 2) . '/frontend/pages/public_menu.php';
    }
}
