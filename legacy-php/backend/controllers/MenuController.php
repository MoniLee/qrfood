<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\Category;
use App\Models\MenuItem;

final class MenuController
{
    public function managePage(): void
    {
        \App\Middleware\require_role('admin');
        $items = MenuItem::allWithCategory(false);
        $categories = Category::allOrdered();
        require dirname(__DIR__, 2) . '/frontend/pages/menu_manage.php';
    }

    public function apiList(): void
    {
        \App\Middleware\require_login();
        $activeOnly = ($_GET['active'] ?? '1') === '1';
        $items = menu_items_with_resolved_images(MenuItem::allWithCategory($activeOnly));
        json_response(['success' => true, 'data' => $items]);
    }

    public function apiCategories(): void
    {
        \App\Middleware\require_login();
        json_response(['success' => true, 'data' => Category::allOrdered()]);
    }

    public function apiSave(): void
    {
        \App\Middleware\require_role('admin');
        require_method('POST');
        $body = input_json();
        $id = isset($body['id']) ? (int) $body['id'] : 0;
        $name = sanitize_string($body['name'] ?? '', 128);
        $desc = sanitize_string($body['description'] ?? '', 2000);
        $price = (float) ($body['price'] ?? 0);
        $cat = (int) ($body['category_id'] ?? 0);
        $active = !empty($body['is_active']) ? 1 : 0;

        if ($name === '' || $cat < 1 || $price < 0) {
            json_response(['success' => false, 'error' => 'Invalid menu data'], 400);
        }
        if (!Category::find($cat)) {
            json_response(['success' => false, 'error' => 'Invalid category'], 400);
        }

        $imagePath = null;
        if ($id > 0) {
            $existing = MenuItem::find($id);
            $imagePath = $existing['image_path'] ?? null;
        }

        if (!empty($body['image_path'])) {
            $imagePath = sanitize_string($body['image_path'], 512);
            if ($imagePath !== '' && !preg_match('#^https?://#i', $imagePath) && str_contains($imagePath, '..')) {
                json_response(['success' => false, 'error' => 'Invalid image path'], 400);
            }
        }

        if ($id > 0) {
            MenuItem::update($id, [
                'category_id' => $cat,
                'name' => $name,
                'description' => $desc,
                'price' => $price,
                'image_path' => $imagePath,
                'is_active' => $active,
            ]);
            json_response(['success' => true, 'id' => $id]);
        }

        $newId = MenuItem::create([
            'category_id' => $cat,
            'name' => $name,
            'description' => $desc,
            'price' => $price,
            'image_path' => $imagePath,
            'is_active' => $active,
        ]);
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
        $row = MenuItem::find($id);
        if ($row && !empty($row['image_path'])) {
            $dir = rtrim((string) app_config('upload_dir'), '/\\');
            $full = $dir . DIRECTORY_SEPARATOR . basename((string) $row['image_path']);
            if (is_file($full)) {
                @unlink($full);
            }
        }
        MenuItem::delete($id);
        json_response(['success' => true]);
    }

    public function apiSaveCategory(): void
    {
        \App\Middleware\require_role('admin');
        require_method('POST');
        $body = input_json();
        $id = isset($body['id']) ? (int) $body['id'] : 0;
        $name = sanitize_string($body['name'] ?? '', 64);
        $sort = (int) ($body['sort_order'] ?? 0);
        if ($name === '') {
            json_response(['success' => false, 'error' => 'Category name required'], 400);
        }
        if ($id > 0) {
            Category::update($id, $name, $sort);
            json_response(['success' => true, 'id' => $id]);
        }
        $newId = Category::create($name, $sort);
        json_response(['success' => true, 'id' => $newId]);
    }

    public function apiDeleteCategory(): void
    {
        \App\Middleware\require_role('admin');
        require_method('POST');
        $body = input_json();
        $id = (int) ($body['id'] ?? 0);
        if ($id < 1) {
            json_response(['success' => false, 'error' => 'Invalid id'], 400);
        }
        $st = \App\Models\Database::pdo()->prepare('SELECT COUNT(*) FROM menu_items WHERE category_id = ?');
        $st->execute([$id]);
        if ((int) $st->fetchColumn() > 0) {
            json_response(['success' => false, 'error' => 'Cannot delete category with menu items'], 400);
        }
        Category::delete($id);
        json_response(['success' => true]);
    }

    public function apiUpload(): void
    {
        \App\Middleware\require_role('admin');
        require_method('POST');
        if (empty($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            json_response(['success' => false, 'error' => 'Upload failed'], 400);
        }
        $f = $_FILES['image'];
        $max = (int) app_config('max_upload_bytes', 2097152);
        if ($f['size'] > $max) {
            json_response(['success' => false, 'error' => 'File too large'], 400);
        }
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($f['tmp_name']) ?: '';
        $allowed = app_config('allowed_image_mimes', []);
        if (!in_array($mime, $allowed, true)) {
            json_response(['success' => false, 'error' => 'Invalid image type'], 400);
        }
        $ext = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            default => 'bin',
        };
        $dir = app_config('upload_dir');
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $filename = 'menu_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $dest = $dir . DIRECTORY_SEPARATOR . $filename;
        if (!move_uploaded_file($f['tmp_name'], $dest)) {
            json_response(['success' => false, 'error' => 'Could not save file'], 500);
        }
        $public = app_config('upload_public_path') . '/' . $filename;
        json_response(['success' => true, 'path' => $public]);
    }
}
