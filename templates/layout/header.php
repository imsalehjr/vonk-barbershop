<?php
$title = $title ?? '';
$active = $active ?? '';
$appName = Env::get('APP_NAME', 'Barbershop');
$nav = ['' => 'Home', 'services.php' => 'Services', 'barbers.php' => 'Barbers', 'contact.php' => 'Contact'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title !== '' ? "$title | $appName" : "$appName | Barbers in Eindhoven") ?></title>
    <meta name="description" content="<?= e($description ?? 'Vonk Barbershop: classic cuts, sharp fades and hot towel shaves. Book your appointment online in under a minute.') ?>">
    <meta name="theme-color" content="#141414">
    <link rel="icon" href="<?= e(asset('img/favicon.svg')) ?>" type="image/svg+xml">
    <link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
    <script src="<?= e(asset('js/app.js')) ?>" defer></script>
</head>
<body>
<a class="skip-link" href="#main">Skip to content</a>
<header class="site-header">
    <div class="container header-inner">
        <a class="logo" href="<?= e(url()) ?>" aria-label="<?= e($appName) ?> home">
            <svg class="logo-mark" viewBox="0 0 32 32" aria-hidden="true"><path d="M18 2 6 18h8l-2 12 14-18h-9l1-10z" fill="currentColor"/></svg>
            <span>VONK</span>
        </a>
        <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="site-nav">
            <span class="sr-only">Menu</span><span class="nav-toggle-bar"></span>
        </button>
        <nav id="site-nav" class="site-nav" aria-label="Main">
            <ul>
                <?php foreach ($nav as $href => $label): ?>
                    <li><a href="<?= e(url($href)) ?>"<?= $active === $href ? ' aria-current="page"' : '' ?>><?= e($label) ?></a></li>
                <?php endforeach; ?>
            </ul>
            <a class="btn btn-primary btn-sm" href="<?= e(url('book.php')) ?>">Book now</a>
        </nav>
    </div>
</header>
<main id="main">
<?php foreach (take_flashes() as $flash): ?>
    <div class="container"><div class="alert alert-<?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?></div></div>
<?php endforeach; ?>
