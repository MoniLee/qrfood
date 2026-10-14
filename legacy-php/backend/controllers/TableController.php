<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\TableModel;

final class TableController
{
    public function page(): void
    {
        \App\Middleware\require_role('admin', 'staff');
        $tables = TableModel::all();
        require dirname(__DIR__, 2) . '/frontend/pages/tables.php';
    }

    public function apiList(): void
    {
        \App\Middleware\require_login();
        json_response(['success' => true, 'data' => TableModel::all()]);
    }

    public function apiSave(): void
    {
        \App\Middleware\require_role('admin');
        require_method('POST');
        $body = input_json();
        $id = isset($body['id']) ? (int) $body['id'] : 0;
        $label = sanitize_string($body['label'] ?? '', 32);
        $cap = (int) ($body['capacity'] ?? 4);
        $status = $body['status'] ?? 'available';
        if (!in_array($status, ['available', 'occupied'], true)) {
            $status = 'available';
        }
        if ($label === '' || $cap < 1) {
            json_response(['success' => false, 'error' => 'Invalid table data'], 400);
        }
        if ($id > 0) {
            TableModel::update($id, $label, $cap, $status);
            json_response(['success' => true, 'id' => $id]);
        }
        $newId = TableModel::create($label, $cap, $status);
        json_response(['success' => true, 'id' => $newId]);
    }

    public function apiDelete(): void
    {
        \App\Middleware\require_role('admin');
        require_method('POST');
        $body = input_json();
        $id = (int) ($body['id'] ?? 0);
        if ($id < 1) {
            json_response(['success' => false, 'error' => 'Invalid id'], 400);
        }
        TableModel::delete($id);
        json_response(['success' => true]);
    }

    public function apiSetStatus(): void
    {
        \App\Middleware\require_role('admin', 'staff');
        require_method('POST');
        $body = input_json();
        $id = (int) ($body['id'] ?? 0);
        $status = $body['status'] ?? '';
        if ($id < 1 || !in_array($status, ['available', 'occupied'], true)) {
            json_response(['success' => false, 'error' => 'Invalid request'], 400);
        }
        TableModel::setStatus($id, $status);
        json_response(['success' => true]);
    }
}
