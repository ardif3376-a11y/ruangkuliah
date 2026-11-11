<?php
/**
 * Layout halaman masuk/daftar (panel hijau di kiri, formulir di kanan).
 *
 * Variabel dari halaman: $auth_title, $auth_heading, $auth_text
 */
$appName      = (string) app_config('app_name');
$auth_title   = $auth_title ?? 'Masuk';
$auth_heading = $auth_heading ?? 'Jadwal kuliah, lebih mudah dilihat.';
$auth_text    = $auth_text ?? 'Temukan jadwal perkuliahan, kelas, dan ruangan dalam satu tampilan yang rapi.';

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
  <title><?= e($auth_title) ?> — <?= e($appName) ?></title>
  <link rel="icon" type="image/png" href="<?= e(asset('img/logo-uniska.png')) ?>">
  <link rel="stylesheet" href="<?= e(asset_v('css/app.css')) ?>">
</head>
<body class="auth-body">
  <section class="login-screen">
    <div class="login-story">
      <div class="brand">
        <span class="brand-tile"><img class="brand-logo" src="<?= e(asset('img/logo-uniska.png')) ?>" alt="Logo UNISKA"></span>
        <?= e($appName) ?>
      </div>
      <div class="story-copy">
        <div class="eyebrow">Sistem informasi akademik</div>
        <h1><?= e($auth_heading) ?></h1>
        <p><?= e($auth_text) ?></p>
      </div>
      <div class="story-foot">© <?= date('Y') ?> <?= e($appName) ?> · <?= e(app_config('campus_name')) ?></div>
    </div>

    <div class="login-form-wrap">
      <img class="watermark watermark-auth" src="<?= e(asset('img/logo-uniska.png')) ?>" alt="" aria-hidden="true">
