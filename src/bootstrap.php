<?php

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/src/Env.php';
require BASE_PATH . '/src/helpers.php';
require BASE_PATH . '/src/Database.php';
require BASE_PATH . '/src/Csrf.php';
require BASE_PATH . '/src/Validator.php';
require BASE_PATH . '/src/Schedule.php';
require BASE_PATH . '/src/Availability.php';
require BASE_PATH . '/src/BookingService.php';
require BASE_PATH . '/src/Mailer.php';
require BASE_PATH . '/src/Auth.php';

require BASE_PATH . '/lib/PHPMailer/Exception.php';
require BASE_PATH . '/lib/PHPMailer/PHPMailer.php';
require BASE_PATH . '/lib/PHPMailer/SMTP.php';

Env::load(BASE_PATH . '/.env');

date_default_timezone_set('Europe/Amsterdam');

$isLocal = Env::get('APP_ENV', 'production') === 'local';
ini_set('display_errors', $isLocal ? '1' : '0');
error_reporting(E_ALL);

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

session_name('vonk_session');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
