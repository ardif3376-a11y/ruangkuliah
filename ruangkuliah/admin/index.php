<?php
/**
 * Administrasi › Ringkasan (khusus super admin).
 * Mempertahankan tabel ringkasan per hari dari template asli.
 */
require __DIR__ . '/../includes/bootstrap.php';
$user = require_role(['admin']);

$rows   = q_jadwal();
$counts = q_counts();

$page_title = 'Administrasi';
$page_key   = 'admin';
include __DIR__ . '/../includes/layout_top.php';
?>
        <section class="page active" id="adminPage">
          <div class="page-heading">
            <div><h1>Administrasi jadwal</h1><p class="subtitle">Ringkasan data akademik dan pengelolaan dosen serta mahasiswa.</p></div>
          </div>

          <?php component_admin_tabs('index'); ?>

          <div class="stats stats-3">
            <article class="card stat-card">
              <div class="stat-top"><span class="stat-label">Dosen terdaftar</span><span class="stat-icon blue"><?= icon('users') ?></span></div>
              <div class="stat-value"><?= $counts['dosen'] ?></div>
              <div class="stat-note"><a href="<?= e(url('admin/dosen.php')) ?>">Kelola data dosen →</a></div>
            </article>
            <article class="card stat-card">
              <div class="stat-top"><span class="stat-label">Mahasiswa terdaftar</span><span class="stat-icon"><?= icon('user-plus') ?></span></div>
              <div class="stat-value"><?= $counts['mahasiswa'] ?></div>
              <div class="stat-note"><a href="<?= e(url('admin/mahasiswa.php')) ?>">Kelola data mahasiswa →</a></div>
            </article>
            <article class="card stat-card">
              <div class="stat-top"><span class="stat-label">Mata kuliah tanpa dosen</span><span class="stat-icon orange"><?= icon('book-sm') ?></span></div>
              <div class="stat-value"><?= $counts['tanpa_dosen'] ?></div>
              <div class="stat-note"><a href="<?= e(url('admin/pengampu.php')) ?>">Pasang dosen pengampu →</a></div>
            </article>
          </div>

          <div class="panel">
            <div class="panel-head">
              <h2>Informasi jadwal</h2>
              <span class="badge">Database MySQL</span>
            </div>
            <p class="subtitle" style="margin-bottom:18px">Ringkasan jumlah sesi dan ruangan yang terpakai pada setiap hari perkuliahan.</p>
            <div class="table-wrap">
              <table>
                <thead>
                  <tr>
                    <th>Hari</th>
                    <th>Jumlah sesi</th>
                    <th>Ruangan terpakai</th>
                  </tr>
                </thead>
                <tbody id="adminRows">
                  <?php foreach (HARI_KULIAH as $h):
                      $items = array_values(array_filter($rows, function ($r) use ($h) { return $r['hari'] === $h; }));
                      $rooms = array_values(array_unique(array_column($items, 'ruang'))); ?>
                    <tr>
                      <td><strong><?= e($h) ?></strong></td>
                      <td><?= count($items) ?> sesi</td>
                      <td>
                        <?php if ($rooms): foreach ($rooms as $room): ?>
                          <span class="room-pill"><?= e($room) ?></span>
                        <?php endforeach; else: ?>—<?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </section>
<?php include __DIR__ . '/../includes/layout_bottom.php'; ?>
