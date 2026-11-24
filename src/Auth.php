<?php

declare(strict_types=1);

final class Auth
{
    private const MAX_ATTEMPTS = 5;
    private const WINDOW_MINUTES = 15;
    private const IDLE_TIMEOUT = 3600;
    private const DUMMY_HASH = '$2y$10$.634ZgqNC9Dn0scj.nTL8.5IsWHSfbVe1i8ScDvrelCiJDYea1NU.';

    public static function attempt(string $email, string $password): bool
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT id, password_hash FROM admin_users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        $passwordOk = password_verify($password, $user['password_hash'] ?? self::DUMMY_HASH);
        $valid = $user !== false && $passwordOk;

        if (!$valid) {
            $pdo->prepare('INSERT INTO login_attempts (ip, email) VALUES (?, ?)')->execute([client_ip(), $email]);
            return false;
        }

        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            $pdo->prepare('UPDATE admin_users SET password_hash = ? WHERE id = ?')
                ->execute([password_hash($password, PASSWORD_DEFAULT), $user['id']]);
        }

        $pdo->prepare('DELETE FROM login_attempts WHERE ip = ?')->execute([client_ip()]);
        $pdo->prepare('UPDATE admin_users SET last_login_at = NOW() WHERE id = ?')->execute([$user['id']]);

        session_regenerate_id(true);
        $_SESSION['admin_id'] = (int) $user['id'];
        $_SESSION['admin_last_seen'] = time();
        return true;
    }

    public static function tooManyAttempts(): bool
    {
        $stmt = Database::pdo()->prepare(
            'SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND attempted_at > NOW() - INTERVAL ' . self::WINDOW_MINUTES . ' MINUTE'
        );
        $stmt->execute([client_ip()]);
        return (int) $stmt->fetchColumn() >= self::MAX_ATTEMPTS;
    }

    public static function check(): bool
    {
        if (empty($_SESSION['admin_id'])) {
            return false;
        }
        if (time() - ($_SESSION['admin_last_seen'] ?? 0) > self::IDLE_TIMEOUT) {
            self::logout();
            return false;
        }
        $_SESSION['admin_last_seen'] = time();
        return true;
    }

    public static function require(): void
    {
        if (!self::check()) {
            redirect('admin/login.php');
        }
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    public static function hasAnyAdmin(): bool
    {
        return (int) Database::pdo()->query('SELECT COUNT(*) FROM admin_users')->fetchColumn() > 0;
    }
}
