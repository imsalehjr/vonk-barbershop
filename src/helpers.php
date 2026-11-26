<?php

declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string
{
    return rtrim(Env::get('APP_URL', ''), '/') . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    $file = BASE_PATH . '/public/assets/' . ltrim($path, '/');
    $version = is_file($file) ? (string) filemtime($file) : '1';
    return url('assets/' . ltrim($path, '/')) . '?v=' . $version;
}

function redirect(string $path): void
{
    header('Location: ' . (str_starts_with($path, 'http') ? $path : url($path)));
    exit;
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function take_flashes(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

function money(int $cents): string
{
    return '€' . number_format($cents / 100, 2, ',', '.');
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function post_string(string $key): string
{
    $value = $_POST[$key] ?? '';
    return is_string($value) ? trim($value) : '';
}

function get_string(string $key): string
{
    $value = $_GET[$key] ?? '';
    return is_string($value) ? trim($value) : '';
}

function get_int(string $key): int
{
    $value = $_GET[$key] ?? '';
    return (is_string($value) && ctype_digit($value)) ? (int) $value : 0;
}

function post_int(string $key): int
{
    $value = $_POST[$key] ?? '';
    return (is_string($value) && ctype_digit($value)) ? (int) $value : 0;
}

function format_date(string $datetime): string
{
    $d = new DateTimeImmutable($datetime);
    return weekday_name((int) $d->format('N')) . ' ' . $d->format('j') . ' ' . month_name((int) $d->format('n')) . ' ' . $d->format('Y');
}

function format_time(string $datetime): string
{
    return (new DateTimeImmutable($datetime))->format('H:i');
}

function weekday_name(int $weekday): string
{
    return ['maandag', 'dinsdag', 'woensdag', 'donderdag', 'vrijdag', 'zaterdag', 'zondag'][$weekday - 1];
}

function month_name(int $month, bool $short = false): string
{
    $names = ['januari', 'februari', 'maart', 'april', 'mei', 'juni', 'juli', 'augustus', 'september', 'oktober', 'november', 'december'];
    $name = $names[$month - 1];
    return $short ? substr($name, 0, 3) : $name;
}

function format_short_date(string $datetime): string
{
    $d = new DateTimeImmutable($datetime);
    return substr(weekday_name((int) $d->format('N')), 0, 2) . ' ' . $d->format('j') . ' ' . month_name((int) $d->format('n'), true);
}

function format_datetime(string $datetime): string
{
    $d = new DateTimeImmutable($datetime);
    return $d->format('j') . ' ' . month_name((int) $d->format('n'), true) . ' ' . $d->format('Y H:i');
}

function view(string $template, array $data = []): void
{
    extract($data, EXTR_SKIP);
    require BASE_PATH . '/templates/' . $template . '.php';
}

function json_response(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_THROW_ON_ERROR);
    exit;
}

function is_demo(): bool
{
    return Env::get('DEMO_MODE', 'false') === 'true';
}

function client_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}
