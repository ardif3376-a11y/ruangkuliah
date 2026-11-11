<?php
/**
 * Autentikasi & otorisasi.
 *
 * Peran:
 *   mahasiswa -> masuk dengan nama + NPM
 *   dosen     -> masuk dengan nama + NIP
 *   admin     -> masuk dengan username + kata sandi (super admin)
 */

const ROLE_LABEL = [
    'mahasiswa' => 'Mahasiswa',
    'dosen'     => 'Dosen',
    'admin'     => 'Super Admin',
];

/** Tabel & kolom identitas untuk tiap peran. */
const ROLE_TABLE = [
    'mahasiswa' => ['table' => 'mahasiswa', 'ident' => 'npm'],
    'dosen'     => ['table' => 'dosen',     'ident' => 'nip'],
    'admin'     => ['table' => 'admin',     'ident' => 'username'],
];

function is_valid_role(string $role): bool
{
    return isset(ROLE_TABLE[$role]);
}

/* ---------------------------------------------------------------- Sesi */

function login_user(string $role, array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'role'  => $role,
        'id'    => (int) $user['id'],
        'nama'  => $user['nama'],
        'ident' => $user['ident'],
    ];
    unset($_SESSION['_login_fail']);
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/**
 * Pengguna yang sedang masuk, atau null.
 * Data diperiksa ulang ke database agar akun yang sudah dihapus
 * atau diubah super admin langsung berlaku.
 */
function current_user(): ?array
{
    static $cache = false;
    if ($cache !== false) {
        return $cache;
    }
    if (empty($_SESSION['user']['role']) || empty($_SESSION['user']['id'])) {
        return $cache = null;
    }

    $role = (string) $_SESSION['user']['role'];
    if (!is_valid_role($role)) {
        return $cache = null;
    }

    $t  = ROLE_TABLE[$role];
    $st = db()->prepare("SELECT id, nama, {$t['ident']} AS ident FROM {$t['table']} WHERE id = ? LIMIT 1");
    $st->execute([(int) $_SESSION['user']['id']]);
    $row = $st->fetch();
    if (!$row) {
        return $cache = null;
    }

    $_SESSION['user']['nama']  = $row['nama'];
    $_SESSION['user']['ident'] = $row['ident'];

    return $cache = [
        'role'  => $role,
        'id'    => (int) $row['id'],
        'nama'  => $row['nama'],
        'ident' => $row['ident'],
    ];
}

function require_login(): array
{
    $user = current_user();
    if (!$user) {
        if (!empty($_SESSION['user'])) {
            unset($_SESSION['user']);
            flash('error', 'Akun Anda tidak ditemukan lagi. Silakan masuk kembali.');
        }
        redirect('login.php');
    }
    return $user;
}

function require_role(array $roles): array
{
    $user = require_login();
    if (!in_array($user['role'], $roles, true)) {
        flash('error', 'Anda tidak memiliki akses ke halaman tersebut.');
        redirect('dashboard.php');
    }
    return $user;
}

function is_role(string $role): bool
{
    $user = current_user();
    return $user !== null && $user['role'] === $role;
}

/* --------------------------------------------------------- Proses login */

/**
 * Cocokkan kredensial. Mengembalikan ['id','nama','ident'] atau null.
 *   mahasiswa/dosen : $nama = nama lengkap, $secret = NPM / NIP
 *   admin           : $nama = username,     $secret = kata sandi
 */
function auth_attempt(string $role, string $nama, string $secret): ?array
{
    if (!is_valid_role($role)) {
        return null;
    }
    $pdo = db();

    if ($role === 'admin') {
        $st = $pdo->prepare('SELECT id, nama, username, password_hash FROM admin WHERE username = ? LIMIT 1');
        $st->execute([$nama]);
        $row = $st->fetch();

        // password_verify tetap dipanggil walau akun tidak ada (waktu respons sama)
        $hash = $row['password_hash'] ?? '$2y$10$Ce1VLN9PnYZIYatf67yfRex258Lp43YdaCnd4U7UUcp4E8sVqNamO';
        $ok   = password_verify($secret, $hash);
        if ($row && $ok) {
            return ['id' => (int) $row['id'], 'nama' => $row['nama'], 'ident' => $row['username']];
        }
        return null;
    }

    $t  = ROLE_TABLE[$role];
    $st = $pdo->prepare(
        "SELECT id, nama, {$t['ident']} AS ident FROM {$t['table']}
         WHERE {$t['ident']} = ? AND nama = ? LIMIT 1"
    );
    $st->execute([$secret, $nama]);
    $row = $st->fetch();

    return $row ? ['id' => (int) $row['id'], 'nama' => $row['nama'], 'ident' => $row['ident']] : null;
}

/* ------------------------------------------------ Pembatasan percobaan */

/** Sisa detik penguncian login (0 = tidak terkunci). */
function login_lock_remaining(): int
{
    $until = (int) ($_SESSION['_login_fail']['until'] ?? 0);
    return max(0, $until - time());
}

function login_register_failure(): void
{
    $n = (int) ($_SESSION['_login_fail']['n'] ?? 0) + 1;
    $max = (int) app_config('login_max_gagal');
    $_SESSION['_login_fail'] = ['n' => $n, 'until' => 0];
    if ($n >= $max) {
        $_SESSION['_login_fail'] = [
            'n'     => 0,
            'until' => time() + (int) app_config('login_kunci_detik'),
        ];
    }
}
