<?php
declare(strict_types=1);

return [
    'app_name' => 'Velvet Plate',
    'base_url' => '', // set in bootstrap from SERVER if empty, e.g. /restaurant-system
    'session_name' => 'SRMS_SESSION',
    'gst_default_rate' => 18.0,
    'upload_dir' => __DIR__ . '/../../frontend/assets/uploads/menu',
    'upload_public_path' => 'assets/uploads/menu',
    'allowed_image_mimes' => ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
    'max_upload_bytes' => 2 * 1024 * 1024,
    'order_poll_seconds' => 5,
    'order_code_prefix' => 'ORD',
];
