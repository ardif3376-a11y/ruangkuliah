<?php
/**
 * Koneksi database (PDO).
 *
 * Nilai bawaan cocok untuk XAMPP:
 *   host     : localhost
 *   user     : root
 *   password : (kosong)
 *   database : ruangkuliah
 */
$dbConfig = [
    'host'     => '127.0.0.1',
    'port'     => 3306,
    'name'     => 'ruangkuliah',
    'user'     => 'root',
    'pass'     => '',
    'charset'  => 'utf8mb4',
    'timezone' => '+07:00', // WIB, samakan dengan timezone aplikasi
];

function db(): PDO
{
    static $pdo = null;
    global $dbConfig;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $dbConfig['host'], $dbConfig['port'], $dbConfig['name'], $dbConfig['charset']
    );

    try {
        $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        $pdo->exec("SET time_zone = '" . $dbConfig['timezone'] . "'");
    } catch (PDOException $e) {
        http_response_code(500);
        echo '<!DOCTYPE html><meta charset="utf-8"><title>Database tidak terhubung</title>'
           . '<body style="font-family:sans-serif;max-width:560px;margin:60px auto;line-height:1.6">'
           . '<h2>Database belum terhubung</h2>'
           . '<p>Pastikan <strong>MySQL</strong> sudah berjalan di XAMPP dan file '
           . '<code>database/ruangkuliah.sql</code> sudah diimport lewat phpMyAdmin.</p>'
           . '<p>Pengaturan koneksi ada di <code>config/database.php</code>.</p></body>';
        exit;
    }

    return $pdo;
}
