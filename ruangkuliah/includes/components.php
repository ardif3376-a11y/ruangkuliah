<?php
/**
 * Komponen tampilan yang dipakai di beberapa halaman.
 */

/** Tombol "Ubah" yang membuka modal ubah jadwal (diisi lewat data-attribute). */
function component_edit_button(array $row, string $label = 'Ubah'): string
{
    return '<button type="button" class="btn small secondary" data-edit-jadwal'
        . ' data-id="' . (int) $row['id'] . '"'
        . ' data-matkul="' . e($row['matkul']) . '"'
        . ' data-info="' . e('Kelas ' . $row['kelas'] . ' · Ruang ' . $row['ruang']) . '"'
        . ' data-hari="' . e($row['hari']) . '"'
        . ' data-mulai="' . e(jam_input($row['jam_mulai'])) . '"'
        . ' data-selesai="' . e(jam_input($row['jam_selesai'])) . '">'
        . icon('edit') . e($label) . '</button>';
}

/**
 * Modal ubah jadwal. Dipasang sekali per halaman yang punya tombol ubah.
 * $returnKey: jadwal|matkul|dashboard (tujuan kembali setelah simpan)
 */
function component_jadwal_modal(string $returnKey): void
{
    ?>
    <dialog class="modal" id="jadwalModal" aria-labelledby="jadwalModalTitle">
      <form method="post" action="<?= e(url('actions/jadwal_update.php')) ?>" class="modal-form">
        <?= csrf_field() ?>
        <input type="hidden" name="jadwal_id" id="jmId" value="">
        <input type="hidden" name="return" value="<?= e($returnKey) ?>">

        <div class="modal-head">
          <div>
            <h2 id="jadwalModalTitle">Ubah jadwal</h2>
            <p class="subtitle"><strong id="jmMatkul"></strong><br><span id="jmInfo"></span></p>
          </div>
          <button type="button" class="icon-btn" data-close-modal aria-label="Tutup"><?= icon('x') ?></button>
        </div>

        <div class="field">
          <label for="jmHari">Hari</label>
          <select name="hari" id="jmHari" required>
            <?php foreach (HARI_KULIAH as $h): ?>
              <option value="<?= e($h) ?>"><?= e($h) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-row">
          <div class="field">
            <label for="jmMulai">Jam mulai</label>
            <input type="time" name="jam_mulai" id="jmMulai" required>
          </div>
          <div class="field">
            <label for="jmSelesai">Jam selesai</label>
            <input type="time" name="jam_selesai" id="jmSelesai" required>
          </div>
        </div>

        <p class="modal-note"><?= icon('bell') ?>Perubahan langsung tampil di jadwal dan diumumkan ke semua pengguna lewat notifikasi.</p>

        <div class="modal-actions">
          <button type="button" class="btn secondary" data-close-modal>Batal</button>
          <button type="submit" class="btn">Simpan jadwal</button>
        </div>
      </form>
    </dialog>
    <?php
}

/** Tampilan "jadwal lama → jadwal baru". */
function component_perubahan(array $n): string
{
    return '<div class="change">'
        . '<span class="change-old">' . e(notif_lama($n)) . '</span>'
        . '<span class="change-arrow">' . icon('arrow') . '</span>'
        . '<span class="change-new">' . e(notif_baru($n)) . '</span>'
        . '</div>';
}

/** Tab navigasi di area Administrasi (hanya super admin). */
function component_admin_tabs(string $active): void
{
    $tabs = [
        'index'     => ['Ringkasan',       'admin/index.php'],
        'pengampu'  => ['Dosen pengampu',  'admin/pengampu.php'],
        'dosen'     => ['Data dosen',      'admin/dosen.php'],
        'mahasiswa' => ['Data mahasiswa',  'admin/mahasiswa.php'],
    ];
    echo '<div class="week-tabs filter-tabs" role="tablist">';
    foreach ($tabs as $key => [$label, $href]) {
        echo '<a class="day-tab' . ($key === $active ? ' active' : '') . '" href="' . e(url($href)) . '">' . e($label) . '</a>';
    }
    echo '</div>';
}

/**
 * Modal ubah data dosen/mahasiswa.
 * $role: dosen|mahasiswa  -> label identitas NIP/NPM
 */
function component_user_modal(string $role): void
{
    $label = $role === 'dosen' ? 'NIP' : 'NPM';
    $title = $role === 'dosen' ? 'Ubah data dosen' : 'Ubah data mahasiswa';
    ?>
    <dialog class="modal" id="userModal" aria-labelledby="userModalTitle">
      <form method="post" action="<?= e(url('actions/pengguna.php')) ?>" class="modal-form">
        <?= csrf_field() ?>
        <input type="hidden" name="role" value="<?= e($role) ?>">
        <input type="hidden" name="aksi" value="ubah">
        <input type="hidden" name="id" id="umId" value="">

        <div class="modal-head">
          <div><h2 id="userModalTitle"><?= e($title) ?></h2><p class="subtitle">Perbarui nama dan <?= e($label) ?>.</p></div>
          <button type="button" class="icon-btn" data-close-modal aria-label="Tutup"><?= icon('x') ?></button>
        </div>

        <div class="field">
          <label for="umNama">Nama lengkap</label>
          <input type="text" name="nama" id="umNama" required minlength="3" maxlength="120">
        </div>
        <div class="field">
          <label for="umIdent"><?= e($label) ?></label>
          <input type="text" name="ident" id="umIdent" required minlength="5" maxlength="30">
        </div>

        <div class="modal-actions">
          <button type="button" class="btn secondary" data-close-modal>Batal</button>
          <button type="submit" class="btn">Simpan perubahan</button>
        </div>
      </form>
    </dialog>
    <?php
}
