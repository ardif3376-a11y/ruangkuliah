<?php
/**
 * Keluar dari akun. Hanya menerima POST (dengan token CSRF).
 */
require __DIR__ . '/includes/bootstrap.php';

require_post('dashboard.php');
csrf_check('dashboard.php');

logout_user();
session_start();                       // sesi baru hanya untuk pesan flash
flash('success', 'Anda telah keluar.');
redirect('login.php');
