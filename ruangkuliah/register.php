<?php
/**
 * Pendaftaran mandiri mahasiswa (nama + NPM).
 * Akun dosen hanya dapat dibuat oleh super admin.
 */
require __DIR__ . '/includes/bootstrap.php';

if (current_user()) {
    redirect('dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check('register.php');

    $nama = clean_text(post('nama'));
    $npm  = clean_text(post('npm'));
    old_set(['nama' => $nama, 'npm' => $npm]);

    $error = null;
    if ($nama === '' || $npm === '') {
        $error = 'Isi nama dan NPM terlebih dahulu.';
    } elseif (mb_strlen($nama) < 3 || mb_strlen($nama) > 120 || !preg_match('/\p{L}/u', $nama)) {
        $error = 'Nama harus 3–120 karakter dan memuat huruf.';
    } elseif (!preg_match('/^[A-Za-z0-9]{5,30}$/', $npm)) {
        $error = 'NPM hanya boleh huruf/angka tanpa spasi, 5–30 karakter.';
    } elseif (q_ident_exists('mahasiswa', $npm)) {
        $error = 'NPM ini sudah terdaftar. Silakan masuk dengan nama dan NPM tersebut.';
    }

    if ($error !== null) {
        flash('error', $error);
        redirect('register.php');
    }

    $st = db()->prepare('INSERT INTO mahasiswa (nama, npm) VALUES (?, ?)');
    try {
        $st->execute([$nama, $npm]);
    } catch (PDOException $e) {
        flash('error', 'NPM ini sudah terdaftar. Silakan masuk dengan nama dan NPM tersebut.');
        redirect('register.php');
    }

    unset($_SESSION['_old']);
    login_user('mahasiswa', ['id' => (int) db()->lastInsertId(), 'nama' => $nama, 'ident' => $npm]);
    flash('success', 'Pendaftaran berhasil. Selamat datang, ' . $nama . '.');
    redirect('dashboard.php');
}

$auth_title   = 'Daftar mahasiswa';
$auth_heading = 'Daftar untuk melihat jadwal kuliahmu.';
$auth_text    = 'Cukup nama dan NPM. Perubahan jadwal dari dosen akan muncul langsung di dashboard.';
include __DIR__ . '/includes/auth_top.php';
?>
      <form class="login-form" method="post" action="<?= e(url('register.php')) ?>" autocomplete="off">
        <?= csrf_field() ?>

        <h2>Daftar mahasiswa</h2>
        <p class="subtitle">Buat akun dengan nama lengkap dan NPM. Untuk masuk berikutnya, gunakan data yang sama.</p>

        <?php foreach ($alerts as $msg): ?>
          <div class="alert error" role="alert"><?= icon('alert') ?><span><?= e($msg) ?></span></div>
        <?php endforeach; ?>

        <div class="field">
          <label for="nama">Nama lengkap</label>
          <input id="nama" name="nama" type="text" required minlength="3" maxlength="120"
                 placeholder="Nama sesuai data mahasiswa" value="<?= e(old('nama')) ?>">
        </div>
        <div class="field">
          <label for="npm">NPM</label>
          <input id="npm" name="npm" type="text" required minlength="5" maxlength="30"
                 placeholder="Nomor pokok mahasiswa" value="<?= e(old('npm')) ?>">
        </div>

        <button class="btn" type="submit">Daftar dan masuk <span aria-hidden="true">→</span></button>
        <div class="login-hint">Sudah punya akun? <a href="<?= e(url('login.php')) ?>">Masuk di sini</a></div>
      </form>
<?php include __DIR__ . '/includes/auth_bottom.php'; ?>
