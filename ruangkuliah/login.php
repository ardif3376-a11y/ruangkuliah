<?php
/**
 * Halaman masuk untuk 3 peran:
 *   - Mahasiswa  : nama + NPM
 *   - Dosen      : nama + NIP
 *   - Super admin: username + kata sandi
 */
require __DIR__ . '/includes/bootstrap.php';

if (current_user()) {
    redirect('dashboard.php');
}

$role = query('role', 'mahasiswa');
if (!is_valid_role($role)) {
    $role = 'mahasiswa';
}

/* ------------------------------------------------------------- Proses */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = post('role', 'mahasiswa');
    if (!is_valid_role($role)) {
        $role = 'mahasiswa';
    }
    $back = 'login.php?role=' . $role;
    csrf_check($back);

    $nama   = clean_text(post('nama'));
    $secret = $role === 'admin' ? post('secret') : clean_text(post('secret'));

    $kunci = login_lock_remaining();
    if ($kunci > 0) {
        flash('error', "Terlalu banyak percobaan gagal. Coba lagi dalam {$kunci} detik.");
        redirect($back);
    }

    if ($nama === '' || $secret === '') {
        old_set(['nama' => $nama]);
        flash('error', 'Isi semua kolom terlebih dahulu.');
        redirect($back);
    }

    $found = auth_attempt($role, $nama, $secret);
    if ($found) {
        login_user($role, $found);
        flash('success', 'Berhasil masuk. Selamat datang, ' . $found['nama'] . '.');
        redirect('dashboard.php');
    }

    login_register_failure();
    old_set(['nama' => $nama]);
    $pesan = [
        'mahasiswa' => 'Nama atau NPM tidak cocok. Periksa kembali, atau daftar bila belum punya akun.',
        'dosen'     => 'Nama atau NIP tidak cocok. Akun dosen dibuat oleh super admin.',
        'admin'     => 'Username atau kata sandi salah.',
    ];
    flash('error', $pesan[$role]);
    redirect($back);
}

/* ------------------------------------------------------------ Tampilan */
$fields = [
    'mahasiswa' => ['Nama lengkap',   'Nama sesuai data mahasiswa', 'NPM',        'Nomor pokok mahasiswa', 'text'],
    'dosen'     => ['Nama dosen',     'Nama beserta gelar',         'NIP',        'Nomor induk pegawai',   'text'],
    'admin'     => ['Username',       'admin',                      'Kata sandi', '••••••••',              'password'],
];
[$labelNama, $phNama, $labelSecret, $phSecret, $typeSecret] = $fields[$role];

$subtitle = [
    'mahasiswa' => 'Masuk dengan nama dan NPM untuk melihat jadwal perkuliahan.',
    'dosen'     => 'Masuk dengan nama dan NIP untuk mengelola jadwal mata kuliah Anda.',
    'admin'     => 'Masuk sebagai super admin untuk mengelola dosen dan jadwal.',
][$role];

$auth_title = 'Masuk';
include __DIR__ . '/includes/auth_top.php';
?>
      <form class="login-form" method="post" action="<?= e(url('login.php')) ?>" autocomplete="off">
        <?= csrf_field() ?>
        <input type="hidden" name="role" value="<?= e($role) ?>">

        <h2>Selamat datang</h2>
        <p class="subtitle"><?= e($subtitle) ?></p>

        <div class="week-tabs role-tabs" role="tablist" aria-label="Masuk sebagai">
          <a class="day-tab <?= $role === 'mahasiswa' ? 'active' : '' ?>" href="<?= e(url('login.php?role=mahasiswa')) ?>">Mahasiswa</a>
          <a class="day-tab <?= $role === 'dosen' ? 'active' : '' ?>" href="<?= e(url('login.php?role=dosen')) ?>">Dosen</a>
          <a class="day-tab <?= $role === 'admin' ? 'active' : '' ?>" href="<?= e(url('login.php?role=admin')) ?>">Super admin</a>
        </div>

        <?php foreach ($alerts as $msg): ?>
          <div class="alert error" role="alert"><?= icon('alert') ?><span><?= e($msg) ?></span></div>
        <?php endforeach; ?>

        <div class="field">
          <label for="nama"><?= e($labelNama) ?></label>
          <input id="nama" name="nama" type="text" required maxlength="120"
                 placeholder="<?= e($phNama) ?>" value="<?= e(old('nama')) ?>"
                 <?= $role === 'admin' ? 'autocomplete="username"' : '' ?>>
        </div>
        <div class="field">
          <label for="secret"><?= e($labelSecret) ?></label>
          <input id="secret" name="secret" type="<?= e($typeSecret) ?>" required maxlength="100"
                 placeholder="<?= e($phSecret) ?>"
                 <?= $role === 'admin' ? 'autocomplete="current-password"' : 'inputmode="text"' ?>>
        </div>

        <button class="btn" type="submit">Masuk ke sistem <span aria-hidden="true">→</span></button>

        <?php if ($role === 'mahasiswa'): ?>
          <div class="login-hint">Belum punya akun? <a href="<?= e(url('register.php')) ?>">Daftar sebagai mahasiswa</a></div>
        <?php elseif ($role === 'dosen'): ?>
          <div class="login-hint">Tulis nama persis seperti yang didaftarkan super admin.<br>Belum terdaftar? Hubungi super admin.</div>
        <?php elseif (app_config('show_demo_hint')): ?>
          <div class="login-hint demo-hint">Akun demo super admin<br>Username <strong>admin</strong> · Kata sandi <strong>admin123</strong></div>
        <?php endif; ?>
      </form>
<?php include __DIR__ . '/includes/auth_bottom.php'; ?>
