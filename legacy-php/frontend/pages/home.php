<?php
declare(strict_types=1);
$pageTitle = 'Velvet Plate — Home';
require dirname(__DIR__) . '/partials/document_head.php';
require dirname(__DIR__) . '/partials/app_shell_start.php';
?>
<main id="main-content" class="main--fullbleed">
    <section class="vp-hero" aria-labelledby="vp-hero-heading">
        <div class="vp-hero-content">
            <p class="vp-hero-eyebrow">Velvet Plate</p>
            <h1 id="vp-hero-heading" class="vp-hero-title">Where every course tells a story of craft, warmth, and
                unforgettable flavour.</h1>
            <div class="vp-hero-actions">
                <a href="<?= e(base_url('menu')) ?>" class="btn btn-primary">View menu &amp; order</a>
                <a href="#about" class="btn btn-secondary">Our philosophy</a>
            </div>
        </div>
    </section>

    <section class="vp-section vp-section--muted" id="about">
        <div class="vp-section-inner">
            <p class="vp-section-label">Velvet Plate</p>
            <h2 class="vp-section-title">A symphony of flavours</h2>
            <p class="vp-section-lead">We honour timeless recipes and modern technique—sourced with care, plated with
                precision, and served with genuine hospitality. Dine in, or enjoy the same standard at your door.</p>
        </div>
    </section>

    <!-- New Hero 1: The Ambiance -->
    <section class="vp-hero"
        style="background-image: var(--hero-overlay), url('https://images.unsplash.com/photo-1514933651103-005eec06c04b?w=1920&q=80'); min-height: 65vh;">
        <div class="vp-hero-content">
            <p class="vp-hero-eyebrow">The Ambiance</p>
            <h2 class="vp-hero-title" style="font-size: clamp(2.2rem, 6vw, 3.5rem);">Dine in undeniable elegance.</h2>
            <div class="vp-hero-actions">
                <a href="#reservations" class="btn btn-primary">Book a table</a>
            </div>
        </div>
    </section>

    <section class="vp-section">
        <div class="vp-section-inner" style="margin-bottom: 2.5rem;">
            <p class="vp-section-label">Chef’s selection</p>
            <h2 class="vp-section-title">Signature dishes</h2>
            <p class="vp-section-lead">A glimpse of the plates our guests return for—seasonal, balanced, and
                unmistakably Velvet Plate.</p>
        </div>
        <div class="vp-featured-grid">
            <?php foreach ($featured as $item): ?>
                <article class="vp-dish-card">
                    <?php $featImg = !empty($item['image_path']) ? menu_item_image_url($item['image_path']) : null; ?>
                    <div class="vp-dish-media<?= $featImg ? ' is-loading' : '' ?>">
                        <?php if ($featImg): ?>
                            <img src="<?= e($featImg) ?>" alt="<?= e($item['name']) ?>" loading="lazy" width="640" height="480"
                                 onload="this.classList.add('is-loaded'); this.parentElement.classList.remove('is-loading');"
                                 onerror="this.classList.add('is-error'); this.parentElement.classList.remove('is-loading'); this.parentElement.classList.add('is-error');">
                        <?php else: ?>
                            <div class="menu-card-placeholder">Velvet Plate</div>
                        <?php endif; ?>
                    </div>
                    <div class="vp-dish-body">
                        <h3 class="vp-dish-name"><?= e($item['name']) ?></h3>
                        <p class="vp-dish-cat"><?= e($item['category_name'] ?? '') ?></p>
                        <p class="vp-dish-price">₹<?= number_format((float) $item['price'], 2) ?></p>
                        <a href="<?= e(base_url('menu')) ?>" class="btn btn-outline btn-block">Add on menu</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- New Hero 2: Private Events -->
    <section class="vp-hero"
        style="background-image: var(--hero-overlay), url('<?= e(asset_url('assets/luxury-private-dining.png')) ?>'); min-height: 65vh;">
        <div class="vp-hero-content">
            <p class="vp-hero-eyebrow">Private Gatherings</p>
            <h2 class="vp-hero-title" style="font-size: clamp(2.2rem, 6vw, 3.5rem);">Curated moments for your special
                occasions.</h2>
            <div class="vp-hero-actions">
                <button type="button" class="btn btn-primary"
                    onclick="alert('Private event routing initiated.');">Enquire now</button>
            </div>
        </div>
    </section>
</main>
<?php require dirname(__DIR__) . '/partials/document_end.php'; ?>