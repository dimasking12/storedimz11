<?php

/**
 * Legacy Session Helper
 *
 * Memungkinkan controller CI4 membaca $_SESSION yang di-set oleh file PHP lama
 * (login.php, logout.php, profile.php, dll). Tanpa ini, sesi tidak nyambung
 * karena CI4 default pakai handler-nya sendiri.
 */

if (! function_exists('legacy_session_start')) {
    function legacy_session_start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        // Pakai cookie name yang sama dengan PHP default supaya tukar sesi
        // dengan file legacy berfungsi (legacy pakai PHPSESSID).
        session_name('PHPSESSID');

        // Cookie params yang aman
        $secure = (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                  || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        @session_start();

        // Inisialisasi CSRF token legacy (kalau belum ada).
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }
}

if (! function_exists('legacy_user_id')) {
    function legacy_user_id(): ?int
    {
        legacy_session_start();
        return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    }
}

if (! function_exists('legacy_is_admin')) {
    function legacy_is_admin(): bool
    {
        legacy_session_start();
        return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }
}
