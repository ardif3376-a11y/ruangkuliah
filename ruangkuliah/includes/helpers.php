<?php
/**
 * Fungsi bantu umum: konfigurasi, escape HTML, URL, flash message,
 * CSRF, dan format tanggal/jam berbahasa Indonesia.
 */

const HARI_KULIAH = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];

/* ---------------------------------------------------------------- Config */

function app_config(?string $key = null)
{
    static $cfg = null;
    if ($cfg === null) {
        $cfg = require __DIR__ . '/../config/app.php';
    }
    return $key === null ? $cfg : ($cfg[$key] ?? null);
}

/* ---------------------------------------------------------------- Output */

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/* ------------------------------------------------------------------- URL */

/** Awalan URL aplikasi, contoh "/ruangkuliah" (tanpa slash di akhir). */
function base_url(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }

    $manual = app_config('base_url');
    if ($manual !== null) {
        return $base = rtrim('/' . trim((string) $manual, '/'), '/');
    }

    $doc  = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;
    $root = realpath(__DIR__ . '/..');
    if ($doc && $root) {
        $doc  = rtrim(str_replace('\\', '/', $doc), '/');
        $root = rtrim(str_replace('\\', '/', $root), '/');
        if (stripos($root, $doc) === 0) {
            return $base = rtrim(substr($root, strlen($doc)), '/');
        }
    }

    // Cadangan: turunkan dari SCRIPT_NAME
    $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    $dir = preg_replace('#/(admin|actions)$#', '', $dir);
    return $base = $dir;
}

function url(string $path = ''): string
{
    return base_url() . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/'));
}

/** URL aset + penanda versi agar browser memuat ulang saat file berubah. */
function asset_v(string $path): string
{
    $file = __DIR__ . '/../assets/' . ltrim($path, '/');
    return asset($path) . (is_file($file) ? '?v=' . filemtime($file) : '');
}

function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

/** Tujuan kembali yang diizinkan setelah aksi form (mencegah open redirect). */
function safe_return(?string $key, string $default = 'jadwal.php'): string
{
    $allowed = [
        'jadwal'    => 'jadwal.php',
        'matkul'    => 'matkul.php',
        'dashboard' => 'dashboard.php',
    ];
    return $allowed[$key ?? ''] ?? $default;
}

/* -------------------------------------------------------- Input / filter */

/** Rapikan teks: trim + satukan spasi berlebih. */
function clean_text($value): string
{
    $value = trim((string) $value);
    return trim(preg_replace('/\s+/u', ' ', $value) ?? '');
}

function post(string $key, string $default = ''): string
{
    return isset($_POST[$key]) && is_string($_POST[$key]) ? $_POST[$key] : $default;
}

function query(string $key, string $default = ''): string
{
    return isset($_GET[$key]) && is_string($_GET[$key]) ? $_GET[$key] : $default;
}

/* ----------------------------------------------------------------- Flash */

function flash(string $type, string $message): void
{
    $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
}

function flash_pull(): array
{
    $items = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $items;
}

/** Simpan input form sementara agar tidak hilang saat validasi gagal. */
function old_set(array $data): void
{
    $_SESSION['_old'] = $data;
}

function old(string $key, string $default = ''): string
{
    static $loaded = null;
    if ($loaded === null) {
        $loaded = $_SESSION['_old'] ?? [];
        unset($_SESSION['_old']);
    }
    return isset($loaded[$key]) ? (string) $loaded[$key] : $default;
}

/* ------------------------------------------------------------------ CSRF */

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

/** Hentikan request POST bila token CSRF tidak valid. */
function csrf_check(string $backTo): void
{
    $sent = $_POST['_csrf'] ?? '';
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        flash('error', 'Sesi formulir sudah kedaluwarsa. Silakan coba lagi.');
        redirect($backTo);
    }
}

function require_post(string $backTo): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        redirect($backTo);
    }
}

/* ----------------------------------------------------------- Tanggal/jam */

/** "08:00:00" -> "08.00" (format jam seperti pada template). */
function format_jam(string $time): string
{
    return str_replace(':', '.', substr($time, 0, 5));
}

/** "08:00:00","10:00:00" -> "08.00 - 10.00" */
function format_rentang(string $mulai, string $selesai): string
{
    return format_jam($mulai) . ' - ' . format_jam($selesai);
}

/** "08:00:00" -> "08:00" (nilai untuk <input type="time">). */
function jam_input(string $time): string
{
    return substr($time, 0, 5);
}

function valid_jam(string $value): bool
{
    return (bool) preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $value);
}

function bulan_id(int $n): string
{
    static $names = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    return $names[$n] ?? '';
}

function hari_id_dari_tanggal(?int $timestamp = null): string
{
    static $names = [1 => 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
    return $names[(int) date('N', $timestamp ?? time())];
}

function tanggal_id(?string $datetime = null, bool $withTime = true): string
{
    $ts = $datetime ? strtotime($datetime) : time();
    $out = date('j', $ts) . ' ' . bulan_id((int) date('n', $ts)) . ' ' . date('Y', $ts);
    return $withTime ? $out . ', ' . date('H.i', $ts) : $out;
}

function time_ago(string $datetime): string
{
    $diff = time() - strtotime($datetime);
    if ($diff < 60)      return 'baru saja';
    if ($diff < 3600)    return floor($diff / 60) . ' menit lalu';
    if ($diff < 86400)   return floor($diff / 3600) . ' jam lalu';
    if ($diff < 604800)  return floor($diff / 86400) . ' hari lalu';
    return tanggal_id($datetime, false);
}

function initials(string $nama): string
{
    $words = preg_split('/\s+/u', trim($nama)) ?: [];
    $letters = '';
    foreach ($words as $w) {
        $first = mb_substr(preg_replace('/^[^\p{L}]+/u', '', $w) ?? '', 0, 1);
        if ($first !== '') {
            $letters .= mb_strtoupper($first);
        }
        if (mb_strlen($letters) >= 2) {
            break;
        }
    }
    return $letters !== '' ? $letters : '?';
}

/** Persentase berjalannya semester (untuk bilah progres di sidebar). */
function semester_progress(): int
{
    $start = strtotime((string) app_config('semester_mulai'));
    $end   = strtotime((string) app_config('semester_selesai'));
    if (!$start || !$end || $end <= $start) {
        return 0;
    }
    $pct = (time() - $start) / ($end - $start) * 100;
    return (int) max(0, min(100, round($pct)));
}
