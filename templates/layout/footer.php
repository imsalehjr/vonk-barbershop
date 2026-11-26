<?php
$hours = Database::pdo()->query('SELECT * FROM opening_hours ORDER BY weekday')->fetchAll();
?>
</main>
<footer class="site-footer">
    <div class="container footer-grid">
        <div>
            <a class="logo" href="<?= e(url()) ?>">
                <svg class="logo-mark" viewBox="0 0 32 32" aria-hidden="true"><path d="M18 2 6 18h8l-2 12 14-18h-9l1-10z" fill="currentColor"/></svg>
                <span>VONK</span>
            </a>
            <p class="muted">Strakke coupes, eerlijke prijzen, goed gezelschap. Sinds 2014.</p>
        </div>
        <div>
            <h2 class="footer-title">Kom langs</h2>
            <address>Voorbeeldstraat 12<br>5611 AB Eindhoven<br><a href="tel:+31401234567">040 123 45 67</a><br><a href="mailto:hallo@vonk-barbershop.test">hallo@vonk-barbershop.test</a></address>
        </div>
        <div>
            <h2 class="footer-title">Openingstijden</h2>
            <?php view('partials/hours', ['hours' => $hours]); ?>
        </div>
    </div>
    <div class="container footer-bottom">
        <p>&copy; <?= date('Y') ?> Vonk Barbershop. Demoproject.</p>
        <p><?php if (is_demo()): ?><a href="<?= e(url('admin/login.php')) ?>">Beheerpaneel (demo)</a> &middot; <?php endif; ?><a href="<?= e(url('privacy.php')) ?>">Privacy</a></p>
    </div>
</footer>
</body>
</html>
