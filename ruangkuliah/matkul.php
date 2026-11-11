<?php
/**
 * Daftar mata kuliah beserta dosen pengampu dan sesi jadwalnya.
 * Dosen dapat memfilter "Diampu saya" dan mengubah jadwal mata kuliahnya.
 */
require __DIR__ . '/includes/bootstrap.php';
$user = require_login();

$isDosen = $user['role'] === 'dosen';
$isAdmin = $user['role'] === 'admin';

$tampil = ($isDosen && query('tampil') === 'saya') ? 'saya' : 'semua';
$list   = q_matkul($tampil === 'saya' ? $user['id'] : null);

$page_title = 'Mata kuliah';
$page_key   = 'matkul';
include __DIR__ . '/includes/layout_top.php';
?>
        <section class="page active" id="coursesPage">
          <div class="page-heading">
            <div><h1>Mata kuliah</h1><p class="subtitle">Daftar mata kuliah pada jadwal yang diberikan, lengkap dengan dosen pengampu.</p></div>
            <?php if ($isAdmin): ?>
              <a class="btn" href="<?= e(url('admin/pengampu.php')) ?>"><?= icon('user-plus') ?>Atur dosen pengampu</a>
            <?php endif; ?>
          </div>

          <?php if ($isDosen): ?>
            <div class="week-tabs filter-tabs">
              <a class="day-tab <?= $tampil === 'semua' ? 'active' : '' ?>" href="<?= e(url('matkul.php')) ?>">Semua mata kuliah</a>
              <a class="day-tab <?= $tampil === 'saya' ? 'active' : '' ?>" href="<?= e(url('matkul.php?tampil=saya')) ?>">Diampu saya</a>
            </div>
          <?php endif; ?>

          <?php if (!$list): ?>
            <div class="panel"><p class="empty-note">
              <?= $tampil === 'saya'
                  ? 'Anda belum ditetapkan sebagai pengampu mata kuliah apa pun. Hubungi super admin.'
                  : 'Belum ada mata kuliah.' ?>
            </p></div>
          <?php endif; ?>

          <div class="course-grid" id="courseGrid">
            <?php foreach ($list as $i => $m):
                $mine = $isDosen && (int) $m['dosen_id'] === $user['id']; ?>
              <article class="course-card <?= $mine ? 'is-mine' : '' ?>">
                <div class="course-card-top">
                  <div>
                    <h3><?= e($m['nama']) ?></h3>
                    <div class="course-dosen">
                      <?php if ($m['dosen']): ?>
                        Dosen: <strong><?= e($m['dosen']) ?></strong>
                        <?php if ($mine): ?> <span class="badge blue">Diampu Anda</span><?php endif; ?>
                      <?php else: ?>
                        <span class="muted">Dosen belum ditentukan</span>
                      <?php endif; ?>
                    </div>
                  </div>
                  <span class="course-code"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
                </div>

                <div class="session-list">
                  <?php foreach ($m['sesi'] as $s): ?>
                    <div class="session-line">
                      <p>
                        <?= e($s['hari']) ?>, <?= e(format_rentang($s['jam_mulai'], $s['jam_selesai'])) ?>
                        <?php if (jadwal_baru_diubah($s)): ?><span class="badge orange">Diubah</span><?php endif; ?>
                        <br>Kelas <?= e($s['kelas']) ?> · Ruang <?= e($s['ruang']) ?>
                      </p>
                      <?= can_edit_jadwal($user, $s) ? component_edit_button($s) : '' ?>
                    </div>
                  <?php endforeach; ?>
                  <?php if (!$m['sesi']): ?><p class="muted">Belum ada sesi jadwal.</p><?php endif; ?>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        </section>

        <?php if ($isDosen || $isAdmin) { component_jadwal_modal('matkul'); } ?>
<?php include __DIR__ . '/includes/layout_bottom.php'; ?>
