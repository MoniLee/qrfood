<?php
declare(strict_types=1);
$role = $_SESSION['role'] ?? 'guest';
$current = nav_segment();
?>
<header class="site-header">
    <div class="header-inner">
        <a href="<?= e(base_url('')) ?>" class="brand">
            <?php $vpLogoClass = 'vp-logo-on-mark';
            require __DIR__ . '/vp-logo-inline.php'; ?>
            <span class="brand-text">Velvet Plate</span>
        </a>

        <div class="location-selector" id="locationSelector">
            <svg class="location-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="2.5">
                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                <circle cx="12" cy="10" r="3"></circle>
            </svg>
            <span class="location-text" id="currentLocationDisplay">Select Location</span>
            <svg class="location-chevron" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="3">
                <polyline points="6 9 12 15 18 9"></polyline>
            </svg>
        </div>
        <button type="button" class="nav-toggle" id="navToggle" aria-expanded="false" aria-controls="mainNav"
            aria-label="Open menu">
            <span class="nav-toggle-bar"></span>
            <span class="nav-toggle-bar"></span>
            <span class="nav-toggle-bar"></span>
        </button>
        <nav class="main-nav" id="mainNav">
            <?php if ($role === 'guest'): ?>
                <a class="nav-link <?= $current === '' ? 'is-active' : '' ?>" href="<?= e(base_url('')) ?>">Home</a>
                <a class="nav-link <?= $current === 'menu' ? 'is-active' : '' ?>" href="<?= e(base_url('menu')) ?>">Menu</a>
            <?php elseif ($role === 'customer'): ?>
                <a class="nav-link <?= $current === '' ? 'is-active' : '' ?>" href="<?= e(base_url('')) ?>">Home</a>
                <a class="nav-link <?= $current === 'menu' ? 'is-active' : '' ?>" href="<?= e(base_url('menu')) ?>">Menu</a>
                <a class="nav-link <?= $current === 'orders' ? 'is-active' : '' ?>" href="<?= e(base_url('orders')) ?>">My
                    orders</a>
            <?php elseif ($role === 'admin'): ?>
                <a class="nav-link <?= $current === 'dashboard' ? 'is-active' : '' ?>"
                    href="<?= e(base_url('dashboard')) ?>">Dashboard</a>
                <a class="nav-link <?= $current === 'pos' ? 'is-active' : '' ?>" href="<?= e(base_url('pos')) ?>">POS</a>
                <a class="nav-link <?= $current === 'kitchen' ? 'is-active' : '' ?>"
                    href="<?= e(base_url('kitchen')) ?>">Kitchen</a>
                <a class="nav-link <?= $current === 'orders' ? 'is-active' : '' ?>"
                    href="<?= e(base_url('orders')) ?>">Orders</a>
                <a class="nav-link <?= $current === 'manage-menu' ? 'is-active' : '' ?>"
                    href="<?= e(base_url('manage-menu')) ?>">Menu admin</a>
                <a class="nav-link <?= $current === 'tables' ? 'is-active' : '' ?>"
                    href="<?= e(base_url('tables')) ?>">Tables</a>
            <?php elseif ($role === 'staff'): ?>
                <a class="nav-link <?= $current === 'pos' ? 'is-active' : '' ?>" href="<?= e(base_url('pos')) ?>">POS</a>
                <a class="nav-link <?= $current === 'orders' ? 'is-active' : '' ?>"
                    href="<?= e(base_url('orders')) ?>">Orders</a>
                <a class="nav-link <?= $current === 'tables' ? 'is-active' : '' ?>"
                    href="<?= e(base_url('tables')) ?>">Tables</a>
            <?php elseif ($role === 'kitchen'): ?>
                <a class="nav-link <?= $current === 'kitchen' ? 'is-active' : '' ?>"
                    href="<?= e(base_url('kitchen')) ?>">Kitchen</a>
            <?php endif; ?>
            <div class="nav-actions">
                <button type="button" class="btn btn-ghost btn-icon" id="themeToggle" aria-label="Toggle theme"
                    style="margin-right:0.5rem; padding: 0.5rem; border-radius: 50%;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        id="themeIcon">
                        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                    </svg>
                </button>
                <?php if ($role !== 'guest'): ?>
                    <div class="nav-user"
                        style="display: flex; align-items: center; gap: 0.5rem; text-align: left; margin-right: 0.5rem;">
                        <div
                            style="width: 32px; height: 32px; flex-shrink: 0; border: 2px solid #c9a962; border-radius: 50%; overflow: hidden; background: #faf8f4; color: #c9a962; display: flex; align-items: center; justify-content: center; font-weight: bold; text-transform: uppercase;">
                            <?= e(substr($_SESSION['email'] ?? 'U', 0, 1)) ?>
                        </div>
                        <div style="display: flex; flex-direction: column; line-height: 1.2;">
                            <span
                                style="font-weight: 600; font-size: 0.9rem;"><?= e($role === 'admin' ? 'Admin' : ($_SESSION['full_name'] === 'System Administrator' ? 'Admin' : ($_SESSION['full_name'] ?? 'User'))) ?></span>
                            <span
                                style="font-size: 0.75rem; color: var(--text-muted);"><?= e($_SESSION['email'] ?? '') ?></span>
                        </div>
                    </div>
                    <a class="btn btn-outline btn-sm" href="<?= e(base_url('logout')) ?>">Log out</a>
                <?php else: ?>
                    <a class="btn btn-outline btn-sm" href="<?= e(base_url('login')) ?>">Sign in</a>
                    <a class="btn btn-primary btn-sm nav-link-register" href="<?= e(base_url('register')) ?>">Register</a>
                <?php endif; ?>
            </div>
        </nav>
    </div>
</header>

<!-- Location Modal -->
<div class="modal" id="locationModal">
    <div class="modal-backdrop"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Select Location</h3>
            <button class="modal-close" id="closeLocationModal">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
        <div class="modal-body">
            <div class="location-search-wrapper">
                <svg class="location-search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <input type="text" class="location-search-input" id="locationSearchBar"
                    placeholder="Search for area, street, or pincode...">
                <div id="locationSearchSuggestions" class="search-suggestions"></div>
            </div>

            <button class="detect-location-btn" id="detectLocationBtn">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 22s-8-4.5-8-11.8A8 8 0 0 1 12 2a8 8 0 0 1 8 8.2c0 7.3-8 11.8-8 11.8z"></path>
                    <circle cx="12" cy="10" r="3"></circle>
                </svg>
                Detect My Location
            </button>

            <h4 class="saved-addresses-title">Saved Addresses</h4>
            <div id="savedAddressList" class="saved-address-list"></div>
        </div>
    </div>
</div>
