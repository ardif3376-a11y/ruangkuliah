<?php
/**
 * Administrasi › Data mahasiswa (khusus super admin).
 * Tambah / ubah / hapus mahasiswa dengan input nama + NPM.
 */
require __DIR__ . '/../includes/bootstrap.php';
$user = require_role(['admin']);

$list = q_mahasiswa_all();

$page_title = 'Data mahasiswa';
$crumb      = 'Administrasi · Data mahasiswa';
$page_key   = 'admin';
include __DIR__ . '/../includes/layout_top.php';
?>
        <section class="page active" id="mahasiswaPage">
          <div class="page-heading">
            <div><h1>Data mahasiswa</h1><p class="subtitle">Mahasiswa dapat mendaftar sendiri atau ditambahkan di sini dengan nama dan NPM.</p></div>
          </div>

          <?php component_admin_tabs('mahasiswa'); ?>

          <div class="panel add-panel">
            <div class="panel-head"><h2>Tambah mahasiswa</h2></div>
            <form class="add-form" method="post" action="<?= e(url('actions/pengguna.php')) ?>" autocomplete="off">
              <?= csrf_field() ?>
              <input type="hidden" name="role" value="mahasiswa">
              <input type="hidden" name="aksi" value="tambah">
              <div class="field">
                <label for="addNama">Nama mahasiswa</label>
                <input id="addNama" name="nama" type="text" required minlength="3" maxlength="120" placeholder="Nama lengkap" value="<?= e(old('nama')) ?>">
              </div>
              <div class="field">
                <label for="addIdent">NPM</label>
                <input id="addIdent" name="ident" type="text" required minlength="5" maxlength="30" placeholder="Nomor pokok mahasiswa" value="<?= e(old('ident')) ?>">
              </div>
              <button class="btn" type="submit"><?= icon('plus') ?>Tambah mahasiswa</button>
            </form>
          </div>

          <div class="schedule-shell">
            <div class="schedule-tools">
              <strong class="tools-title"><?= count($list) ?> mahasiswa terdaftar</strong>
              <input class="search" type="search" placeholder="Cari nama atau NPM..." data-filter="#mahasiswaRows">
            </div>
            <div class="table-wrap">
              <table>
                <thead>
                  <tr><th>Nama</th><th>NPM</th><th>Terdaftar</th><th class="col-action">Aksi</th></tr>
                </thead>
                <tbody id="mahasiswaRows">
                  <?php foreach ($list as $m): ?>
                    <tr>
                      <td><strong><?= e($m['nama']) ?></strong></td>
                      <td><span class="room-pill"><?= e($m['npm']) ?></span></td>
                      <td class="nowrap"><?= e(tanggal_id($m['created_at'], false)) ?></td>
                      <td class="col-action">
                        <div class="action-group">
                          <button type="button" class="btn small secondary" data-edit-user
                                  data-id="<?= (int) $m['id'] ?>" data-nama="<?= e($m['nama']) ?>" data-ident="<?= e($m['npm']) ?>"><?= icon('edit') ?>Ubah</button>
                          <form method="post" action="<?= e(url('actions/pengguna.php')) ?>"
                                data-confirm="Hapus mahasiswa <?= e($m['nama']) ?>? Akun tidak dapat masuk lagi setelah dihapus.">
                            <?= csrf_field() ?>
                            <input type="hidden" name="role" value="mahasiswa">
                            <input type="hidden" name="aksi" value="hapus">
                            <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
                            <button type="submit" class="btn small danger"><?= icon('trash') ?>Hapus</button>
                          </form>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                  <tr class="filter-empty" hidden><td colspan="4" class="empty-cell">Tidak ada mahasiswa yang cocok.</td></tr>
                  <?php if (!$list): ?>
                    <tr><td colspan="4" class="empty-cell">Belum ada mahasiswa. Tambahkan lewat formulir di atas, atau minta mahasiswa mendaftar sendiri.</td></tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </section>

        <?php component_user_modal('mahasiswa'); ?>
<?php include __DIR__ . '/../includes/layout_bottom.php'; ?>
