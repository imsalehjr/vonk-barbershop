<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($title) ?> | Vonk beheer</title>
    <link rel="icon" href="<?= e(asset('img/favicon.svg')) ?>" type="image/svg+xml">
    <link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
</head>
<body>
<main class="login-wrap">
    <div class="card login-card">
        <span class="logo">
            <svg class="logo-mark" viewBox="0 0 32 32" aria-hidden="true"><path d="M18 2 6 18h8l-2 12 14-18h-9l1-10z" fill="currentColor"/></svg>
            <span>VONK</span>
        </span>
        <h1 class="h2"><?= e($title) ?></h1>
        <?php if (!empty($intro)): ?><p class="muted"><?= e($intro) ?></p><?php endif; ?>
        <?php if (!empty($demoLogin)): ?>
            <div class="alert alert-info" role="note">
                <strong>Demo-inlog</strong><br>
                E-mailadres: <code><?= e($demoLogin['email']) ?></code><br>
                Wachtwoord: <code><?= e($demoLogin['password']) ?></code>
            </div>
        <?php endif; ?>
        <?php if (!empty($error)): ?><div class="alert alert-error" role="alert"><?= e($error) ?></div><?php endif; ?>
        <form method="post" novalidate>
            <?= Csrf::field() ?>
            <div class="field">
                <label for="email">E-mailadres</label>
                <input type="email" id="email" name="email" required autocomplete="username" value="<?= e($email ?? '') ?>">
            </div>
            <div class="field">
                <label for="password">Wachtwoord</label>
                <input type="password" id="password" name="password" required autocomplete="<?= e($passwordAutocomplete) ?>">
            </div>
            <?php if (!empty($confirm)): ?>
                <div class="field">
                    <label for="password_confirm">Herhaal wachtwoord</label>
                    <input type="password" id="password_confirm" name="password_confirm" required autocomplete="new-password">
                </div>
            <?php endif; ?>
            <button class="btn btn-primary btn-block" type="submit"><?= e($button) ?></button>
        </form>
    </div>
</main>
</body>
</html>
