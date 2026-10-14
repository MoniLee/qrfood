<?php
declare(strict_types=1);
if (empty($minimalLayout)): ?>
    <footer class="site-footer site-footer--luxury">
        <div class="site-footer-ornament" aria-hidden="true"></div>
        <div class="site-footer-main">
            <div class="site-footer-intro">
                <p class="site-footer-kicker">Colaba · Mumbai</p>
                <p class="site-footer-logo-type">Velvet Plate</p>
                <p class="site-footer-lede">Fine dining and delivery under one roof—ingredients sourced with care, plates
                    composed with precision, and service that remembers your name.</p>
            </div>

            <div class="site-footer-grid-lux">
                <div class="site-footer-col">
                    <h3 class="site-footer-h">Hours</h3>
                    <ul class="site-footer-list">
                        <li><span class="site-footer-list-label">Lunch</span> Tue–Sun · 12:00 – 15:30</li>
                        <li><span class="site-footer-list-label">Dinner</span> Tue–Sun · 18:30 – 23:30</li>
                        <li><span class="site-footer-list-label">Monday</span> Private dining &amp; events</li>
                    </ul>
                </div>
                <div class="site-footer-col">
                    <h3 class="site-footer-h">Reservations</h3>
                    <p class="site-footer-text"><a class="site-footer-link" href="tel:+919876543210">+91 98765 43210</a></p>
                    <p class="site-footer-text"><a class="site-footer-link"
                            href="mailto:reserve@velvetplate.com">reserve@velvetplate.com</a></p>
                    <p class="site-footer-text-muted">Concierge replies within one business day.</p>
                </div>
                <div class="site-footer-col site-footer-col--map">
                    <h3 class="site-footer-h">Visit</h3>
                    <p class="site-footer-text-muted">Heritage district, Colaba — valet on request.</p>
                    <div class="site-footer-map-frame">
                        <iframe class="site-footer-map" title="Velvet Plate on the map"
                            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2000!2d72.8258!3d18.9220!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3be7ce7e012e8b09%3A0xe10433292120e2ef!2sColaba%2C%20Mumbai%2C%20Maharashtra!5e0!3m2!1sen!2sin!4v1711234567890!5m2!1sen!2sin"
                            allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                    </div>
                    <a href="https://www.google.com/maps/search/Colaba+Mumbai" target="_blank" rel="noopener noreferrer"
                        class="btn btn-footer-directions">Open in Google Maps</a>
                </div>
                <div class="site-footer-col">
                    <h3 class="site-footer-h">Inner circle</h3>
                    <p class="site-footer-text-muted">Tasting menus, wine pairings, and members-only previews.</p>
                    <form class="footer-newsletter-lux" action="#" method="get" onsubmit="return false;">
                        <label class="sr-only" for="footer-email">Email for updates</label>
                        <input id="footer-email" class="footer-newsletter-lux-input" type="email" name="email"
                            placeholder="Your email" autocomplete="email" required>
                        <button type="submit" class="btn btn-primary btn-sm btn-no-vp-mark">Subscribe</button>
                    </form>
                    <div class="footer-social-lux" aria-label="Social">
                        <a href="#" aria-label="Facebook">f</a>
                        <a href="#" aria-label="Instagram">in</a>
                        <a href="#" aria-label="X">x</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="site-footer-bar">
            <p class="site-footer-copy">&copy; <?= date('Y') ?> Velvet Plate. All rights reserved.</p>
            <nav class="site-footer-mini-nav" aria-label="Legal">
                <a href="#">Privacy</a>
                <span class="site-footer-mini-dot" aria-hidden="true"></span>
                <a href="#">Terms</a>
                <span class="site-footer-mini-dot" aria-hidden="true"></span>
                <a href="#">Careers</a>
            </nav>
        </div>
    </footer>
    </div>
    <div id="toastHost" class="toast-host" aria-live="polite" aria-atomic="true"></div>
    <script>window.SRMS = window.SRMS || {}; window.SRMS.baseUrl = '<?= e(rtrim(base_url(''), '/')) ?>'; window.SRMS.pollMs = <?= (int) app_config('order_poll_seconds', 5) * 1000 ?>; window.SRMS.gstRate = <?= json_encode((float) app_config('gst_default_rate', 18), JSON_THROW_ON_ERROR) ?>;</script>
    <script src="<?= e(asset_url('js/srms-api.js?v=' . filemtime(__DIR__ . '/../js/srms-api.js'))) ?>"></script>
    <script src="<?= e(asset_url('js/location.js?v=' . filemtime(__DIR__ . '/../js/location.js'))) ?>"></script>
    <script src="<?= e(asset_url('js/nav.js?v=' . filemtime(__DIR__ . '/../js/nav.js'))) ?>"></script>
    <?php if (!empty($extraScripts)):
        foreach ($extraScripts as $src): ?>
            <script src="<?= e(asset_url('js/' . $src . '?v=' . filemtime(__DIR__ . '/../js/' . $src))) ?>"></script>
        <?php endforeach; endif; ?>
<?php else: ?>
    <script>window.SRMS = { baseUrl: '<?= e(rtrim(base_url(''), '/')) ?>' };</script>
<?php endif; ?>
</body>

</html>