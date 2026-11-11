# RuangKuliah — Sistem Jadwal Perkuliahan UNISKA

Aplikasi web jadwal kuliah (PHP + MySQL) dengan 3 peran: **Mahasiswa**, **Dosen**, dan **Super Admin**.
Tampilan mengikuti template `jadwal-kuliah.html`, ditambah logo UNISKA (kecil di kiri atas dan
watermark transparan di tengah halaman).

## Cara menjalankan di XAMPP

1. Salin folder `ruangkuliah` ke `C:\xampp\htdocs\` (macOS/Linux: `htdocs` XAMPP).
2. Buka **XAMPP Control Panel** → *Start* **Apache** dan **MySQL**.
3. Buka `http://localhost/phpmyadmin` → tab **Import** → pilih `database/ruangkuliah.sql` → **Import**.
   Database `ruangkuliah` dibuat otomatis, lengkap dengan jadwal Kelas A2 Semester 5 dan akun super admin.
4. Buka **http://localhost/ruangkuliah/**

Pengaturan database ada di `config/database.php` (bawaan XAMPP: user `root`, tanpa password).
Bila folder tidak bernama `ruangkuliah`, tidak perlu diubah — alamat dasar dideteksi otomatis.

Kebutuhan: PHP 7.4 atau lebih baru (XAMPP terbaru sudah memenuhi), ekstensi `pdo_mysql` dan `mbstring` (aktif bawaan).

## Akun dan cara masuk

| Peran | Kolom 1 | Kolom 2 | Asal akun |
|---|---|---|---|
| Super admin | username `admin` | kata sandi `admin123` | dummy bawaan SQL |
| Dosen | nama dosen | NIP | dibuat super admin |
| Mahasiswa | nama | NPM | daftar sendiri di `register.php` atau dibuat super admin |

Nama dosen/mahasiswa tidak peka huruf besar-kecil, tetapi harus sama persis dengan yang terdaftar
(termasuk gelar dosen).

## Alur penggunaan

1. **Super admin** masuk → **Administrasi › Data dosen** → tambah dosen (nama + NIP). Mahasiswa juga bisa ditambah di **Data mahasiswa**.
2. **Administrasi › Dosen pengampu** → pilih dosen untuk setiap mata kuliah → *Simpan*.
3. **Dosen** masuk → **Jadwal kuliah** atau **Mata kuliah** → tombol **Ubah** muncul hanya pada mata kuliah yang diampu → ganti hari/jam.
4. Perubahan otomatis menjadi **notifikasi** (contoh: *Rabu, 13.50 - 16.00 → Kamis, 09.00 - 11.00*) yang tampil di bar notifikasi dashboard dan halaman **Notifikasi** untuk semua pengguna.

## Hak akses

| Fitur | Mahasiswa | Dosen | Super admin |
|---|:--:|:--:|:--:|
| Lihat dashboard, jadwal, mata kuliah, notifikasi | ✓ | ✓ (dashboard: matkul yang diampu) | ✓ |
| Ubah hari & jam jadwal | – | hanya matkul yang diampu | semua |
| Pasang/ganti dosen pengampu | – | – | ✓ |
| Tambah/ubah/hapus dosen & mahasiswa | – | – | ✓ |

Pengecekan hak akses dilakukan di server, bukan hanya dengan menyembunyikan tombol.

## Aturan sistem

- Jam selesai harus lebih akhir dari jam mulai.
- Perubahan ditolak bila **ruangan** yang sama sudah dipakai sesi lain pada hari dan jam yang tumpang tindih.
- Perubahan/pemasangan ditolak bila **dosen yang sama** jadi mengajar dua sesi di waktu yang bersamaan.
- Sesi yang diubah dalam 7 hari terakhir diberi tanda **Diubah**.
- Menghapus dosen tidak menghapus mata kuliah; kolom dosen pengampu kembali "belum ditentukan".

## Fitur dari template yang dipertahankan

Halaman login split-layout, sidebar + bottom-nav mobile, 4 kartu statistik (kini dihitung dari database),
panel jadwal harian, pilih hari, tabel jadwal dengan tab hari dan pencarian, tombol cetak (CSS print),
kartu mata kuliah, ringkasan administrasi per hari, toast, dan kartu semester.

## Struktur folder

