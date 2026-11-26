<?php

require dirname(__DIR__, 2) . '/src/bootstrap.php';

header('Cache-Control: no-store');

if (is_demo() || Auth::hasAnyAdmin()) {
    redirect('admin/login.php');
}

$error = '';
$email = '';

if (is_post()) {
    Csrf::verify();
    $email = post_string('email');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['password_confirm'] ?? '';

    if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        $error = 'Vul een geldig e-mailadres in.';
    } elseif (!is_string($password) || mb_strlen($password) < 12) {
        $error = 'Gebruik een wachtwoord van minimaal 12 tekens.';
    } elseif ($password !== $confirm) {
        $error = 'De wachtwoorden zijn niet gelijk.';
    } else {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        $exists = (int) $pdo->query('SELECT COUNT(*) FROM admin_users FOR UPDATE')->fetchColumn() > 0;
        if (!$exists) {
            $pdo->prepare('INSERT INTO admin_users (email, password_hash) VALUES (?, ?)')
                ->execute([$email, password_hash($password, PASSWORD_DEFAULT)]);
        }
        $pdo->commit();
        flash('success', 'Beheerdersaccount aangemaakt. Je kunt nu inloggen.');
        redirect('admin/login.php');
    }
}

view('admin/auth-layout', [
    'title' => 'Beheerdersaccount aanmaken',
    'intro' => 'Er is nog geen beheerder. Maak het eerste account aan. Daarna is deze pagina niet meer te gebruiken.',
    'error' => $error,
    'email' => $email,
    'confirm' => true,
    'button' => 'Account aanmaken',
    'passwordAutocomplete' => 'new-password',
]);
