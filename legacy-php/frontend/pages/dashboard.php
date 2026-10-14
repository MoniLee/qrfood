<?php
declare(strict_types=1);
$pageTitle = 'Admin Dashboard';
$extraScripts = ['dashboard.js'];
require dirname(__DIR__) . '/partials/document_head.php';
require dirname(__DIR__) . '/partials/app_shell_start.php';

$dashPayload = [
    'revenueByDay' => $chartRevenue,
    'statusCounts' => $statusCounts,
    'popular' => $popular,
];
?>
<main id="main-content">
    <header class="page-header">
        <div>
            <h1 class="page-title">Dashboard</h1>
            <p class="page-sub">Live overview of operations and performance</p>
        </div>
        <button type="button" class="btn btn-secondary btn-sm" id="refreshDashboard">Refresh</button>
    </header>

    <div class="grid-stats grid-stats--auto" id="statGrid">
        <article class="stat-card">
            <p class="stat-label">Total orders</p>
            <p class="stat-value" id="statTotalOrders"><?= (int) $stats['total_orders'] ?></p>
        </article>
        <article class="stat-card">
            <p class="stat-label">Revenue (completed)</p>
            <p class="stat-value" id="statRevenue">₹<?= number_format((float) $stats['revenue'], 2) ?></p>
        </article>
        <article class="stat-card">
            <p class="stat-label">Orders today</p>
            <p class="stat-value" id="statOrdersToday"><?= (int) $stats['orders_today'] ?></p>
        </article>
        <article class="stat-card">
            <p class="stat-label">Revenue today</p>
            <p class="stat-value" id="statRevenueToday">₹<?= number_format((float) $stats['revenue_today'], 2) ?></p>
        </article>
        <article class="stat-card">
            <p class="stat-label">Tables occupied</p>
            <p class="stat-value" id="statOccupied"><?= (int) $stats['tables_occupied'] ?></p>
        </article>
        <article class="stat-card">
            <p class="stat-label">Tables available</p>
            <p class="stat-value" id="statAvailable"><?= (int) $stats['tables_available'] ?></p>
        </article>
    </div>

    <div class="layout-pos layout-pos--dashboard">
        <div class="dashboard-charts-stack">
            <?php if (count($chartRevenue) > 0 || true): ?>
            <section class="card">
                <div class="card-body">
                    <h2 class="card-title">Revenue (last 7 days)</h2>
                    <div class="chart-wrap">
                        <canvas id="revenueChart" aria-label="Revenue chart"></canvas>
                    </div>
                </div>
            </section>
            <?php endif; ?>
            <section class="card">
                <div class="card-body">
                    <h2 class="card-title">Orders by status</h2>
                    <div class="chart-wrap chart-wrap--narrow">
                        <canvas id="statusChart" aria-label="Status chart"></canvas>
                    </div>
                </div>
            </section>
        </div>
        <section class="card">
            <div class="card-body">
                <h2 class="card-title">Popular items (completed orders)</h2>
                <div id="popularList">
                    <?php if (empty($popular)): ?>
                        <p class="text-muted mt-0">No completed orders yet.</p>
                    <?php else: ?>
                        <ul class="kitchen-items">
                            <?php foreach ($popular as $p): ?>
                                <li><strong><?= e($p['name']) ?></strong> — <?= (int) $p['total_qty'] ?> sold</li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </section>
        </section>
        <section class="card" id="locationMgmtSection">
            <div class="card-body">
                <h2 class="card-title">Delivery Zones</h2>
                <div class="table-responsive">
                    <table class="table-lux">
                        <thead>
                            <tr>
                                <th>Zone Name</th>
                                <th>Pincodes</th>
                                <th>Fee</th>
                                <th>Min Order</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="zoneTableBody">
                            <tr><td colspan="5" class="text-muted">Loading zones...</td></tr>
                        </tbody>
                    </table>
                </div>
                <button type="button" class="btn btn-primary btn-sm mt-3" id="btnAddZone">Add New Delivery Zone</button>
            </div>
        </section>
    </div>
</main>

<!-- Zone Management Modal -->
<div class="modal" id="zoneModal">
    <div class="modal-backdrop"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title" id="zoneModalTitle">Add Delivery Zone</h3>
            <button class="modal-close" id="closeZoneModal">&times;</button>
        </div>
        <div class="modal-body">
            <form id="zoneForm">
                <input type="hidden" id="zoneId">
                <div class="form-group">
                    <label class="form-label" for="zoneName">Zone Name</label>
                    <input type="text" class="form-input" id="zoneName" required placeholder="e.g. Central Delhi">
                </div>
                <div class="form-group mt-3">
                    <label class="form-label" for="zonePincodes">Serviceable Pincodes (comma separated)</label>
                    <textarea class="form-textarea" id="zonePincodes" required placeholder="e.g. 110001, 110002"></textarea>
                </div>
                <div class="grid-stats grid-stats--auto mt-3">
                    <div class="form-group">
                        <label class="form-label" for="zoneFee">Delivery Fee (₹)</label>
                        <input type="number" class="form-input" id="zoneFee" step="0.01" value="0.00">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="zoneMin">Min Order (₹)</label>
                        <input type="number" class="form-input" id="zoneMin" step="0.01" value="0.00">
                    </div>
                </div>
                <div class="form-group mt-3">
                    <label class="form-label" for="zoneTime">Est. Delivery Time</label>
                    <input type="text" class="form-input" id="zoneTime" value="30-45 mins">
                </div>
                <div class="form-group mt-3">
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                        <input type="checkbox" id="zoneActive" checked> Active & Serviceable
                    </label>
                </div>
                <div class="btn-group mt-3">
                    <button type="submit" class="btn btn-primary" id="btnSaveZone">Save Zone</button>
                    <button type="button" class="btn btn-outline" id="btnCancelZone">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js" crossorigin="anonymous"></script>
<script id="srms-dashboard-json" type="application/json"><?= json_encode($dashPayload, JSON_THROW_ON_ERROR) ?></script>
<?php require dirname(__DIR__) . '/partials/document_end.php'; ?>
