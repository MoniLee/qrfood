<?php
declare(strict_types=1);
$cls = $vpLogoClass ?? 'vp-logo-svg';
?>
<span class="<?= e($cls) ?>" aria-hidden="true" style="display:inline-flex; align-items:center; justify-content:center; flex-shrink:0;">
    <img src="<?= e(asset_url('assets/favicon.png')) ?>" alt="Velvet Plate logo" style="width: 42px; height: 42px; object-fit: cover; border-radius: var(--radius-sm, 6px); box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);">
</span>
