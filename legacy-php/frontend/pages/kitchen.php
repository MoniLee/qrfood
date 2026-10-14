<?php
declare(strict_types=1);
$pageTitle = 'Kitchen';
$extraScripts = ['kitchen.js'];
require dirname(__DIR__) . '/partials/document_head.php';
require dirname(__DIR__) . '/partials/app_shell_start.php';
?>
<main id="main-content">
    <header class="page-header">
        <div>
            <h1 class="page-title">Kitchen</h1>
            <p class="page-sub">Large controls — tap to advance order status</p>
        </div>
        <p class="text-muted mt-0" id="kitchenClock" style="align-self:flex-end;"></p>
    </header>

    <p class="text-muted mb-1" id="kitchenSync" aria-live="polite"></p>

    <div class="loading-overlay is-visible" id="kitchenLoading">
        <div class="spinner" aria-hidden="true"></div>
    </div>
    <div class="kitchen-grid" id="kitchenGrid" hidden></div>
    <p class="text-muted" id="kitchenEmpty" hidden>No active tickets. New orders appear automatically.</p>
</main>
<?php require dirname(__DIR__) . '/partials/document_end.php'; ?>
