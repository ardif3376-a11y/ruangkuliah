<?php
/**
 * Dashboard (Ringkasan) untuk semua peran.
 *   mahasiswa & admin : seluruh jadwal
 *   dosen             : jadwal mata kuliah yang diampu
 * Menampilkan bar notifikasi perubahan jadwal terbaru.
 */
require __DIR__ . '/includes/bootstrap.php';
$user = require_login();

$isDosen = $user['role'] === 'dosen';
$rows    = $isDosen ? q_jadwal($user['id']) : q_jadwal();
$stats   = jadwal_stats($rows);

// Panel jadwal: hari ini bila hari kuliah, selain itu Senin
$hariIni    = hari_id_dari_tanggal();
$hariPanel  = in_array($hariIni, HARI_KULIAH, true) ? $hariIni : 'Senin';
$listPanel  = array_values(array_filter($rows, function ($r) use ($hariPanel) {
    return $r['hari'] === $hariPanel;
}));

// Bar notifikasi
$notifs       = q_notifikasi(3);
$lastRead     = q_notif_terakhir_dibaca($user);
$unreadCount  = q_notif_unread_count($user);

// Kartu "Hari tanpa kuliah"
$kosong = $stats['kosong'];
if (count($kosong) === 0)      { $kosongValue = '—';                       $kosongNote = 'Ada kuliah setiap hari'; }
elseif (count($kosong) <= 2)   { $kosongValue = implode(', ', $kosong);    $kosongNote = 'Tidak ada jadwal terlampir'; }
else                           { $kosongValue = count($kosong) . ' hari';  $kosongNote = implode(', ', $kosong); }

$subtitle = [
    'mahasiswa' => 'Ringkasan jadwal kuliah mingguan Anda.',
    'dosen'     => 'Ringkasan jadwal mengajar untuk mata kuliah yang Anda ampu.',
    'admin'     => 'Ringkasan seluruh jadwal kuliah mingguan.',
][$user['role']];

