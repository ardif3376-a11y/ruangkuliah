<?php
/**
 * Riwayat notifikasi pergantian jadwal. Dapat dilihat oleh semua pengguna.
 */
require __DIR__ . '/includes/bootstrap.php';
$user = require_login();

$lastRead = q_notif_terakhir_dibaca($user);   // untuk menandai item "Baru"
$items    = q_notifikasi(100);
q_notif_mark_read($user);                      // setelah ini lencana di menu menjadi 0

$page_title = 'Notifikasi';
$page_key   = 'notifikasi';
include __DIR__ . '/includes/layout_top.php';
?>
        <section class="page active" id="notifPage">
          <div class="page-heading">
            <div><h1>Notifikasi</h1><p class="subtitle">Riwayat perubahan hari dan jam kuliah oleh dosen pengampu.</p></div>
          </div>

          <?php if (!$items): ?>
            <div class="panel"><p class="empty-note">Belum ada perubahan jadwal. Saat dosen mengganti hari atau jam kuliah, pemberitahuannya muncul di sini.</p></div>
          <?php else: ?>
            <div class="notif-list">
              <?php foreach ($items as $n): ?>
                <article class="notif-item <?= (int) $n['id'] > $lastRead ? 'is-new' : '' ?>">
                  <div class="notif-icon"><?= icon('clock') ?></div>
                  <div class="notif-main">
                    <div class="notif-title">
                      <strong><?= e($n['mata_kuliah']) ?></strong>
                      <span class="class-pill"><?= e($n['kelas']) ?></span>
                      <?php if ((int) $n['id'] > $lastRead): ?><span class="badge orange">Baru</span><?php endif; ?>
                    </div>
                    <p class="notif-text"><?= e(notif_kalimat($n)) ?></p>
                    <?= component_perubahan($n) ?>
                  </div>
                  <time class="notif-time" datetime="<?= e($n['created_at']) ?>">
                    <?= e(time_ago($n['created_at'])) ?>
                    <small><?= e(tanggal_id($n['created_at'])) ?></small>
                  </time>
                </article>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </section>
<?php include __DIR__ . '/includes/layout_bottom.php'; ?>
