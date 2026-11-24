<?php

require dirname(__DIR__, 2) . '/src/bootstrap.php';

header('Cache-Control: no-store');

if (Auth::hasAnyAdmin()) {
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
        $error = 'Please enter a valid email address.';
    } elseif (!is_string($password) || mb_strlen($password) < 12) {
        $error = 'Use a password of at least 12 characters.';
    } elseif ($password !== $confirm) {
        $error = 'The passwords do not match.';
    } else {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        $exists = (int) $pdo->query('SELECT COUNT(*) FROM admin_users FOR UPDATE')->fetchColumn() > 0;
        if (!$exists) {
            $pdo->prepare('INSERT INTO admin_users (email, password_hash) VALUES (?, ?)')
                ->execute([$email, password_hash($password, PASSWORD_DEFAULT)]);
        }
        $pdo->commit();
        flash('success', 'Admin account created. You can now log in.');
        redirect('admin/login.php');
    }
}

view('admin/auth-layout', [
    'title' => 'Create admin account',
    'intro' => 'No admin exists yet. Create the first account. This page locks itself once an admin exists.',
    'error' => $error,
    'email' => $email,
    'confirm' => true,
    'button' => 'Create account',
    'passwordAutocomplete' => 'new-password',
]);
