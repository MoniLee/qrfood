<?php
declare(strict_types=1);
$pageTitle = 'Register — ' . app_config('app_name');
$minimalLayout = true;
require dirname(__DIR__) . '/partials/document_head.php';
?>
<div class="login-page">
    <div class="login-card">
        <div class="brand">
            <span class="brand-mark"><?php $vpLogoClass = 'vp-logo-on-mark'; require dirname(__DIR__) . '/partials/vp-logo-inline.php'; ?></span>
            <span class="brand-text">Velvet Plate</span>
        </div>
        <?php if (!empty($_SESSION['flash_error'])): ?>
            <div class="alert alert-error"><?= e($_SESSION['flash_error']) ?></div>
            <?php unset($_SESSION['flash_error']); ?>
        <?php endif; ?>
        <form method="post" action="<?= e(base_url('register')) ?>" novalidate>
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <div class="form-group">
                <label class="form-label" for="full_name">Full Name</label>
                <input class="form-input" id="full_name" name="full_name" type="text" autocomplete="name" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="email">Email address</label>
                <input class="form-input" id="email" name="email" type="email" autocomplete="email" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="password">Password (minimum 6 characters)</label>
                <input class="form-input" id="password" name="password" type="password" autocomplete="new-password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Create Account</button>
        </form>
        <p class="login-footnote">
            Already a guest? <a href="<?= e(base_url('login')) ?>">Sign in</a>
        </p>
    </div>
</div>
<?php require dirname(__DIR__) . '/partials/document_end.php'; ?>
