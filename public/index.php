<?php

require dirname(__DIR__) . '/src/bootstrap.php';

$pdo = Database::pdo();
$services = $pdo->query('SELECT * FROM services WHERE is_active = 1 ORDER BY sort_order, name LIMIT 4')->fetchAll();
$barbers = $pdo->query('SELECT * FROM barbers WHERE is_active = 1 ORDER BY sort_order, name')->fetchAll();
$hours = $pdo->query('SELECT * FROM opening_hours ORDER BY weekday')->fetchAll();

view('layout/header', ['active' => '']);
?>
<section class="hero">
    <div class="container hero-inner">
        <p class="eyebrow">Barbershop &middot; Eindhoven</p>
        <h1>Zie er strak uit.<br><span class="accent">Voel je nog strakker.</span></h1>
        <p class="lead">Klassieke knipbeurten, skin fades en scheren met hete handdoek, door barbiers die de tijd voor je nemen. Binnen een minuut online geboekt.</p>
        <div class="hero-actions">
            <a class="btn btn-primary" href="<?= e(url('book.php')) ?>">Maak een afspraak</a>
            <a class="btn btn-ghost" href="<?= e(url('services.php')) ?>">Bekijk prijzen</a>
        </div>
        <ul class="hero-facts">
            <li><strong>3</strong><span>ervaren barbiers</span></li>
            <li><strong>&lt;1 min</strong><span>online boeken</span></li>
            <li><strong>21:00</strong><span>koopavond donderdag</span></li>
        </ul>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head">
            <h2>Populaire behandelingen</h2>
            <a class="link-arrow" href="<?= e(url('services.php')) ?>">Alle behandelingen</a>
        </div>
        <div class="card-grid">
            <?php foreach ($services as $service): ?>
                <article class="card service-card">
                    <h3><?= e($service['name']) ?></h3>
                    <p class="muted"><?= e($service['description']) ?></p>
                    <div class="service-meta">
                        <span><?= (int) $service['duration_min'] ?> min</span>
                        <strong><?= e(money((int) $service['price_cents'])) ?></strong>
                    </div>
                    <a class="btn btn-outline btn-sm" href="<?= e(url('book.php?service=' . (int) $service['id'])) ?>">Boek deze</a>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section section-alt">
    <div class="container">
        <div class="section-head">
            <h2>Maak kennis met de barbiers</h2>
            <a class="link-arrow" href="<?= e(url('barbers.php')) ?>">Het team</a>
        </div>
        <div class="card-grid">
            <?php foreach ($barbers as $barber): ?>
                <article class="card barber-card">
                    <div class="avatar" aria-hidden="true"><?= e(mb_substr($barber['name'], 0, 1)) ?></div>
                    <h3><?= e($barber['name']) ?></h3>
                    <p class="muted"><?= e($barber['bio']) ?></p>
                    <a class="link-arrow" href="<?= e(url('book.php?barber=' . (int) $barber['id'])) ?>">Boek bij <?= e($barber['name']) ?></a>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section">
    <div class="container split">
        <div>
            <h2>Binnenlopen, zitten, ontspannen</h2>
            <p class="muted">Bij elke afspraak hoort een kort adviesgesprek, een hete handdoek en een afwerking met goede producten. Geen haast en geen verkooppraatjes. Gewoon een goede coupe en een fijn gesprek.</p>
            <a class="btn btn-primary" href="<?= e(url('book.php')) ?>">Reserveer je stoel</a>
        </div>
        <div class="card">
            <h3>Openingstijden</h3>
            <?php view('partials/hours', ['hours' => $hours]); ?>
        </div>
    </div>
</section>
<?php view('layout/footer'); ?>
