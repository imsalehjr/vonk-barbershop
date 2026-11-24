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
        <h1>Look sharp.<br><span class="accent">Feel sharper.</span></h1>
        <p class="lead">Classic cuts, skin fades and hot towel shaves by barbers who take their time. Book online in under a minute.</p>
        <div class="hero-actions">
            <a class="btn btn-primary" href="<?= e(url('book.php')) ?>">Book an appointment</a>
            <a class="btn btn-ghost" href="<?= e(url('services.php')) ?>">View prices</a>
        </div>
        <ul class="hero-facts">
            <li><strong>3</strong><span>skilled barbers</span></li>
            <li><strong>&lt;1 min</strong><span>to book online</span></li>
            <li><strong>21:00</strong><span>late night Thursday</span></li>
        </ul>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head">
            <h2>Popular services</h2>
            <a class="link-arrow" href="<?= e(url('services.php')) ?>">All services</a>
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
                    <a class="btn btn-outline btn-sm" href="<?= e(url('book.php?service=' . (int) $service['id'])) ?>">Book this</a>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section section-alt">
    <div class="container">
        <div class="section-head">
            <h2>Meet the barbers</h2>
            <a class="link-arrow" href="<?= e(url('barbers.php')) ?>">The team</a>
        </div>
        <div class="card-grid">
            <?php foreach ($barbers as $barber): ?>
                <article class="card barber-card">
                    <div class="avatar" aria-hidden="true"><?= e(mb_substr($barber['name'], 0, 1)) ?></div>
                    <h3><?= e($barber['name']) ?></h3>
                    <p class="muted"><?= e($barber['bio']) ?></p>
                    <a class="link-arrow" href="<?= e(url('book.php?barber=' . (int) $barber['id'])) ?>">Book with <?= e($barber['name']) ?></a>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section">
    <div class="container split">
        <div>
            <h2>Walk in, sit down, relax</h2>
            <p class="muted">Every appointment includes a consult, a hot towel and a finish with quality products. No rush, no upselling. Just a great cut and a good conversation.</p>
            <a class="btn btn-primary" href="<?= e(url('book.php')) ?>">Book your chair</a>
        </div>
        <div class="card">
            <h3>Opening hours</h3>
            <?php view('partials/hours', ['hours' => $hours]); ?>
        </div>
    </div>
</section>
<?php view('layout/footer'); ?>
