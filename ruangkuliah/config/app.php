<?php
/**
 * Konfigurasi umum aplikasi RuangKuliah.
 * Sesuaikan nilai di bawah ini bila perlu.
 */
return [
    'app_name'        => 'Ruang Kuliah',
    'campus_name'     => 'Universitas Islam Kadiri',
    'timezone'        => 'Asia/Jakarta',

    // Informasi semester yang tampil di sidebar (kartu "Tahun akademik")
    'tahun_akademik'  => '2026/2027 Ganjil',
    'semester'        => 5,
    'kelas_aktif'     => 'A2',
    'semester_mulai'  => '2026-09-07',
    'semester_selesai'=> '2027-01-15',

    // Kosongkan (null) agar URL dasar dideteksi otomatis.
    // Isi manual bila perlu, contoh: '/ruangkuliah'
    'base_url'        => null,

    // Tampilkan kotak "Akun demo" di halaman login.
    // Ubah menjadi false saat aplikasi dipakai sungguhan.
    'show_demo_hint'  => true,

    // Batas percobaan login gagal sebelum dikunci sementara.
    'login_max_gagal' => 5,
    'login_kunci_detik' => 60,
];
