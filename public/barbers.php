<?php

require dirname(__DIR__) . '/src/bootstrap.php';

$pdo = Database::pdo();
$barbers = $pdo->query('SELECT * FROM barbers WHERE is_active = 1 ORDER BY sort_order, name')->fetchAll();
$skills = [];
foreach ($pdo->query('SELECT bs.barber_id, s.name FROM barber_services bs JOIN services s ON s.id = bs.service_id WHERE s.is_active = 1 ORDER BY s.sort_order') as $row) {
    $skills[(int) $row['barber_id']][] = $row['name'];
}

view('layout/header', ['title' => 'Onze barbiers', 'active' => 'barbers.php']);
?>
<section class="page-head">
    <div class="container">
        <h1>Het team</h1>
        <p class="lead">Drie barbiers, één standaard: je gaat beter de deur uit dan je binnenkwam.</p>
    </div>
</section>
<section class="section">
    <div class="container card-grid">
        <?php foreach ($barbers as $barber): ?>
            <article class="card barber-card">
                <div class="avatar avatar-lg" aria-hidden="true"><?= e(mb_substr($barber['name'], 0, 1)) ?></div>
                <h2><?= e($barber['name']) ?></h2>
                <p class="muted"><?= e($barber['bio']) ?></p>
                <ul class="tags">
                    <?php foreach ($skills[(int) $barber['id']] ?? [] as $skill): ?>
                        <li><?= e($skill) ?></li>
                    <?php endforeach; ?>
                </ul>
                <a class="btn btn-outline btn-sm" href="<?= e(url('book.php?barber=' . (int) $barber['id'])) ?>">Boek bij <?= e($barber['name']) ?></a>
            </article>
        <?php endforeach; ?>
    </div>
</section>
<?php view('layout/footer'); ?>