$page_title = 'Ringkasan';
$page_key   = 'dashboard';
include __DIR__ . '/includes/layout_top.php';
?>
        <section class="page active" id="dashboardPage">
          <div class="page-heading">
            <div><h1>Jadwal perkuliahan</h1><p class="subtitle"><?= e($subtitle) ?></p></div>
            <a class="btn" href="<?= e(url('jadwal.php?cetak=1')) ?>">
              <?= icon('print') ?>
              Cetak jadwal
            </a>
          </div>

          <!-- Bar notifikasi pergantian jadwal -->
          <section class="notice-bar" aria-label="Notifikasi perubahan jadwal">
            <div class="notice-icon"><?= icon('bell') ?></div>
            <div class="notice-body">
              <div class="notice-head">
                <h2>Perubahan jadwal terbaru</h2>
                <?php if ($unreadCount > 0): ?><span class="badge orange"><?= $unreadCount ?> baru</span><?php endif; ?>
                <a class="link-btn" href="<?= e(url('notifikasi.php')) ?>">Lihat semua →</a>
              </div>

              <?php if ($notifs): ?>
                <ul class="notice-list">
                  <?php foreach ($notifs as $n): ?>
                    <li class="<?= (int) $n['id'] > $lastRead ? 'is-new' : '' ?>">
                      <div class="notice-text">
                        <strong><?= e($n['mata_kuliah']) ?> · Kelas <?= e($n['kelas']) ?></strong>
                        <span><?= e(notif_kalimat($n)) ?></span>
                      </div>
                      <?= component_perubahan($n) ?>
                      <time class="notice-time" datetime="<?= e($n['created_at']) ?>" title="<?= e(tanggal_id($n['created_at'])) ?>"><?= e(time_ago($n['created_at'])) ?></time>
                    </li>
                  <?php endforeach; ?>
                </ul>
              <?php else: ?>
                <p class="notice-empty">Belum ada perubahan jadwal. Saat dosen mengganti hari atau jam kuliah, pemberitahuannya muncul di sini.</p>
              <?php endif; ?>
            </div>
          </section>

          <div class="stats">
            <article class="card stat-card">
              <div class="stat-top">
                <span class="stat-label"><?= $isDosen ? 'Mata kuliah diampu' : 'Mata kuliah' ?></span>
                <span class="stat-icon"><?= icon('book-sm') ?></span>
              </div>
              <div class="stat-value"><?= (int) $stats['matkul'] ?></div>
              <div class="stat-note">Termasuk sesi praktikum</div>
            </article>

            <article class="card stat-card">
              <div class="stat-top">
                <span class="stat-label"><?= $isDosen ? 'Hari mengajar' : 'Hari kuliah' ?></span>
                <span class="stat-icon blue"><?= icon('calendar-plain') ?></span>
              </div>
              <div class="stat-value"><?= count($stats['hari']) ?> <span class="stat-unit">hari</span></div>
              <div class="stat-note"><?= $stats['hari'] ? e(implode(', ', $stats['hari'])) : 'Belum ada jadwal' ?></div>
            </article>

            <article class="card stat-card">
              <div class="stat-top">
                <span class="stat-label">Sesi praktikum</span>
                <span class="stat-icon orange"><?= icon('flask') ?></span>
              </div>
              <div class="stat-value"><?= (int) $stats['praktikum'] ?></div>
              <div class="stat-note">Di laboratorium</div>
            </article>

            <article class="card stat-card">
              <div class="stat-top">
                <span class="stat-label">Hari tanpa kuliah</span>
                <span class="stat-icon"><?= icon('minus') ?></span>
              </div>
              <div class="stat-value"><?= e($kosongValue) ?></div>
              <div class="stat-note"><?= e($kosongNote) ?></div>
            </article>
          </div>

          <?php if ($isDosen && !$rows): ?>
            <div class="alert info" role="status"><?= icon('info') ?><span>Anda belum ditetapkan sebagai pengampu mata kuliah apa pun. Hubungi super admin untuk menambahkan mata kuliah Anda.</span></div>
          <?php endif; ?>

          <div class="dashboard-grid">
            <section class="panel">
              <div class="panel-head">
                <h2>Jadwal hari <?= e($hariPanel) ?></h2>
                <a class="link-btn" href="<?= e(url('jadwal.php')) ?>">Lihat semua →</a>
              </div>
              <div id="mondayList">
                <?php if ($listPanel): foreach ($listPanel as $r): ?>
                  <div class="schedule-row">
                    <span class="time"><?= e(format_rentang($r['jam_mulai'], $r['jam_selesai'])) ?></span><span class="marker"></span>
                    <div>
                      <div class="course-name"><?= e($r['matkul']) ?></div>
                      <div class="course-meta">Kelas <?= e($r['kelas']) ?> · Ruang <?= e($r['ruang']) ?><?= !$isDosen ? ' · ' . ($r['dosen'] ? e($r['dosen']) : 'Dosen belum ditentukan') : '' ?></div>
                    </div>
                    <span class="row-badges">
                      <?php if (jadwal_baru_diubah($r)): ?><span class="badge orange">Diubah</span><?php endif; ?>
                      <span class="badge <?= $r['jenis'] === 'Praktikum' ? 'orange' : '' ?>"><?= e($r['jenis']) ?></span>
                    </span>
                  </div>
                <?php endforeach; else: ?>
                  <p class="empty-note">Tidak ada kuliah pada hari <?= e($hariPanel) ?>.</p>
                <?php endif; ?>
              </div>
            </section>

            <section class="panel">
              <div class="panel-head"><h2>Pilih hari</h2></div>
              <div class="day-shortcut" id="dayShortcuts">
                <?php foreach (HARI_KULIAH as $h):
                    $count = count(array_filter($rows, function ($r) use ($h) { return $r['hari'] === $h; })); ?>
                  <a class="day-link" href="<?= e(url('jadwal.php?hari=' . $h)) ?>">
                    <strong><?= e($h) ?></strong>
                    <span><?= $count ? $count . ' sesi →' : 'Tidak ada kuliah' ?></span>
                  </a>
                <?php endforeach; ?>
              </div>
            </section>
          </div>
        </section>
<?php include __DIR__ . '/includes/layout_bottom.php'; ?>
