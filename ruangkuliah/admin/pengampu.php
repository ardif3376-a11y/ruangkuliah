<?php
/**
 * Administrasi › Dosen pengampu (khusus super admin).
 * Memasangkan / mengganti dosen pada setiap mata kuliah.
 */
require __DIR__ . '/../includes/bootstrap.php';
$user = require_role(['admin']);

$matkul = q_matkul();
$dosen  = q_dosen_options();

$page_title = 'Dosen pengampu';
$crumb      = 'Administrasi · Dosen pengampu';
$page_key   = 'admin';
include __DIR__ . '/../includes/layout_top.php';
?>
        <section class="page active" id="pengampuPage">
          <div class="page-heading">
            <div><h1>Dosen pengampu</h1><p class="subtitle">Pilih dosen untuk setiap mata kuliah. Dosen hanya dapat mengubah jadwal mata kuliah yang diampunya.</p></div>
          </div>

          <?php component_admin_tabs('pengampu'); ?>

          <?php if (!$dosen): ?>
            <div class="alert info" role="status"><?= icon('info') ?><span>Belum ada dosen terdaftar. <a href="<?= e(url('admin/dosen.php')) ?>">Tambahkan data dosen</a> terlebih dahulu, lalu kembali ke sini.</span></div>
          <?php endif; ?>

          <div class="schedule-shell">
            <div class="table-wrap">
              <table>
                <thead>
                  <tr>
                    <th>Mata kuliah</th>
                    <th>Jadwal</th>
                    <th>Dosen pengampu</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($matkul as $m): ?>
                    <tr>
                      <td>
                        <strong><?= e($m['nama']) ?></strong>
                        <div class="cell-sub">
                          <?php if ($m['sesi']): ?>
                            Kelas <?= e(implode(', ', array_unique(array_column($m['sesi'], 'kelas')))) ?>
                          <?php endif; ?>
                        </div>
                      </td>
                      <td class="nowrap">
                        <?php foreach ($m['sesi'] as $s): ?>
                          <div><?= e($s['hari']) ?>, <?= e(format_rentang($s['jam_mulai'], $s['jam_selesai'])) ?></div>
                        <?php endforeach; ?>
                      </td>
                      <td class="col-assign">
                        <form class="inline-form" method="post" action="<?= e(url('actions/pengampu_update.php')) ?>">
                          <?= csrf_field() ?>
                          <input type="hidden" name="mata_kuliah_id" value="<?= (int) $m['id'] ?>">
                          <select name="dosen_id" aria-label="Dosen pengampu <?= e($m['nama']) ?>">
                            <option value="">— Belum ditentukan —</option>
                            <?php foreach ($dosen as $d): ?>
                              <option value="<?= (int) $d['id'] ?>" <?= (int) $m['dosen_id'] === (int) $d['id'] ? 'selected' : '' ?>>
                                <?= e($d['nama']) ?> (<?= e($d['nip']) ?>)
                              </option>
                            <?php endforeach; ?>
                          </select>
                          <button class="btn small" type="submit">Simpan</button>
                        </form>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <div class="course-count"><?= count($matkul) ?> mata kuliah · <?= count(array_filter($matkul, function ($m) { return $m['dosen_id'] === null; })) ?> belum memiliki dosen</div>
          </div>
        </section>
<?php include __DIR__ . '/../includes/layout_bottom.php'; ?>
