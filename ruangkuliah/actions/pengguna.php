<?php
/**
 * Tambah / ubah / hapus data dosen & mahasiswa (khusus super admin).
 *   role : dosen | mahasiswa
 *   aksi : tambah | ubah | hapus
 */
require __DIR__ . '/../includes/bootstrap.php';

$user = require_role(['admin']);

$role = post('role');
if (!in_array($role, ['dosen', 'mahasiswa'], true)) {
    redirect('admin/index.php');
}
$back = 'admin/' . $role . '.php';
require_post($back);
csrf_check($back);

$t      = ROLE_TABLE[$role];            // ['table' => ..., 'ident' => nip|npm]
$label  = $role === 'dosen' ? 'NIP' : 'NPM';
$kata   = $role === 'dosen' ? 'Dosen' : 'Mahasiswa';
$aksi   = post('aksi');
$id     = (int) post('id');

/** Validasi nama + NIP/NPM. Mengembalikan pesan error atau null. */
$validasi = function (string $nama, string $ident) use ($label): ?string {
    if ($nama === '' || $ident === '') {
        return 'Nama dan ' . $label . ' wajib diisi.';
    }
    if (mb_strlen($nama) < 3 || mb_strlen($nama) > 120 || !preg_match('/\p{L}/u', $nama)) {
        return 'Nama harus 3–120 karakter dan memuat huruf.';
    }
    if (!preg_match('/^[A-Za-z0-9]{5,30}$/', $ident)) {
        return $label . ' hanya boleh huruf/angka tanpa spasi, 5–30 karakter.';
    }
    return null;
};

/* ---------------------------------------------------------------- Tambah */
if ($aksi === 'tambah') {
    $nama  = clean_text(post('nama'));
    $ident = clean_text(post('ident'));
    old_set(['nama' => $nama, 'ident' => $ident]);

    $error = $validasi($nama, $ident);
    if ($error === null && q_ident_exists($role, $ident)) {
        $error = $label . ' ' . $ident . ' sudah terdaftar.';
    }
    if ($error !== null) {
        flash('error', $error);
        redirect($back);
    }

    try {
        $st = db()->prepare("INSERT INTO {$t['table']} (nama, {$t['ident']}) VALUES (?, ?)");
        $st->execute([$nama, $ident]);
    } catch (PDOException $e) {
        flash('error', $label . ' ' . $ident . ' sudah terdaftar.');
        redirect($back);
    }

    unset($_SESSION['_old']);
    flash('success', $kata . ' ' . $nama . ' berhasil ditambahkan.');
    redirect($back);
}

/* ------------------------------------------------------------------ Ubah */
if ($aksi === 'ubah') {
    $nama  = clean_text(post('nama'));
    $ident = clean_text(post('ident'));

    $st = db()->prepare("SELECT id FROM {$t['table']} WHERE id = ?");
    $st->execute([$id]);
    if (!$st->fetchColumn()) {
        flash('error', $kata . ' tidak ditemukan.');
        redirect($back);
    }

    $error = $validasi($nama, $ident);
    if ($error === null && q_ident_exists($role, $ident, $id)) {
        $error = $label . ' ' . $ident . ' sudah dipakai pengguna lain.';
    }
    if ($error !== null) {
        flash('error', $error);
        redirect($back);
    }

    try {
        $st = db()->prepare("UPDATE {$t['table']} SET nama = ?, {$t['ident']} = ? WHERE id = ?");
        $st->execute([$nama, $ident, $id]);
    } catch (PDOException $e) {
        flash('error', $label . ' ' . $ident . ' sudah dipakai pengguna lain.');
        redirect($back);
    }

    flash('success', 'Data ' . strtolower($kata) . ' ' . $nama . ' diperbarui.');
    redirect($back);
}

/* ----------------------------------------------------------------- Hapus */
if ($aksi === 'hapus') {
    $st = db()->prepare("SELECT nama FROM {$t['table']} WHERE id = ?");
    $st->execute([$id]);
    $nama = $st->fetchColumn();
    if ($nama === false) {
        flash('error', $kata . ' tidak ditemukan.');
        redirect($back);
    }

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $pdo->prepare("DELETE FROM {$t['table']} WHERE id = ?")->execute([$id]);
        $pdo->prepare('DELETE FROM notifikasi_dibaca WHERE role = ? AND user_id = ?')->execute([$role, $id]);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        flash('error', $kata . ' gagal dihapus. Silakan coba lagi.');
        redirect($back);
    }

    flash('success', $kata . ' ' . $nama . ' dihapus.');
    redirect($back);
}

redirect($back);
