<?php
declare(strict_types=1);
$pageTitle = 'Menu — Velvet Plate';
$extraScripts = ['public_menu.js'];
require dirname(__DIR__) . '/partials/document_head.php';
require dirname(__DIR__) . '/partials/app_shell_start.php';

use App\Models\Database;
$st = Database::pdo()->query('SELECT id, label, capacity, status FROM tables ORDER BY label ASC');
$tables = $st->fetchAll();
?>
<main id="main-content" class="menu-page-wrap">
    <header class="page-menu-hero">
        <h1 class="page-menu-title">The Velvet Plate menu</h1>
        <p class="page-menu-sub">Search, filter by course, and build your tray—dine in or take it away with the same
            attention to detail.</p>
    </header>

    <div class="menu-toolbar">
        <div class="search-input">
            <label class="sr-only" for="menuSearch">Search dishes</label>
            <input type="text" id="menuSearch" class="form-input" placeholder="Search dishes, ingredients…"
                autocomplete="off">
        </div>
        <div class="filter-chips" id="categoryChips" role="tablist" aria-label="Categories"></div>
    </div>

    <div class="layout-pos">
        <div class="menu-grid" id="menuGrid"></div>

        <aside class="cart-panel cart-panel--lux" aria-label="Your order">
            <h2 class="cart-title-lux text-center"
                style="font-family: var(--font-display); font-size: 1.6rem; letter-spacing: 1px; color: var(--accent);">
                Velvet Gourmet Tray</h2>
            <div class="form-group" style="padding: 1rem 1.25rem 0.5rem;">
                <label class="form-label" for="orderType">Order type</label>
                <select class="form-select" id="orderType">
                    <option value="DINE_IN">Dine-in</option>
                    <option value="DELIVERY">Delivery</option>
                </select>
            </div>

            <div id="cartEmpty" class="cart-empty-state">
                <img src="<?= e(base_url('frontend/assets/empty-tray.png')) ?>" alt="Empty Tray"
                    class="cart-empty-image">
                <p>Your tray is empty.<br>Add some delicious items from the menu!</p>
            </div>

            <div id="cartSummary" class="cart-summary-inner-wrap is-hidden">
                <div class="cart-lines-container">
                    <div class="cart-lines" id="cartLines"></div>
                </div>

                <div class="cart-summary-sticky">
                    <div class="cart-summary-card">
                        <div class="cart-summary-row">
                            <span>Subtotal</span><span id="sumSub">₹0.00</span>
                        </div>
                        <div class="cart-summary-row">
                            <span>GST (<span id="gstPctLabel">18</span>%)</span><span id="sumGst">₹0.00</span>
                        </div>
                        <div class="cart-summary-row" id="rowDeliveryFee" hidden>
                            <span>Delivery fee</span><span id="sumDelivery">₹0.00</span>
                        </div>
                        <div class="cart-summary-total">
                            <span>Total</span><span id="sumTotal">₹0.00</span>
                        </div>
                    </div>

                    <?php if (isset($_SESSION['user_id'])): ?>
                        <div id="wrapTable" class="table-booking-compact">
                            <label class="form-label">Select Table</label>
                            <div class="table-selector-grid">
                                <button type="button" class="table-btn table-btn--none selected" data-id="0">None</button>
                                <?php foreach ($tables as $t): ?>
                                    <button type="button"
                                        class="table-btn <?= e(($t['status'] ?? '') === 'available' ? 'table-btn--available' : 'table-btn--occupied') ?>"
                                        data-id="<?= (int) $t['id'] ?>" data-status="<?= e((string) $t['status']) ?>"
                                        <?= ($t['status'] ?? '') === 'available' ? '' : 'disabled' ?>>
                                        <?= e((string) $t['label']) ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                            <input type="hidden" id="selectedTable" value="0">
                        </div>
                        <button type="button" id="btnPlace" class="btn btn-primary btn-block btn-lg btn-place-order">Place
                            Order</button>
                    <?php else: ?>
                        <a href="<?= e(base_url('login')) ?>"
                            class="btn btn-secondary btn-block btn-lg btn-place-order">Sign in to Order</a>
                    <?php endif; ?>
                </div>
            </div>
        </aside>
    </div>
