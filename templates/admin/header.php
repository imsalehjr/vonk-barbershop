<?php
$active = $active ?? '';
$items = [
    'index.php' => 'Afspraken',
    'services.php' => 'Behandelingen',
    'barbers.php' => 'Barbiers',
    'hours.php' => 'Openingstijden',
];
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e(($title ?? 'Beheer') . ' | Vonk beheer') ?></title>
    <link rel="icon" href="<?= e(asset('img/favicon.svg')) ?>" type="image/svg+xml">
    <link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
    <script src="<?= e(asset('js/app.js')) ?>" defer></script>
</head>
<body class="admin-body">
<a class="skip-link" href="#main">Naar de inhoud</a>
<?php if (is_demo()): ?>
<aside class="demo-banner" aria-label="Demo-melding">
    <div class="container"><strong>Demo:</strong> Dit is het beheerpaneel van een demo. Je mag alles aanpassen; elke nacht wordt alles teruggezet.</div>
</aside>
<?php endif; ?>
<header class="admin-header">
    <div class="container header-inner">
        <a class="logo" href="<?= e(url('admin/index.php')) ?>">
            <svg class="logo-mark" viewBox="0 0 32 32" aria-hidden="true"><path d="M18 2 6 18h8l-2 12 14-18h-9l1-10z" fill="currentColor"/></svg>
            <span>BEHEER</span>
        </a>
        <nav aria-label="Beheer">
            <ul class="admin-nav">
                <?php foreach ($items as $href => $label): ?>
                    <li><a href="<?= e(url('admin/' . $href)) ?>"<?= $active === $href ? ' aria-current="page"' : '' ?>><?= e($label) ?></a></li>
                <?php endforeach; ?>
                <li><a href="<?= e(url()) ?>">Bekijk website</a></li>
                <li>
                    <form class="inline-form" method="post" action="<?= e(url('admin/logout.php')) ?>">
                        <?= Csrf::field() ?>
                        <button class="btn btn-ghost btn-sm" type="submit">Uitloggen</button>
                    </form>
                </li>
            </ul>
        </nav>
    </div>
</header>
<main id="main" class="admin-main">
    <div class="container">
        <?php foreach (take_flashes() as $flash): ?>
            <div class="alert alert-<?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?></div>
        <?php endforeach; ?>
