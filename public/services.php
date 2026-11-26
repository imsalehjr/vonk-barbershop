<?php

require dirname(__DIR__) . '/src/bootstrap.php';

$services = Database::pdo()->query('SELECT * FROM services WHERE is_active = 1 ORDER BY sort_order, name')->fetchAll();

view('layout/header', ['title' => 'Behandelingen & prijzen', 'active' => 'services.php']);
?>
<section class="page-head">
    <div class="container">
        <h1>Behandelingen &amp; prijzen</h1>
        <p class="lead">Duidelijke prijzen, geen verrassingen. Je betaalt na je afspraak in de zaak.</p>
    </div>
</section>
<section class="section">
    <div class="container narrow">
        <ul class="price-list">
            <?php foreach ($services as $service): ?>
                <li class="price-item">
                    <div>
                        <h2><?= e($service['name']) ?></h2>
                        <p class="muted"><?= e($service['description']) ?> &middot; <?= (int) $service['duration_min'] ?> min</p>
                    </div>
                    <div class="price-side">
                        <strong><?= e(money((int) $service['price_cents'])) ?></strong>
                        <a class="btn btn-outline btn-sm" href="<?= e(url('book.php?service=' . (int) $service['id'])) ?>">Boek</a>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
<?php view('layout/footer'); ?>
