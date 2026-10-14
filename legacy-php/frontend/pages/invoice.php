<?php
declare(strict_types=1);
$pageTitle = 'Invoice ' . (string) ($order['order_code'] ?? '');
require dirname(__DIR__) . '/partials/document_head.php';
require dirname(__DIR__) . '/partials/app_shell_start.php';

$doneLabel = order_completed_label($order);
?>
<main id="main-content" class="invoice-print">
    <header class="page-header">
        <div>
            <h1 class="page-title">Invoice</h1>
            <p class="page-sub"><?= e((string) ($order['order_code'] ?? '')) ?> · <?= e((string) ($order['type'] ?? '')) ?></p>
        </div>
        <div class="btn-group no-print">
            <button type="button" class="btn btn-primary btn-sm" onclick="window.print()">Print</button>
            <a class="btn btn-outline btn-sm" href="<?= e(base_url('orders')) ?>">Back to orders</a>
        </div>
    </header>

    <section class="card" style="max-width:720px;margin:0 auto;">
        <div class="card-body">
            <div style="display:flex;flex-wrap:wrap;justify-content:space-between;gap:1rem;margin-bottom:1.25rem;">
                <div>
                    <p class="mt-0" style="font-weight:700;margin:0 0 0.25rem;"><?= e(app_config('app_name')) ?></p>
                    <p class="text-muted mt-0" style="font-size:0.9rem;">GST-inclusive bill</p>
                </div>
                <div style="text-align:right;">
                    <p class="mt-0"><strong>Date:</strong> <?= e((string) $order['created_at']) ?></p>
                    <p class="mt-0"><strong>Status:</strong> <?= e((string) $order['status']) ?><?= ($order['status'] ?? '') === 'completed' ? ' (' . e($doneLabel) . ')' : '' ?></p>
                </div>
            </div>

            <?php if (($order['type'] ?? '') === 'DINE_IN'): ?>
                <p><strong>Table:</strong> <?= e((string) ($order['table_label'] ?? '—')) ?></p>
            <?php else: ?>
                <p><strong>Customer:</strong> <?= e((string) ($order['customer_name'] ?? '')) ?></p>
                <p><strong>Phone:</strong> <?= e((string) ($order['customer_phone'] ?? '')) ?></p>
                <p><strong>Address:</strong><br><?= nl2br(e((string) ($order['delivery_address'] ?? ''))) ?></p>
            <?php endif; ?>

            <div class="table-wrap" style="margin-top:1rem;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Qty</th>
                            <th>Unit</th>
                            <th>Line</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $li): ?>
                            <tr>
                                <td><?= e((string) ($li['item_name'] ?? '')) ?></td>
                                <td><?= (int) $li['quantity'] ?></td>
                                <td>₹<?= number_format((float) $li['unit_price'], 2) ?></td>
                                <td>₹<?= number_format((float) $li['line_total'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div style="margin-top:1rem;max-width:280px;margin-left:auto;font-size:0.95rem;">
                <div class="cart-summary-row"><span>Subtotal</span><span>₹<?= number_format((float) $order['subtotal'], 2) ?></span></div>
                <div class="cart-summary-row">
                    <span>GST (<?= e((string) $order['gst_rate']) ?>%)</span>
                    <span>₹<?= number_format((float) $order['gst_amount'], 2) ?></span>
                </div>
                <div class="cart-summary-row cart-summary-total" style="margin-top:0.5rem;padding-top:0.5rem;border-top:2px solid var(--border);">
                    <span>Grand total</span>
                    <span>₹<?= number_format((float) $order['total'], 2) ?></span>
                </div>
            </div>
        </div>
    </section>
</main>
<?php require dirname(__DIR__) . '/partials/document_end.php'; ?>
