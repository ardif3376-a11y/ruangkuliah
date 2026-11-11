<?php
/**
 * Pintu masuk aplikasi: arahkan ke dashboard (sudah login) atau halaman masuk.
 */
require __DIR__ . '/includes/bootstrap.php';

redirect(current_user() ? 'dashboard.php' : 'login.php');
