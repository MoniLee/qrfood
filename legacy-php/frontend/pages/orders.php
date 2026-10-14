<?php
declare(strict_types=1);
$pageTitle = 'Orders';
$extraScripts = ['orders.js'];
$role = $_SESSION['role'] ?? '';
require dirname(__DIR__) . '/partials/document_head.php';
require dirname(__DIR__) . '/partials/app_shell_start.php';
?>
<main id="main-content" data-customer="<?= $role === 'customer' ? '1' : '0' ?>">
    <header class="page-header">
        <div>
            <h1 class="page-title">Orders</h1>
            <p class="page-sub"><?= $role === 'customer' ? 'Your recent orders' : 'Track and update order status' ?></p>
        </div>
        <div class="btn-group">
            <button type="button" class="btn btn-secondary btn-sm" id="btnRefreshOrders">Refresh</button>
            <?php if ($role !== 'customer'): ?>
                <a class="btn btn-primary btn-sm" href="<?= e(base_url('pos')) ?>">New order</a>
            <?php endif; ?>
        </div>
    </header>

    <p class="text-muted mb-1" id="ordersSyncNote" aria-live="polite"></p>

    <div class="table-wrap table-cards-sm">
        <table class="data-table" id="ordersTable">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Type</th>
                    <th>Table / Customer</th>
                    <th>Status</th>
                    <th>Total</th>
                    <th>Time</th>
                    <th class="no-print">Actions</th>
                </tr>
            </thead>
            <tbody id="ordersBody">
                <?php foreach ($orders as $o): ?>
                    <tr data-order-id="<?= (int) $o['id'] ?>">
                        <td data-label="Code"><strong><?= e((string) $o['order_code']) ?></strong></td>
                        <td data-label="Type"><?= e((string) $o['type']) ?></td>
                        <td data-label="Details">
                            <?php if (($o['type'] ?? '') === 'DINE_IN'): ?>
                                <?= e((string) ($o['table_label'] ?? '—')) ?>
                            <?php else: ?>
                                <?= e((string) ($o['customer_name'] ?? '')) ?><br>
                                <span class="text-muted"><?= e((string) ($o['customer_phone'] ?? '')) ?></span>
                            <?php endif; ?>
                        </td>
                        <td data-label="Status"><span class="badge <?= e('badge-' . preg_replace('/[^a-z]/', '', (string) $o['status'])) ?>"><?= e((string) $o['status']) ?></span></td>
                        <td data-label="Total">₹<?= number_format((float) $o['total'], 2) ?></td>
                        <td data-label="Time" class="text-muted"><?= e((string) $o['created_at']) ?></td>
                        <td data-label="Actions" class="no-print">
                            <div class="btn-group">
                                <a class="btn btn-outline btn-sm" href="<?= e(base_url('invoice/' . (int) $o['id'])) ?>">Invoice</a>
                                <?php if ($role !== 'customer'): ?>
                                <select class="form-select status-select btn-sm" style="min-width:120px;padding:0.35rem;" data-order-id="<?= (int) $o['id'] ?>" aria-label="Update status for <?= e((string) $o['order_code']) ?>">
                                    <?php foreach (['pending', 'preparing', 'ready', 'completed'] as $st): ?>
                                        <option value="<?= $st ?>" <?= ($o['status'] ?? '') === $st ? 'selected' : '' ?>><?= $st === 'completed' ? (($o['type'] ?? '') === 'DELIVERY' ? 'delivered' : 'served') : $st ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>
<?php require dirname(__DIR__) . '/partials/document_end.php'; ?>
