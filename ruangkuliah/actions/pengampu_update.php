<?php
/**
 * Pasang / ganti / lepas dosen pengampu sebuah mata kuliah (khusus super admin).
 */
require __DIR__ . '/../includes/bootstrap.php';

$user = require_role(['admin']);
$back = 'admin/pengampu.php';
require_post($back);
csrf_check($back);

$mkId     = (int) post('mata_kuliah_id');
$dosenRaw = post('dosen_id');
$dosenId  = $dosenRaw === '' ? null : (int) $dosenRaw;

$mk = q_matkul_find($mkId);
if (!$mk) {
    flash('error', 'Mata kuliah tidak ditemukan.');
    redirect($back);
}

$current = $mk['dosen_id'] === null ? null : (int) $mk['dosen_id'];
if ($current === $dosenId) {
    flash('info', 'Dosen pengampu ' . $mk['nama'] . ' tidak berubah.');
    redirect($back);
}

$dosen = null;
if ($dosenId !== null) {
    $dosen = q_dosen_find($dosenId);
    if (!$dosen) {
        flash('error', 'Dosen tidak ditemukan.');
        redirect($back);
    }

    // Satu dosen tidak boleh mengajar dua sesi di jam yang sama
    foreach (q_jadwal_by_mk($mkId) as $s) {
        $bentrok = q_conflict_dosen($dosenId, $s['hari'], $s['jam_mulai'], $s['jam_selesai'], 0, $mkId);
        if ($bentrok) {
            flash('error', sprintf(
                '%s tidak dapat dipasang: jadwal %s (%s, %s) bentrok dengan %s (Kelas %s) pada %s, %s.',
                $dosen['nama'], $mk['nama'], $s['hari'], format_rentang($s['jam_mulai'], $s['jam_selesai']),
                $bentrok['matkul'], $bentrok['kelas'],
                $bentrok['hari'], format_rentang($bentrok['jam_mulai'], $bentrok['jam_selesai'])
            ));
            redirect($back);
        }
    }
}

q_set_pengampu($mkId, $dosenId);

flash('success', $dosen
    ? $dosen['nama'] . ' kini mengampu ' . $mk['nama'] . '.'
    : 'Dosen pengampu ' . $mk['nama'] . ' dilepas.');
redirect($back);
