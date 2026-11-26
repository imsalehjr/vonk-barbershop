<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/src/bootstrap.php';

if (!is_demo()) {
    fwrite(STDERR, "DEMO_MODE is not true. Refusing to reset.\n");
    exit(1);
}

$pdo = Database::pdo();

foreach (['schema.sql', 'seed.sql', 'demo.sql'] as $file) {
    $sql = (string) file_get_contents(BASE_PATH . '/database/' . $file);
    $sql = preg_replace('/^\s*(CREATE DATABASE|USE)\b.*$/mi', '', $sql);
    $pdo->exec($sql);
}

$email = (string) Env::get('DEMO_ADMIN_EMAIL', '');
$password = (string) Env::get('DEMO_ADMIN_PASSWORD', '');
if ($email !== '' && $password !== '') {
    $pdo->prepare('INSERT INTO admin_users (email, password_hash) VALUES (?, ?)')
        ->execute([$email, password_hash($password, PASSWORD_DEFAULT)]);
}

foreach (glob(BASE_PATH . '/storage/mail/*.eml') ?: [] as $mail) {
    unlink($mail);
}

echo 'Demo reset at ' . date('Y-m-d H:i:s') . "\n";
