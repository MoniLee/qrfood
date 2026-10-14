<?php
declare(strict_types=1);
$pageTitle = 'Menu management';
$extraScripts = ['menu_manage.js'];
require dirname(__DIR__) . '/partials/document_head.php';
require dirname(__DIR__) . '/partials/app_shell_start.php';

$itemsPayload = array_map(static function (array $m): array {
    return [
        'id' => (int) $m['id'],
        'category_id' => (int) $m['category_id'],
        'name' => $m['name'],
        'description' => (string) ($m['description'] ?? ''),
        'price' => (float) $m['price'],
        'category_name' => $m['category_name'] ?? '',
        'image_path' => $m['image_path'] ?? null,
        'is_active' => (int) ($m['is_active'] ?? 1),
        'image_url' => menu_item_image_url($m['image_path'] ?? null),
    ];
}, $items);

$catsPayload = array_map(static function (array $c): array {
    return [
        'id' => (int) $c['id'],
        'name' => $c['name'],
        'sort_order' => (int) ($c['sort_order'] ?? 0),
    ];
}, $categories);
?>
<main id="main-content">
    <header class="page-header">
        <div>
            <h1 class="page-title">Menu &amp; categories</h1>
            <p class="page-sub">Add items, upload images, organize categories</p>
        </div>
    </header>

    <div class="layout-pos" style="grid-template-columns:1fr;">
        <section class="card">
            <div class="card-body">
                <h2 class="card-title">Categories</h2>
                <form id="catForm" class="no-print" style="display:flex;flex-wrap:wrap;gap:0.5rem;align-items:flex-end;margin-bottom:1rem;">
                    <input type="hidden" id="catId" value="">
                    <div class="form-group" style="flex:1;min-width:140px;margin:0;">
                        <label class="form-label" for="catName">Name</label>
                        <input class="form-input" id="catName" maxlength="64" required>
                    </div>
                    <div class="form-group" style="width:100px;margin:0;">
                        <label class="form-label" for="catSort">Sort</label>
                        <input class="form-input" id="catSort" type="number" value="0">
                    </div>
                    <button type="submit" class="btn btn-primary">Save category</button>
                    <button type="button" class="btn btn-secondary" id="catReset">Clear</button>
                </form>
                <div class="table-wrap table-cards-sm">
                    <table class="data-table" id="catTable">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Sort</th>
                                <th class="no-print">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="catBody"></tbody>
                    </table>
                </div>
            </div>
        </section>

        <section class="card card-spaced">
            <div class="card-body">
                <h2 class="card-title">Menu items</h2>
                <p class="text-muted form-hint">Tip: search Unsplash for your dish name, copy the image link, and paste it above for a quick hero photo.</p>
                <form id="itemForm" class="no-print" enctype="multipart/form-data">
                    <input type="hidden" id="itemId" value="">
                    <div class="form-group">
                        <label class="form-label" for="itemName">Name</label>
                        <input class="form-input" id="itemName" maxlength="128" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="itemDesc">Description</label>
                        <textarea class="form-textarea" id="itemDesc" maxlength="2000"></textarea>
                    </div>
                    <div class="form-grid-2">
                        <div class="form-group mb-0">
                            <label class="form-label" for="itemPrice">Price (₹)</label>
                            <input class="form-input" id="itemPrice" type="number" step="0.01" min="0" required>
                        </div>
                        <div class="form-group mb-0">
                            <label class="form-label" for="itemCategory">Category</label>
                            <select class="form-select" id="itemCategory" required></select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="itemImageUrl">Photo link (optional)</label>
                        <input class="form-input" id="itemImageUrl" type="url" inputmode="url" placeholder="https://… (Unsplash, CDN, or your host)" autocomplete="off">
                        <p class="text-muted form-hint">Paste a direct image URL, or upload a file below. Upload wins if both are set.</p>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="itemImage">Upload image</label>
                        <input class="form-input" id="itemImage" type="file" accept="image/jpeg,image/png,image/webp,image/gif">
                        <p class="text-muted form-hint">Optional. Max 2MB. Saved under <code>assets/uploads/menu</code>.</p>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><input type="checkbox" id="itemActive" checked> Active on menu</label>
                    </div>
                    <div class="btn-group">
                        <button type="submit" class="btn btn-primary">Save item</button>
                        <button type="button" class="btn btn-secondary" id="itemReset">New item</button>
                    </div>
                </form>
                <div id="itemPreview" class="no-print item-preview-box"></div>
                <div class="table-wrap table-cards-sm table-below-form">
                    <table class="data-table" id="itemTable">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Active</th>
                                <th class="no-print">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="itemBody"></tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</main>

<script id="srms-menu-initial" type="application/json"><?= json_encode(['items' => $itemsPayload, 'categories' => $catsPayload], JSON_THROW_ON_ERROR) ?></script>
<?php require dirname(__DIR__) . '/partials/document_end.php'; ?>
