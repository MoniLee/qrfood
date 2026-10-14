<?php
declare(strict_types=1);
$pageTitle = 'New Order (POS)';
$extraScripts = ['pos.js'];
require dirname(__DIR__) . '/partials/document_head.php';
require dirname(__DIR__) . '/partials/app_shell_start.php';

$gstRate = (float) app_config('gst_default_rate', 18.0);
$menuPayload = array_map(static function (array $m): array {
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
}, $menuInitial ?? []);
?>
<main id="main-content">
    <header class="page-header">
        <div>
            <h1 class="page-title">New order</h1>
            <p class="page-sub">Dine-in or delivery — search, add to cart, place order</p>
        </div>
        <div class="btn-group no-print">
            <a class="btn btn-outline btn-sm" href="<?= e(base_url('orders')) ?>">All orders</a>
        </div>
    </header>

    <div class="layout-pos">
        <div>
            <div class="menu-toolbar">
                <input type="search" class="form-input search-input" id="menuSearch" placeholder="Search menu…" autocomplete="off" aria-label="Search menu">
                <div class="filter-chips" id="categoryChips" role="tablist" aria-label="Categories"></div>
            </div>
            <div class="loading-overlay" id="menuLoading" aria-hidden="true">
                <div class="spinner" aria-hidden="true"></div>
            </div>
            <div class="menu-grid" id="menuGrid"></div>
        </div>

        <aside class="cart-panel" aria-label="Cart">
            <h2 class="card-title mt-0 text-center" style="font-family: var(--font-display); font-size: 1.5rem; letter-spacing: 1px;">Gourmet Selection</h2>
            <div class="form-group" style="margin-bottom:0.75rem;">
                <label class="form-label" for="orderType">Order type</label>
                <select class="form-select" id="orderType" aria-label="Order type">
                    <option value="DINE_IN">Dine-in</option>
                    <option value="DELIVERY">Delivery</option>
                </select>
            </div>
            <div class="form-group" id="wrapTable">
                <label class="form-label" for="tableId">Table <span class="text-muted" style="font-size:0.8em;">(required for dine-in)</span></label>
                <select class="form-select" id="tableId" aria-label="Table">
                    <option value="0">— Select a table —</option>
                    <?php foreach ($tables as $t): ?>
                        <option value="<?= (int) $t['id'] ?>" data-status="<?= e((string) $t['status']) ?>">
                            <?= e((string) $t['label']) ?> (<?= (int) $t['capacity'] ?> seats)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group" id="wrapDelivery" hidden>
                <label class="form-label">Delivery address will be entered at checkout</label>
            </div>

            <div class="cart-lines" id="cartLines">
                <div id="cartEmpty" class="cart-empty-state">
                    <img src="<?= e(base_url('frontend/assets/empty-tray.png')) ?>" alt="Empty Tray" class="cart-empty-image">
                    <p>Your tray is empty.<br>Add some delicious items from the menu!</p>
                </div>
            </div>
            <div class="cart-summary-sticky is-hidden" id="cartSummary">
                <div class="cart-summary-inner">
                    <div class="cart-summary-row"><span>Subtotal</span><span id="sumSub">₹0.00</span></div>
                    <div class="cart-summary-row"><span>GST (<?= e((string) $gstRate) ?>%)</span><span id="sumGst">₹0.00</span></div>
                    <div class="cart-summary-row" id="rowDeliveryFee" hidden><span>Delivery fee</span><span id="sumDelivery">₹0.00</span></div>
                    <div class="cart-summary-total"><span>Total</span><span id="sumTotal">₹0.00</span></div>
                </div>
                <button type="button" class="btn btn-primary btn-block btn-lg" id="btnPlace" style="margin-top:0.75rem;">Place Order</button>
            </div>
        </aside>
    </div>
</main>

<!-- Checkout Multi-step Modal -->
<div class="modal" id="checkoutModal">
    <div class="modal-backdrop"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title" id="checkoutModalTitle">Checkout</h3>
            <button class="modal-close" id="closeCheckoutModal">&times;</button>
        </div>
        <div class="modal-body">
            <!-- Step 1: Address / Customer Details -->
            <div id="stepAddress" class="checkout-step">
                <h4 class="mb-1" id="checkoutStepTitle">Customer Details</h4>
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" class="form-input" id="checkoutName" placeholder="Enter your name">
                </div>
                <div class="form-group">
                    <label class="form-label">Phone Number</label>
                    <input type="tel" class="form-input" id="checkoutPhone" placeholder="Enter phone number">
                </div>
                <div id="wrapCheckoutAddr" class="form-group">
                    <label class="form-label">Delivery Address</label>
                    <textarea class="form-textarea" id="checkoutAddr" placeholder="Complete address..."></textarea>
                    <p class="text-muted" style="font-size:0.8rem; margin-top:0.5rem" id="detectedLocationInfo"></p>
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
                <button class="btn btn-primary btn-block mt-3" id="btnNextToConfirm">Review Order</button>
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
                    <div id="upiQrPlaceholder" style="width:180px; height:180px; background:#fff; margin:0 auto; padding:10px; border-radius:10px; border:1px solid #ddd">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=160x160&data=upi://pay?pa=velvetplate@upi" alt="QR Code" style="width:160px; height:160px">
                    </div>
                </div>

                <button class="btn btn-primary btn-block" id="btnFinalConfirm">Confirm Order</button>
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

<script id="srms-pos-initial" type="application/json"><?= json_encode(['menu' => $menuPayload, 'gstRate' => $gstRate], JSON_THROW_ON_ERROR) ?></script>
<?php require dirname(__DIR__) . '/partials/document_end.php'; ?>
