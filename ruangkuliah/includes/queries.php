<?php
/**
 * Lapisan akses data. Semua query memakai prepared statement.
 */

/* ============================================================== Jadwal */

const SQL_JADWAL = "
    SELECT j.id, j.hari, j.jam_mulai, j.jam_selesai, j.kelas, j.ruang, j.jenis, j.diubah_pada,
           m.id AS mk_id, m.nama AS matkul, m.dosen_id, d.nama AS dosen
    FROM jadwal j
    JOIN mata_kuliah m ON m.id = j.mata_kuliah_id
    LEFT JOIN dosen d  ON d.id = m.dosen_id";

/** Semua sesi jadwal, urut hari lalu jam. Opsional: hanya milik satu dosen. */
function q_jadwal(?int $dosenId = null): array
{
    if ($dosenId !== null) {
        $st = db()->prepare(SQL_JADWAL . ' WHERE m.dosen_id = ? ORDER BY j.hari, j.jam_mulai');
        $st->execute([$dosenId]);
    } else {
        $st = db()->query(SQL_JADWAL . ' ORDER BY j.hari, j.jam_mulai');
    }
    return $st->fetchAll();
}

function q_jadwal_find(int $id): ?array
{
    $st = db()->prepare(SQL_JADWAL . ' WHERE j.id = ? LIMIT 1');
    $st->execute([$id]);
    $row = $st->fetch();
    return $row ?: null;
}

/** Apakah pengguna boleh mengubah sesi ini? (admin: semua, dosen: yang diampu) */
function can_edit_jadwal(?array $user, array $row): bool
{
    if (!$user) {
        return false;
    }
    if ($user['role'] === 'admin') {
        return true;
    }
    return $user['role'] === 'dosen'
        && $row['dosen_id'] !== null
        && (int) $row['dosen_id'] === (int) $user['id'];
}

/** Sesi yang diubah dalam 7 hari terakhir ditandai "Diubah". */
function jadwal_baru_diubah(array $row): bool
{
    return !empty($row['diubah_pada']) && (time() - strtotime($row['diubah_pada'])) < 7 * 86400;
}

/** Ringkasan untuk kartu statistik dashboard. */
function jadwal_stats(array $rows): array
{
    $days = [];
    $praktikum = 0;
    $courses = [];
    foreach ($rows as $r) {
        $days[$r['hari']] = true;
        $courses[$r['mk_id']] = true;
        if ($r['jenis'] === 'Praktikum') {
            $praktikum++;
        }
    }
    $aktif = array_values(array_filter(HARI_KULIAH, function ($h) use ($days) {
        return isset($days[$h]);
    }));
    $kosong = array_values(array_diff(HARI_KULIAH, $aktif));

    return [
        'matkul'    => count($courses),
        'hari'      => $aktif,
        'praktikum' => $praktikum,
        'kosong'    => $kosong,
    ];
}

/** Bentrok ruangan: ruang yang sama, hari sama, jam saling tumpang tindih. */
function q_conflict_ruang(int $jadwalId, string $ruang, string $hari, string $mulai, string $selesai): ?array
{
    $st = db()->prepare(
        "SELECT m.nama AS matkul, j.kelas, j.hari, j.jam_mulai, j.jam_selesai, j.ruang
         FROM jadwal j JOIN mata_kuliah m ON m.id = j.mata_kuliah_id
         WHERE j.id <> ? AND j.ruang = ? AND j.hari = ? AND j.jam_mulai < ? AND j.jam_selesai > ?
         LIMIT 1"
    );
    $st->execute([$jadwalId, $ruang, $hari, $selesai, $mulai]);
    return $st->fetch() ?: null;
}

/**
 * Bentrok dosen: dosen yang sama mengajar dua sesi yang jamnya tumpang tindih.
 * $excludeJadwalId : abaikan satu sesi (sesi yang sedang diubah)
 * $excludeMkId     : abaikan semua sesi milik satu mata kuliah (saat pasang dosen)
 */
