<?php
/**
 * Bootstrap: dimuat di awal SETIAP halaman.
 * Memuat konfigurasi, koneksi database, helper, autentikasi, lalu memulai sesi.
 */
require_once __DIR__ . '/helpers.php';

date_default_timezone_set((string) app_config('timezone'));

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/queries.php';
require_once __DIR__ . '/icons.php';
require_once __DIR__ . '/components.php';

if (session_status() === PHP_SESSION_NONE) {
    session_name('RUANGKULIAH');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => base_url() . '/',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header('Cache-Control: no-store, max-age=0'); // halaman berisi data akun: jangan di-cache
