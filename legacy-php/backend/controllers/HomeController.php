<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\MenuItem;

final class HomeController
{
    public function index(): void
    {
        // Fetch some generic data to show on the landing page
        // "Chef's special" and "Featured dishes" can just be active menu items
        $featured = MenuItem::allWithCategory(true);
        // Only take first 6 for the landing page showcase
        $featured = array_slice($featured, 0, 6);
        require dirname(__DIR__, 2) . '/frontend/pages/home.php';
    }
}