</main>
<script type="application/json" id="srms-pos-initial">
    <?= json_encode(['menu' => menu_items_with_resolved_images(\App\Models\MenuItem::allWithCategory(true)), 'gstRate' => (float) app_config('gst_default_rate', 18)], JSON_THROW_ON_ERROR) ?>
</script>

<!-- Checkout Multi-step Modal -->
<div class="modal" id="checkoutModal">
    <div class="modal-backdrop"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title" id="checkoutModalTitle">Finalize Order</h3>
            <button class="modal-close" id="closeCheckoutModal">&times;</button>
        </div>
        <div class="modal-body">
            <!-- Step 1: Address (Only for Delivery) -->
            <div id="stepAddress" class="checkout-step">
                <h4 class="mb-1">Customer Details</h4>
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" class="form-input" id="checkoutName" placeholder="Enter your name">
                </div>
                <div class="form-group">
                    <label class="form-label">Phone Number</label>
                    <input type="tel" class="form-input" id="checkoutPhone" placeholder="Enter phone number">
                </div>
                <div id="wrapCheckoutAddr">
                    <div class="form-group">
                        <label class="form-label">Delivery Address</label>
                        <textarea class="form-textarea" id="checkoutAddr" placeholder="Complete address..."></textarea>
                        <p class="text-muted" style="font-size:0.8rem; margin-top:0.5rem" id="detectedLocationInfo"></p>
                    </div>
                </div>
                <button class="btn btn-primary btn-block" id="btnNextToPayment">Continue to Payment</button>
            </div>

            <!-- Step 2: Payment -->
            <div id="stepPayment" class="checkout-step" style="display:none">
                <h4 class="mb-1">Select Payment Method</h4>
                <div class="payment-options">
                    <label class="payment-option">
                        <input type="radio" name="paymentMethod" value="COD" checked>
                        <div class="payment-option-card">
                            <span class="payment-option-icon">💵</span>
                            <span>Cash on Delivery</span>
                        </div>
                    </label>
                    <label class="payment-option">
                        <input type="radio" name="paymentMethod" value="UPI">
                        <div class="payment-option-card">
                            <span class="payment-option-icon">📱</span>
                            <span>UPI (QR Scan)</span>
                        </div>
                    </label>
                </div>
                <button class="btn btn-primary btn-block mt-3" id="btnNextToConfirm">Review Summary</button>
            </div>

            <!-- Step 3: Confirmation -->
            <div id="stepConfirm" class="checkout-step" style="display:none">
                <h4 class="mb-1">Order Summary</h4>
                <div class="confirm-details card p-3 mb-3" style="background:var(--bg-elevated)">
                    <p id="confirmSummaryName"></p>
                    <p id="confirmSummaryPhone"></p>
                    <p id="confirmSummaryAddr" class="mb-0"></p>
                </div>

                <!-- UPI QR Section -->
                <div id="upiQrSection" style="display:none; text-align:center; margin-bottom:1.5rem;">
                    <p class="text-muted mb-2">Scan QR to pay ₹<span id="upiAmount">0</span></p>
                    <div id="upiQrPlaceholder"
                        style="width:180px; height:180px; background:#fff; margin:0 auto; padding:10px; border-radius:10px; border:1px solid #ddd">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=160x160&data=upi://pay?pa=velvetplate@upi"
                            alt="QR Code" style="width:160px; height:160px">
                    </div>
                </div>

                <button class="btn btn-primary btn-block" id="btnFinalConfirm">Confirm & Place Order</button>
            </div>

            <!-- Step 4: Success -->
            <div id="stepSuccess" class="checkout-step" style="display:none; text-align:center; padding:2rem 0">
                <div style="font-size:4rem; margin-bottom:1rem">✅</div>
                <h3 class="font-display">Ordered Successfully!</h3>
                <p class="text-muted">Your delicious meal is being prepared.</p>
                <div class="card p-3 mt-3 mb-3" style="background:var(--bg-elevated)">
                    <p class="mb-1">Order ID: <strong id="successOrderId">#--</strong></p>
                    <p class="mb-0">Payment: <strong id="successPayment">--</strong></p>
                </div>
                <button class="btn btn-outline btn-block" onclick="window.location.reload()">Done</button>
            </div>
        </div>
    </div>
</div>

<?php require dirname(__DIR__) . '/partials/document_end.php'; ?>