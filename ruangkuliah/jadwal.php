<?php
/**
 * Jadwal kuliah (semua peran).
 *   - mahasiswa : hanya melihat
 *   - dosen     : dapat mengubah hari & jam untuk mata kuliah yang diampu
 *   - admin     : dapat mengubah semua jadwal
 */
require __DIR__ . '/includes/bootstrap.php';
$user = require_login();

$rows     = q_jadwal();
$canEdit  = in_array($user['role'], ['dosen', 'admin'], true);
$isDosen  = $user['role'] === 'dosen';

$hari = query('hari', 'Semua');
if ($hari !== 'Semua' && !in_array($hari, HARI_KULIAH, true)) {
    $hari = 'Semua';
}
$autoPrint = query('cetak') === '1';
if ($autoPrint) {
    $hari = 'Semua';
}

$page_title = 'Jadwal kuliah';
$page_key   = 'jadwal';
include __DIR__ . '/includes/layout_top.php';
?>
        <section class="page active" id="schedulePage"
                 data-day="<?= e($hari) ?>" data-autoprint="<?= $autoPrint ? '1' : '0' ?>">
          <div class="print-only">
            <strong>Jadwal Kuliah Kelas <?= e(app_config('kelas_aktif')) ?> · Semester <?= (int) app_config('semester') ?></strong>
            <?= e(app_config('campus_name')) ?> · Tahun akademik <?= e(app_config('tahun_akademik')) ?>
          </div>

          <div class="page-heading">
            <div><h1>Jadwal kuliah</h1><p class="subtitle">Jadwal mingguan berdasarkan mata kuliah, kelas, ruangan, dan jam.</p></div>
            <button class="btn" id="printSchedule" type="button">
              <?= icon('print') ?>
              Cetak
            </button>
          </div>

          <?php if ($isDosen): ?>
            <div class="alert info" role="status"><?= icon('info') ?><span>Anda dapat mengubah hari dan jam pada mata kuliah yang Anda ampu (ditandai tombol <strong>Ubah</strong>).</span></div>
          <?php elseif ($user['role'] === 'admin'): ?>
            <div class="alert info" role="status"><?= icon('info') ?><span>Sebagai super admin, Anda dapat mengubah hari dan jam semua sesi. Untuk memasang dosen pengampu, buka <a href="<?= e(url('admin/pengampu.php')) ?>">Administrasi › Dosen pengampu</a>.</span></div>
          <?php endif; ?>

          <div class="schedule-shell">
            <div class="schedule-tools">
              <div class="week-tabs" id="dayTabs">
                <?php foreach (array_merge(['Semua'], HARI_KULIAH) as $d): ?>
                  <button class="day-tab <?= $hari === $d ? 'active' : '' ?>" data-day="<?= e($d) ?>" type="button"><?= e($d) ?></button>
                <?php endforeach; ?>
              </div>
              <div class="tools-right">
                <?php if ($isDosen): ?>
                  <label class="toggle"><input type="checkbox" id="onlyMine"> Hanya jadwal saya</label>
                <?php endif; ?>
                <input class="search" id="scheduleSearch" type="search" placeholder="Cari mata kuliah, kelas, atau ruangan...">
              </div>
            </div>

            <div class="table-wrap">
              <table>
                <thead>
                  <tr>
                    <th>Hari</th>
                    <th>Mata kuliah</th>
                    <th>Kelas</th>
                    <th>Ruangan</th>
                    <th>Jam</th>
                    <?php if ($canEdit): ?><th class="col-action">Aksi</th><?php endif; ?>
                  </tr>
                </thead>
                <tbody id="scheduleRows">
                  <?php foreach ($rows as $r):
                      $mine = $isDosen && can_edit_jadwal($user, $r);
                      $search = mb_strtolower(implode(' ', [
                          $r['hari'], $r['matkul'], $r['kelas'], $r['ruang'],
                          format_rentang($r['jam_mulai'], $r['jam_selesai']), (string) $r['dosen'],
                      ])); ?>
                    <tr data-hari="<?= e($r['hari']) ?>" data-search="<?= e($search) ?>" data-mine="<?= $mine ? '1' : '0' ?>">
                      <td class="day-cell"><?= e($r['hari']) ?></td>
                      <td>
                        <strong><?= e($r['matkul']) ?></strong>
                        <?php if (jadwal_baru_diubah($r)): ?> <span class="badge orange" title="Diubah <?= e(time_ago($r['diubah_pada'])) ?>">Diubah</span><?php endif; ?>
                        <?php if ($mine): ?> <span class="badge blue">Anda</span><?php endif; ?>
                        <div class="cell-sub"><?= $r['dosen'] ? 'Dosen: ' . e($r['dosen']) : 'Dosen belum ditentukan' ?></div>
                      </td>
                      <td><span class="class-pill"><?= e($r['kelas']) ?></span></td>
                      <td><span class="room-pill"><?= e($r['ruang']) ?></span></td>
                      <td class="nowrap"><?= e(format_rentang($r['jam_mulai'], $r['jam_selesai'])) ?></td>
                      <?php if ($canEdit): ?>
                        <td class="col-action"><?= can_edit_jadwal($user, $r) ? component_edit_button($r) : '' ?></td>
                      <?php endif; ?>
                    </tr>
                  <?php endforeach; ?>
                  <tr id="emptyRow" <?= $rows ? 'hidden' : '' ?>>
                    <td colspan="<?= $canEdit ? 6 : 5 ?>" class="empty-cell">Tidak ada jadwal yang cocok.</td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div class="course-count" id="scheduleCount"></div>
          </div>
        </section>

        <?php if ($canEdit) { component_jadwal_modal('jadwal'); } ?>
<?php include __DIR__ . '/includes/layout_bottom.php'; ?>
