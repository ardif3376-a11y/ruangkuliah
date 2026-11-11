<?php
/**
 * Ubah hari & jam sebuah sesi jadwal.
 *   - dosen : hanya untuk mata kuliah yang diampu
 *   - admin : semua sesi
 * Setiap perubahan dicatat sebagai notifikasi untuk semua pengguna.
 */
require __DIR__ . '/../includes/bootstrap.php';

$user   = require_role(['dosen', 'admin']);
$return = safe_return(post('return'));
require_post($return);
csrf_check($return);

$id      = (int) post('jadwal_id');
$hari    = post('hari');
$mulai   = trim(post('jam_mulai'));
$selesai = trim(post('jam_selesai'));

$row = q_jadwal_find($id);
if (!$row) {
    flash('error', 'Jadwal tidak ditemukan.');
    redirect($return);
}

// Otorisasi di sisi server (tombol di tampilan bukan jaminan keamanan)
if (!can_edit_jadwal($user, $row)) {
    flash('error', 'Anda hanya dapat mengubah jadwal mata kuliah yang Anda ampu.');
    redirect($return);
}

// Validasi input
if (!in_array($hari, HARI_KULIAH, true)) {
    flash('error', 'Hari tidak valid.');
    redirect($return);
}
if (!valid_jam($mulai) || !valid_jam($selesai)) {
    flash('error', 'Format jam tidak valid.');
    redirect($return);
}
if ($selesai <= $mulai) {
    flash('error', 'Jam selesai harus lebih akhir dari jam mulai.');
    redirect($return);
}

$mulaiDb   = $mulai . ':00';
$selesaiDb = $selesai . ':00';

// Tidak ada perubahan?
if ($row['hari'] === $hari && $row['jam_mulai'] === $mulaiDb && $row['jam_selesai'] === $selesaiDb) {
    flash('info', 'Tidak ada perubahan pada jadwal ' . $row['matkul'] . '.');
    redirect($return);
}

// Bentrok ruangan
$bentrok = q_conflict_ruang($id, $row['ruang'], $hari, $mulaiDb, $selesaiDb);
if ($bentrok) {
    flash('error', sprintf(
        'Ruang %s sudah dipakai %s (Kelas %s) pada %s, %s.',
        $bentrok['ruang'], $bentrok['matkul'], $bentrok['kelas'],
        $bentrok['hari'], format_rentang($bentrok['jam_mulai'], $bentrok['jam_selesai'])
    ));
    redirect($return);
}

// Bentrok jadwal mengajar dosen
if ($row['dosen_id'] !== null) {
    $bentrok = q_conflict_dosen((int) $row['dosen_id'], $hari, $mulaiDb, $selesaiDb, $id, 0);
    if ($bentrok) {
        flash('error', sprintf(
            'Bentrok dengan jadwal mengajar dosen: %s (Kelas %s) pada %s, %s.',
            $bentrok['matkul'], $bentrok['kelas'],
            $bentrok['hari'], format_rentang($bentrok['jam_mulai'], $bentrok['jam_selesai'])
        ));
        redirect($return);
    }
}

try {
    q_update_jadwal($row, $hari, $mulaiDb, $selesaiDb, $user);
} catch (Throwable $e) {
    flash('error', 'Jadwal gagal disimpan. Silakan coba lagi.');
    redirect($return);
}

flash('success', sprintf(
    'Jadwal %s diperbarui menjadi %s, %s. Notifikasi dikirim ke semua pengguna.',
    $row['matkul'], $hari, format_rentang($mulaiDb, $selesaiDb)
));
redirect($return);
