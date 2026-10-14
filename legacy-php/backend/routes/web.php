<?php
declare(strict_types=1);

/**
 * Declarative route map (reference). Active dispatch lives in the project root `index.php`
 * so Apache/XAMPP can use a single front controller with mod_rewrite.
 *
 * Pattern: GET|POST path → Controller::method
 */
return [
    'GET login' => 'AuthController::showLogin',
    'POST login' => 'AuthController::loginPost',
    'GET logout' => 'AuthController::logout',
    'GET dashboard' => 'DashboardController::index',
    'GET kitchen' => 'KitchenController::index',
    'GET pos' => 'OrderController::posPage',
    'GET orders' => 'OrderController::ordersListPage',
    'GET menu' => 'MenuController::managePage',
    'GET tables' => 'TableController::page',
    'GET invoice/{id}' => 'OrderController::invoicePage',
];