```
ruangkuliah/
├── index.php            Pintu masuk (arahkan ke login/dashboard)
├── login.php            Login 3 peran (tab: Mahasiswa | Dosen | Super admin)
├── register.php         Pendaftaran mahasiswa
├── logout.php           Keluar (POST + CSRF)
├── dashboard.php        Ringkasan + bar notifikasi
├── jadwal.php           Jadwal kuliah (filter, cari, cetak, ubah)
├── matkul.php           Mata kuliah + dosen pengampu
├── notifikasi.php       Riwayat pergantian jadwal
├── admin/               Area super admin
│   ├── index.php          Ringkasan administrasi
│   ├── pengampu.php       Pasang dosen ke mata kuliah
│   ├── dosen.php          Kelola dosen (nama + NIP)
│   └── mahasiswa.php      Kelola mahasiswa (nama + NPM)
├── actions/             Pemroses form (POST)
│   ├── jadwal_update.php  Ubah hari/jam + buat notifikasi
│   ├── pengampu_update.php
│   └── pengguna.php       Tambah/ubah/hapus dosen & mahasiswa
├── config/
│   ├── app.php            Nama aplikasi, semester, opsi umum
│   └── database.php       Koneksi MySQL (PDO)
├── includes/            Logika bersama
│   ├── bootstrap.php      Pemuat awal + sesi
│   ├── auth.php           Login, peran, pembatasan percobaan
│   ├── queries.php        Semua query database
│   ├── helpers.php        Escape, URL, CSRF, flash, format tanggal/jam
│   ├── components.php     Modal, tombol ubah, tab admin
│   ├── icons.php          Ikon SVG
│   └── layout_*.php, auth_*.php   Kerangka halaman
├── assets/
│   ├── css/app.css
│   ├── js/app.js
│   └── img/logo-uniska.png
└── database/ruangkuliah.sql   Skema + data awal (import lewat phpMyAdmin)
```

## Tabel database

`admin`, `dosen`, `mahasiswa`, `mata_kuliah` (punya `dosen_id`), `jadwal` (hari, jam, kelas, ruang, jenis),
`notifikasi` (jadwal lama → baru, siapa yang mengubah), `notifikasi_dibaca` (penanda lencana "baru").

## Pengaturan (`config/app.php`)

- `tahun_akademik`, `semester`, `kelas_aktif`, `semester_mulai/selesai` → kartu semester di sidebar.
- `show_demo_hint` → ubah ke `false` untuk menyembunyikan kotak akun demo di halaman login.
- `base_url` → isi manual (mis. `/ruangkuliah`) hanya bila deteksi otomatis tidak cocok.

## Catatan keamanan

- Sesuai permintaan, dosen dan mahasiswa masuk dengan **nama + NIP/NPM** tanpa kata sandi. Karena NIP/NPM
  berperan sebagai "kata sandi", keamanannya lebih lemah daripada sandi sungguhan. Untuk dipakai publik,
  pertimbangkan menambah kolom kata sandi.
- Kata sandi super admin disimpan sebagai hash bcrypt. **Ganti `admin123`** sebelum dipakai sungguhan:
  buat hash baru dengan `php -r "echo password_hash('SandiBaru', PASSWORD_DEFAULT);"` lalu perbarui
  kolom `password_hash` di tabel `admin`.
- Sudah ada: prepared statement (anti SQL injection), escape output (anti XSS), token CSRF di semua form,
  `session_regenerate_id` saat login, cookie sesi HttpOnly + SameSite, penguncian 60 detik setelah 5 kali gagal login,
  serta `.htaccess` yang memblokir akses langsung ke `config/`, `includes/`, dan `database/`.

## Pemecahan masalah

| Gejala | Solusi |
|---|---|
| "Database belum terhubung" | Pastikan MySQL menyala di XAMPP dan SQL sudah diimport; cek `config/database.php`. |
| Halaman tanpa gaya / logo hilang | Buka lewat `http://localhost/ruangkuliah/`, bukan membuka file langsung. Atur `base_url` bila perlu. |
| Font berbeda | Font Manrope & DM Sans dimuat dari Google Fonts; tanpa internet dipakai font bawaan. |
| Ingin mengulang dari awal | Import ulang `database/ruangkuliah.sql` (menghapus & membuat ulang semua tabel). |
# ruangkuliah
# ruangkuliah
