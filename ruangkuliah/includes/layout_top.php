<?php
/**
 * Layout utama aplikasi (sidebar + topbar).
 *
 * Variabel yang diisi halaman sebelum meng-include file ini:
 *   $page_title : judul halaman (tab browser & breadcrumb)
 *   $page_key   : kunci menu aktif (dashboard|jadwal|matkul|admin|notifikasi)
 *
 * Halaman yang meng-include file ini otomatis wajib login.
 */
$user       = require_login();
$page_title = $page_title ?? 'Ringkasan';
$page_key   = $page_key ?? '';
$appName    = (string) app_config('app_name');
$unread     = q_notif_unread_count($user);

$nav = [
    ['key' => 'dashboard',  'label' => 'Ringkasan',     'href' => 'dashboard.php',  'icon' => 'grid'],
    ['key' => 'jadwal',     'label' => 'Jadwal kuliah', 'href' => 'jadwal.php',     'icon' => 'calendar'],
    ['key' => 'matkul',     'label' => 'Mata kuliah',   'href' => 'matkul.php',     'icon' => 'book'],
];
if ($user['role'] === 'admin') {
    $nav[] = ['key' => 'admin', 'label' => 'Administrasi', 'href' => 'admin/index.php', 'icon' => 'users'];
}
$nav[] = ['key' => 'notifikasi', 'label' => 'Notifikasi', 'href' => 'notifikasi.php', 'icon' => 'bell', 'badge' => $unread];

$identLabel = ['mahasiswa' => 'NPM ', 'dosen' => 'NIP ', 'admin' => 'Username '][$user['role']];

// Pisahkan flash: error tampil sebagai kotak peringatan, lainnya sebagai toast
$alerts = [];
$toastMessage = '';
foreach (flash_pull() as $f) {
    if ($f['type'] === 'error') {
        $alerts[] = $f['message'];
    } else {
        $toastMessage = trim($toastMessage . ' ' . $f['message']);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="theme-color" content="#f5f4f0">
  <title><?= e($page_title) ?> — <?= e($appName) ?></title>
  <link rel="icon" type="image/png" href="<?= e(asset('img/logo-uniska.png')) ?>">
  <link rel="stylesheet" href="<?= e(asset_v('css/app.css')) ?>">
</head>
<body data-base="<?= e(base_url()) ?>">
  <img class="watermark" src="<?= e(asset('img/logo-uniska.png')) ?>" alt="" aria-hidden="true">

  <div class="app">
    <aside class="sidebar">
      <a class="brand" href="<?= e(url('dashboard.php')) ?>">
        <img class="brand-logo" src="<?= e(asset('img/logo-uniska.png')) ?>" alt="Logo UNISKA">
        <?= e($appName) ?>
      </a>
      <div class="nav-label">Akademik</div>

      <nav class="nav" aria-label="Navigasi utama">
        <?php foreach ($nav as $item): ?>
          <a class="nav-btn <?= $page_key === $item['key'] ? 'active' : '' ?>" href="<?= e(url($item['href'])) ?>"
             <?= $page_key === $item['key'] ? 'aria-current="page"' : '' ?>>
            <?= icon($item['icon']) ?>
            <span><?= e($item['label']) ?></span>
            <?php if (!empty($item['badge'])): ?>
              <span class="nav-badge" title="<?= (int) $item['badge'] ?> notifikasi baru"><?= (int) $item['badge'] > 9 ? '9+' : (int) $item['badge'] ?></span>
            <?php endif; ?>
          </a>
        <?php endforeach; ?>

        <form method="post" action="<?= e(url('logout.php')) ?>" class="nav-form">
          <?= csrf_field() ?>
          <button class="nav-btn" type="submit">
            <?= icon('logout') ?>
            <span>Keluar</span>
          </button>
        </form>
      </nav>

      <div class="sidebar-bottom">
        <div class="semester-card">
          <small>Tahun akademik <?= e(app_config('tahun_akademik')) ?></small>
          <strong>Semester <?= (int) app_config('semester') ?> · Kelas <?= e(app_config('kelas_aktif')) ?></strong>
          <div class="semester-line" title="<?= semester_progress() ?>% semester berjalan"><span style="width: <?= semester_progress() ?>%"></span></div>
        </div>
        <div class="profile">
          <div class="avatar"><?= e(initials($user['nama'])) ?></div>
          <div class="profile-text">
            <strong><?= e($user['nama']) ?></strong>
            <small><?= $user['role'] !== 'admin' ? e(ROLE_LABEL[$user['role']]) . ' · ' : '' ?><?= e($identLabel . $user['ident']) ?></small>
          </div>
        </div>
      </div>
    </aside>

    <main class="main">
      <header class="topbar">
        <div class="breadcrumb">Akademik <span aria-hidden="true">/</span> <strong><?= e($crumb ?? $page_title) ?></strong></div>
        <div class="top-right">
          <span class="today-label"><?= e(hari_id_dari_tanggal() . ', ' . tanggal_id(null, false)) ?></span>
          <div class="avatar" title="<?= e($user['nama']) ?>"><?= e(initials($user['nama'])) ?></div>
        </div>
      </header>

      <div class="content">
        <?php foreach ($alerts as $msg): ?>
          <div class="alert error" role="alert"><?= icon('alert') ?><span><?= e($msg) ?></span></div>
        <?php endforeach; ?>
