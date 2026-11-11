/* RuangKuliah — interaksi sisi browser.
   Halaman tetap berfungsi tanpa JS untuk melihat data; JS menambahkan
   filter instan, modal, konfirmasi, toast, dan cetak. */
(function () {
  'use strict';

  var $ = function (sel, root) { return (root || document).querySelector(sel); };
  var $$ = function (sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); };

  /* ------------------------------------------------------------- Toast */
  var toast = $('#toast');
  var toastTimer;
  function showToast(message) {
    if (!toast || !message) return;
    toast.textContent = message;
    toast.classList.add('show');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(function () { toast.classList.remove('show'); }, 4200);
  }
  if (toast && toast.dataset.message) showToast(toast.dataset.message);

  /* ------------------------------------------- Jadwal: filter & pencarian */
  var schedule = $('#schedulePage');
  if (schedule) {
    var rows = $$('#scheduleRows tr[data-hari]');
    var emptyRow = $('#emptyRow');
    var countEl = $('#scheduleCount');
    var search = $('#scheduleSearch');
    var onlyMine = $('#onlyMine');
    var tabs = $$('#dayTabs .day-tab');
    var selectedDay = schedule.dataset.day || 'Semua';

    var applyFilter = function () {
      var q = (search ? search.value : '').trim().toLowerCase();
      var mine = onlyMine && onlyMine.checked;
      var visible = 0;

      rows.forEach(function (tr) {
        var ok = (selectedDay === 'Semua' || tr.dataset.hari === selectedDay) &&
                 (!mine || tr.dataset.mine === '1') &&
                 tr.dataset.search.indexOf(q) !== -1;
        tr.hidden = !ok;
        if (ok) visible++;
      });

      if (emptyRow) emptyRow.hidden = visible !== 0;
      if (countEl) {
        countEl.textContent = visible + ' sesi perkuliahan' + (selectedDay !== 'Semua' ? ' · ' + selectedDay : '');
      }
      tabs.forEach(function (b) { b.classList.toggle('active', b.dataset.day === selectedDay); });
    };

    tabs.forEach(function (b) {
      b.addEventListener('click', function () { selectedDay = b.dataset.day; applyFilter(); });
    });
    if (search) search.addEventListener('input', applyFilter);
    if (onlyMine) onlyMine.addEventListener('change', applyFilter);
    applyFilter();

    var printBtn = $('#printSchedule');
    if (printBtn) printBtn.addEventListener('click', function () { window.print(); });

    // Datang dari tombol "Cetak jadwal" di dashboard
    if (schedule.dataset.autoprint === '1') {
      setTimeout(function () { window.print(); }, 250);
    }
  }

  /* ------------------------------------------------------------ Modal */
  function openModal(dialog) {
    if (!dialog) return;
    if (typeof dialog.showModal === 'function') dialog.showModal();
    else dialog.setAttribute('open', '');
  }
  function closeModal(dialog) {
    if (!dialog) return;
    if (typeof dialog.close === 'function') dialog.close();
    else dialog.removeAttribute('open');
  }

  $$('dialog.modal').forEach(function (dlg) {
    // klik di luar kotak (area backdrop) menutup modal
    dlg.addEventListener('click', function (ev) {
      if (ev.target === dlg) closeModal(dlg);
    });
    $$('[data-close-modal]', dlg).forEach(function (b) {
      b.addEventListener('click', function () { closeModal(dlg); });
    });
  });

  // Modal ubah jadwal
  var jadwalModal = $('#jadwalModal');
  $$('[data-edit-jadwal]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      $('#jmId').value = btn.dataset.id;
      $('#jmMatkul').textContent = btn.dataset.matkul;
      $('#jmInfo').textContent = btn.dataset.info;
      $('#jmHari').value = btn.dataset.hari;
      $('#jmMulai').value = btn.dataset.mulai;
      $('#jmSelesai').value = btn.dataset.selesai;
      openModal(jadwalModal);
    });
  });

  // Modal ubah dosen / mahasiswa
  var userModal = $('#userModal');
  $$('[data-edit-user]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      $('#umId').value = btn.dataset.id;
      $('#umNama').value = btn.dataset.nama;
      $('#umIdent').value = btn.dataset.ident;
      openModal(userModal);
    });
  });

  /* -------------------------------------------- Konfirmasi hapus (form) */
  $$('form[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (ev) {
      if (!window.confirm(form.dataset.confirm)) ev.preventDefault();
    });
  });

  /* ------------------------------------- Filter tabel umum (data-filter) */
  $$('input[data-filter]').forEach(function (input) {
    var body = $(input.dataset.filter);
    if (!body) return;
    var emptyMsg = $('.filter-empty', body);
    input.addEventListener('input', function () {
      var q = input.value.trim().toLowerCase();
      var visible = 0;
      $$('tr', body).forEach(function (tr) {
        if (tr.classList.contains('filter-empty')) return;
        var ok = tr.textContent.toLowerCase().indexOf(q) !== -1;
        tr.hidden = !ok;
        if (ok) visible++;
      });
      if (emptyMsg) emptyMsg.hidden = visible !== 0;
    });
  });
})();
