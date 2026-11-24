<?php

require dirname(__DIR__, 2) . '/src/bootstrap.php';

header('Cache-Control: no-store');

if (!Auth::hasAnyAdmin()) {
    redirect('admin/setup.php');
}
if (Auth::check()) {
    redirect('admin/index.php');
}

$error = '';
$email = '';

if (is_post()) {
    Csrf::verify();
    $email = post_string('email');
    $password = $_POST['password'] ?? '';

    if (Auth::tooManyAttempts()) {
        $error = 'Too many failed attempts. Please wait 15 minutes and try again.';
    } elseif (!is_string($password) || !Auth::attempt($email, $password)) {
        $error = 'Invalid email or password.';
    } else {
        redirect('admin/index.php');
    }
}

view('admin/auth-layout', [
    'title' => 'Admin login',
    'error' => $error,
    'email' => $email,
    'button' => 'Log in',
    'passwordAutocomplete' => 'current-password',
]);
