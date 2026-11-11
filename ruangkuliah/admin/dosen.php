<?php
/**
 * Administrasi › Data dosen (khusus super admin).
 * Tambah / ubah / hapus dosen dengan input nama + NIP.
 */
require __DIR__ . '/../includes/bootstrap.php';
$user = require_role(['admin']);

$list = q_dosen_all();

$page_title = 'Data dosen';
$crumb      = 'Administrasi · Data dosen';
$page_key   = 'admin';
include __DIR__ . '/../includes/layout_top.php';
?>
        <section class="page active" id="dosenPage">
          <div class="page-heading">
            <div><h1>Data dosen</h1><p class="subtitle">Tambahkan dosen dengan nama dan NIP. Dosen masuk ke sistem memakai nama dan NIP tersebut.</p></div>
          </div>

          <?php component_admin_tabs('dosen'); ?>

          <div class="panel add-panel">
            <div class="panel-head"><h2>Tambah dosen</h2></div>
            <form class="add-form" method="post" action="<?= e(url('actions/pengguna.php')) ?>" autocomplete="off">
              <?= csrf_field() ?>
              <input type="hidden" name="role" value="dosen">
              <input type="hidden" name="aksi" value="tambah">
              <div class="field">
                <label for="addNama">Nama dosen</label>
                <input id="addNama" name="nama" type="text" required minlength="3" maxlength="120" placeholder="Nama beserta gelar" value="<?= e(old('nama')) ?>">
              </div>
              <div class="field">
                <label for="addIdent">NIP</label>
                <input id="addIdent" name="ident" type="text" required minlength="5" maxlength="30" placeholder="Nomor induk pegawai" value="<?= e(old('ident')) ?>">
              </div>
              <button class="btn" type="submit"><?= icon('plus') ?>Tambah dosen</button>
            </form>
          </div>

          <div class="schedule-shell">
            <div class="schedule-tools">
              <strong class="tools-title"><?= count($list) ?> dosen terdaftar</strong>
              <input class="search" type="search" placeholder="Cari nama atau NIP..." data-filter="#dosenRows">
            </div>
            <div class="table-wrap">
              <table>
                <thead>
                  <tr><th>Nama</th><th>NIP</th><th>Mata kuliah diampu</th><th class="col-action">Aksi</th></tr>
                </thead>
                <tbody id="dosenRows">
                  <?php foreach ($list as $d): $mk = $d['matkul'] !== null ? explode('||', $d['matkul']) : []; ?>
                    <tr>
                      <td><strong><?= e($d['nama']) ?></strong></td>
                      <td><span class="room-pill"><?= e($d['nip']) ?></span></td>
                      <td>
                        <?php if ($mk): foreach ($mk as $name): ?>
                          <span class="chip"><?= e($name) ?></span>
                        <?php endforeach; else: ?><span class="muted">Belum mengampu mata kuliah</span><?php endif; ?>
                      </td>
                      <td class="col-action">
                        <div class="action-group">
                          <button type="button" class="btn small secondary" data-edit-user
                                  data-id="<?= (int) $d['id'] ?>" data-nama="<?= e($d['nama']) ?>" data-ident="<?= e($d['nip']) ?>"><?= icon('edit') ?>Ubah</button>
                          <form method="post" action="<?= e(url('actions/pengguna.php')) ?>"
                                data-confirm="Hapus dosen <?= e($d['nama']) ?>? Mata kuliah yang diampu akan kembali tanpa dosen.">
                            <?= csrf_field() ?>
                            <input type="hidden" name="role" value="dosen">
                            <input type="hidden" name="aksi" value="hapus">
                            <input type="hidden" name="id" value="<?= (int) $d['id'] ?>">
                            <button type="submit" class="btn small danger"><?= icon('trash') ?>Hapus</button>
                          </form>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                  <tr class="filter-empty" hidden><td colspan="4" class="empty-cell">Tidak ada dosen yang cocok.</td></tr>
                  <?php if (!$list): ?>
                    <tr><td colspan="4" class="empty-cell">Belum ada dosen. Tambahkan dosen pertama lewat formulir di atas.</td></tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </section>

        <?php component_user_modal('dosen'); ?>
<?php include __DIR__ . '/../includes/layout_bottom.php'; ?>