function q_conflict_dosen(int $dosenId, string $hari, string $mulai, string $selesai,
                          int $excludeJadwalId = 0, int $excludeMkId = 0): ?array
{
    $st = db()->prepare(
        "SELECT m.nama AS matkul, j.kelas, j.hari, j.jam_mulai, j.jam_selesai
         FROM jadwal j JOIN mata_kuliah m ON m.id = j.mata_kuliah_id
         WHERE m.dosen_id = ? AND j.id <> ? AND j.mata_kuliah_id <> ?
           AND j.hari = ? AND j.jam_mulai < ? AND j.jam_selesai > ?
         LIMIT 1"
    );
    $st->execute([$dosenId, $excludeJadwalId, $excludeMkId, $hari, $selesai, $mulai]);
    return $st->fetch() ?: null;
}

/**
 * Simpan perubahan hari/jam + catat notifikasi dalam satu transaksi.
 * $row = data sesi sebelum diubah (hasil q_jadwal_find).
 */
function q_update_jadwal(array $row, string $hari, string $mulai, string $selesai, array $actor): void
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $up = $pdo->prepare(
            'UPDATE jadwal SET hari = ?, jam_mulai = ?, jam_selesai = ?, diubah_pada = NOW() WHERE id = ?'
        );
        $up->execute([$hari, $mulai, $selesai, $row['id']]);

        $in = $pdo->prepare(
            'INSERT INTO notifikasi
               (jadwal_id, mata_kuliah, kelas, hari_lama, mulai_lama, selesai_lama,
                hari_baru, mulai_baru, selesai_baru, pengubah_role, pengubah_nama)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $in->execute([
            $row['id'], $row['matkul'], $row['kelas'],
            $row['hari'], $row['jam_mulai'], $row['jam_selesai'],
            $hari, $mulai, $selesai,
            $actor['role'] === 'admin' ? 'admin' : 'dosen',
            $actor['nama'],
        ]);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/* ======================================================== Mata kuliah */

/**
 * Mata kuliah beserta dosen pengampu dan daftar sesinya.
 * Opsional: hanya milik satu dosen.
 */
function q_matkul(?int $dosenId = null): array
{
    $sql = 'SELECT m.id, m.nama, m.dosen_id, d.nama AS dosen, d.nip
            FROM mata_kuliah m LEFT JOIN dosen d ON d.id = m.dosen_id';
    if ($dosenId !== null) {
        $st = db()->prepare($sql . ' WHERE m.dosen_id = ? ORDER BY m.id');
        $st->execute([$dosenId]);
    } else {
        $st = db()->query($sql . ' ORDER BY m.id');
    }
    $list = $st->fetchAll();

    $sessions = [];
    foreach (q_jadwal($dosenId) as $j) {
        $sessions[$j['mk_id']][] = $j;
    }
    foreach ($list as &$m) {
        $m['sesi'] = $sessions[$m['id']] ?? [];
    }
    unset($m);
    return $list;
}

/** Pasang / lepas dosen pengampu. $dosenId null = belum ditentukan. */
function q_set_pengampu(int $mkId, ?int $dosenId): void
{
    $st = db()->prepare('UPDATE mata_kuliah SET dosen_id = ? WHERE id = ?');
    $st->execute([$dosenId, $mkId]);
}

function q_matkul_find(int $id): ?array
{
    $st = db()->prepare('SELECT id, nama, dosen_id FROM mata_kuliah WHERE id = ? LIMIT 1');
    $st->execute([$id]);
    return $st->fetch() ?: null;
}

/* ========================================================== Notifikasi */

function q_notifikasi(int $limit = 50): array
{
    $st = db()->prepare('SELECT * FROM notifikasi ORDER BY id DESC LIMIT ' . (int) $limit);
    $st->execute();
    return $st->fetchAll();
}

function q_notif_terakhir_dibaca(array $user): int
{
    $st = db()->prepare('SELECT terakhir_id FROM notifikasi_dibaca WHERE role = ? AND user_id = ?');
    $st->execute([$user['role'], $user['id']]);
    return (int) ($st->fetchColumn() ?: 0);
}

function q_notif_unread_count(array $user): int
{
    $st = db()->prepare('SELECT COUNT(*) FROM notifikasi WHERE id > ?');
    $st->execute([q_notif_terakhir_dibaca($user)]);
    return (int) $st->fetchColumn();
}

function q_notif_mark_read(array $user): void
{
    $max = (int) db()->query('SELECT COALESCE(MAX(id), 0) FROM notifikasi')->fetchColumn();
    $st = db()->prepare(
        'INSERT INTO notifikasi_dibaca (role, user_id, terakhir_id) VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE terakhir_id = VALUES(terakhir_id)'
    );
    $st->execute([$user['role'], $user['id'], $max]);
}

/** Kalimat ringkas untuk satu notifikasi (nama mata kuliah tampil di judul kartu). */
function notif_kalimat(array $n): string
{
    $siapa = $n['pengubah_role'] === 'admin'
        ? 'Super admin'
        : 'Dosen ' . $n['pengubah_nama'];

    if ($n['hari_lama'] === $n['hari_baru']) {
        return $siapa . ' mengubah jam kuliah pada hari ' . $n['hari_lama'] . '.';
    }
    return $siapa . ' memindahkan jadwal dari hari ' . $n['hari_lama']
         . ' ke hari ' . $n['hari_baru'] . '.';
}

function notif_lama(array $n): string
{
    return $n['hari_lama'] . ', ' . format_rentang($n['mulai_lama'], $n['selesai_lama']);
}

function notif_baru(array $n): string
{
    return $n['hari_baru'] . ', ' . format_rentang($n['mulai_baru'], $n['selesai_baru']);
}

/* ============================================================ Pengguna */

function q_dosen_all(): array
{
    return db()->query(
        "SELECT d.id, d.nama, d.nip, d.created_at,
                GROUP_CONCAT(m.nama ORDER BY m.id SEPARATOR '||') AS matkul
         FROM dosen d LEFT JOIN mata_kuliah m ON m.dosen_id = d.id
         GROUP BY d.id, d.nama, d.nip, d.created_at
         ORDER BY d.nama"
    )->fetchAll();
}

function q_dosen_options(): array
{
    return db()->query('SELECT id, nama, nip FROM dosen ORDER BY nama')->fetchAll();
}

function q_mahasiswa_all(): array
{
    return db()->query('SELECT id, nama, npm, created_at FROM mahasiswa ORDER BY nama')->fetchAll();
}

/** Cek apakah nilai unik (NIP/NPM) sudah dipakai. $exceptId untuk mode ubah. */
function q_ident_exists(string $role, string $value, int $exceptId = 0): bool
{
    $t  = ROLE_TABLE[$role];
    $st = db()->prepare("SELECT 1 FROM {$t['table']} WHERE {$t['ident']} = ? AND id <> ? LIMIT 1");
    $st->execute([$value, $exceptId]);
    return (bool) $st->fetchColumn();
}

function q_counts(): array
{
    $pdo = db();
    return [
        'dosen'       => (int) $pdo->query('SELECT COUNT(*) FROM dosen')->fetchColumn(),
        'mahasiswa'   => (int) $pdo->query('SELECT COUNT(*) FROM mahasiswa')->fetchColumn(),
        'tanpa_dosen' => (int) $pdo->query('SELECT COUNT(*) FROM mata_kuliah WHERE dosen_id IS NULL')->fetchColumn(),
    ];
}

/** Semua sesi milik satu mata kuliah. */
function q_jadwal_by_mk(int $mkId): array
{
    $st = db()->prepare('SELECT id, hari, jam_mulai, jam_selesai, kelas FROM jadwal WHERE mata_kuliah_id = ?');
    $st->execute([$mkId]);
    return $st->fetchAll();
}

function q_dosen_find(int $id): ?array
{
    $st = db()->prepare('SELECT id, nama, nip FROM dosen WHERE id = ? LIMIT 1');
    $st->execute([$id]);
    return $st->fetch() ?: null;
}
