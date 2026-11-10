# 🌿 MentalCare - Platform Layanan Kesehatan Mental Terpadu

Website platform layanan kesehatan mental modern, responsif, dan mudah digunakan yang dirancang khusus untuk memberikan ruang yang tenang, aman, dan nyaman bagi pengguna. Dilengkapi dengan instrumen pemeriksaan mandiri (*mental health assessment*), sistem perhitungan skor dan rekomendasi otomatis, manajemen konsultasi bersama psikolog klinis berlisensi, serta dasbor analitik lengkap multi-role.

---

## 👥 3 Jenis Hak Akses (Multi-Role)

1. **Pasien (Pengguna):**
   - Registrasi lengkap (Nama, NIK/NIM, Email, No HP, Jenis Kelamin, Tanggal Lahir, Password).
   - Dashboard Pasien: Ringkasan status kesehatan mental terakhir, riwayat pemeriksaan, jadwal konsultasi mendatang, tombol tindakan cepat.
   - Pemeriksaan Mental (Kuisioner): Skrining Stres, Kecemasan (GAD-7), Depresi (PHQ-9), Burnout, dan Kesejahteraan Mental.
   - Pilihan jawaban skala 5 tingkat (*Tidak pernah*, *Jarang*, *Kadang-kadang*, *Sering*, *Sangat sering*).
   - Hasil Pemeriksaan: Tampilan kartu skor, kategori hasil (Normal, Ringan, Sedang, Tinggi), tingkat risiko, penafian medis resmi, serta rekomendasi gaya hidup dan relaksasi mandiri.
   - Konsultasi Psikolog: Melihat profil, spesialisasi, jam terbang, jadwal ketersediaan, serta mengajukan konsultasi (online/tatap muka) dengan opsi izin akses rekam skrining.
   - Riwayat & Detail: Membuka arsip hasil pemeriksaan lampau, jawaban butir kuisioner, serta catatan/rekomendasi pasca-konsultasi dari psikolog.
   - Profil: Mengubah data diri dan kata sandi.

2. **Psikolog (Konselor Klinis):**
   - Dashboard Psikolog: Statistik jumlah pasien, total konsultasi, permintaan menunggu, konsultasi hari ini, dan daftar pasien terbaru.
   - Manajemen Konsultasi: Menerima/menolak permintaan konsultasi, menyematkan tautan ruang temu online (*Google Meet/Zoom*), dan memperbarui status menjadi sedang berlangsung atau selesai.
   - Kelola Jadwal Praktik: Menambah slot ketersediaan hari/tanggal dan jam praktik yang dapat dipilih langsung oleh pasien.
   - Rekam Pasien & Privasi: Melihat riwayat pemeriksaan pasien **hanya jika pasien memberikan izin akses**. Jika izin tidak diberikan, data sensitif dilindungi secara ketat.
   - Catatan Konsultasi: Mencatat dinamika masalah/keluhan, hasil observasi klinis, rekomendasi psikologis, dan rencana tindak lanjut (*follow-up*).
   - Profil Profesional: Memperbarui nomor izin praktik (SIPP/STR), spesialisasi, latar belakang pendidikan, dan riwayat pengalaman.

3. **Administrator:**
   - Dashboard & Statistik: Visualisasi grafik interaktif (*Chart.js*) untuk tren pemeriksaan per kategori, demografi gender pasien, dan status konsultasi.
   - Kelola Pasien: Tambah, edit, aktifkan/nonaktifkan akun, reset password, dan hapus data.
   - Kelola Psikolog: Manajemen data psikolog, nomor izin praktik, status verifikasi, dan status akun.
   - Kelola Kategori Assessment: Tambah instrumen baru, aktifkan/nonaktifkan kategori.
   - Kelola Butir Pertanyaan: Tambah butir pertanyaan, atur urutan, tandai butir kritis (*crisis/self-harm*), dan pilihan jawaban.
   - Kelola Aturan Rentang Skor & Rekomendasi: Menentukan batas skor minimum/maksimum, nama kategori hasil, tingkat risiko, dan saran rekomendasi secara dinamis **tanpa mengubah baris kode program**.
   - Kelola Konsultasi: Memantau dan mengontrol seluruh transaksi sesi konsultasi lintas pengguna.
   - Laporan & Cetak PDF: Rekapitulasi pemeriksaan dan konsultasi dengan filter rentang tanggal, siap cetak dengan format kop resmi dan lembar pengesahan.
   - Kelola Artikel: Publikasi materi psikoedukasi dan artikel kesehatan mental.

---

## 🛠️ Teknologi yang Digunakan

- **Backend:** PHP 8.x (Native Modern dengan PDO MySQL, Arsitektur Bersih & Modular)
- **Database:** MySQL / MariaDB (Relational Integrity dengan Foreign Key Cascade)
- **Frontend:** HTML5, CSS3 Modern Flexbox/Grid, Vanilla JavaScript (ES6+)
- **Desain UI:** Calming Soft Green (`#3b8276`), Sky Blue (`#0284c7`), Soft Mint-Gray (`#f4f8f7`), Font *Plus Jakarta Sans*
- **Komponen:** Card UI, Responsive Sidebar & Topbar Dashboard, Modal Dialog, Print Stylesheet
- **Visualisasi & Ikon:** Chart.js, Font Awesome 6 Icons

---

## 🔐 Keamanan & Privasi Data

- **Password Hashing:** Menggunakan algoritma BCRYPT kuat (`PASSWORD_DEFAULT`).
- **Proteksi CSRF:** Token kriptografi acak pada setiap form metode POST (`csrf_check()`).
- **Proteksi XSS:** Sanitasi output dengan entitas HTML UTF-8 (`e()`).
- **Proteksi SQL Injection:** 100% Prepared Statements PDO pada seluruh interaksi basis data.
- **Role-Based Authorization:** Validasi sesi dan peran pada setiap modul halaman (`require_login()`).
- **Kerahasiaan Data Sensitif Pasien:** Akses rekam hasil skrining pasien oleh psikolog dibatasi oleh bendera otorisasi (*consent toggle* `izinkan_akses_riwayat`).

---

## 🔑 Akun Demo Siap Pakai

Tersedia tombol **⚡ Akses Demo Cepat (1-Klik)** pada halaman login untuk pengujian instan:

| Peran (Role) | Alamat Email | Password | Keterangan |
| :--- | :--- | :--- | :--- |
| **Admin** | `admin@mentalcare.test` | `Admin123!` | Akses seluruh panel kontrol master |
| **Psikolog 1** | `psikolog@mentalcare.test` | `Psikolog123!` | Sarah Amanda, M.Psi., Psikolog |
| **Psikolog 2** | `dian.psikolog@mentalcare.test` | `Psikolog123!` | Dian Wijaya, S.Psi., M.Psi. |
| **Pasien 1** | `pasien@mentalcare.test` | `Pasien123!` | Siti Rahmawati (Sudah ada riwayat) |
| **Pasien 2** | `budi.pasien@mentalcare.test` | `Pasien123!` | Budi Santoso |

---

## 🚀 Panduan Menjalankan Project

### A. Menggunakan XAMPP

1. **Pindahkan Folder Project:**
   Pastikan folder project berada di dalam direktori `htdocs` XAMPP Anda:
   ```
   C:\xampp\htdocs\mentalcare\mentalcare
   atau
   D:\xampp\htdocs\mentalcare\mentalcare
   ```

2. **Jalankan Apache & MySQL:**
   - Buka **XAMPP Control Panel**.
   - Klik **Start** pada modul **Apache** dan **MySQL**.

3. **Impor Database:**
   - Buka browser dan akses phpMyAdmin: `http://localhost/phpmyadmin/`
   - Buat database baru dengan nama: `db_mentalcare`
   - Klik menu **Import**, pilih file `database.sql` yang ada di root project ini.
   - Klik tombol **Import / Go**. Seluruh tabel relasional dan data awal (*seed data*) akan terbuat otomatis.

4. **Akses Website:**
   - Buka browser dan kunjungi:
     ```
     http://localhost/mentalcare/mentalcare/
     ```
     *(Sistem dilengkapi auto-detect `BASE_URL` cerdas, sehingga akan langsung berjalan normal di subfolder manapun).*

---

### B. Menggunakan Laragon

1. Buka aplikasi **Laragon**.
2. Klik **Start All** (Apache & MySQL).
3. Buka **HeidiSQL** atau **phpMyAdmin** bawaan Laragon, lalu jalankan/impor file `database.sql`.
4. Buka browser dan akses alamat virtual host atau localhost Anda:
   ```
   http://localhost/mentalcare/mentalcare/
   ```

---

### C. Menggunakan PHP Built-in Server (Opsional / Praktis)

Jika MySQL sudah berjalan di localhost (port 3306):
```bash
cd D:\xampp\htdocs\mentalcare\mentalcare
php -S localhost:8000
```
Buka browser dan akses: `http://localhost:8000/`

---

## 📁 Struktur Direktori Project

```text
mentalcare/
├── config/
│   └── config.php               # Konfigurasi database, deteksi URL, auth & helper
├── assets/
│   ├── css/
│   │   └── style.css            # Desain UI modern, menenangkan & responsif
│   └── js/
│       └── app.js               # Toggle menu, interaktivitas kuisioner & alert
├── includes/
│   ├── header.php               # Navigasi publik
│   ├── footer.php               # Footer publik & hotline darurat
│   ├── sidebar_pasien.php       # Layout dashboard & navigasi pasien
│   ├── sidebar_psikolog.php     # Layout dashboard & navigasi psikolog
│   ├── sidebar_admin.php        # Layout dashboard & navigasi admin
│   └── dashboard_footer.php     # Tag penutup dashboard
├── index.php                    # Halaman Beranda (Hero & 4 Card Layanan)
├── tentang.php                  # Halaman Tentang Kami
├── layanan.php                  # Halaman Layanan
├── artikel.php                  # Halaman Daftar Artikel Psikoedukasi
├── artikel_detail.php           # Halaman Baca Artikel
├── kontak.php                   # Kontak & Informasi Hotline Bantuan Krisis
├── login.php                    # Login Multi-Role (Dengan tombol demo)
├── register.php                 # Registrasi Pasien Lengkap
├── logout.php                   # Proses Keluar
├── dashboard.php                # Router Pengarah Role
├── database.sql                 # DDL MySQL & Data Awal Lengkap
├── pasien/
│   ├── index.php                # Dashboard Pasien
│   ├── assessment.php           # Pilih Kategori Skrining Mental
│   ├── kuisioner.php            # Pengisian Kuisioner Interaktif & Progress
│   ├── hasil.php                # Kartu Hasil, Skor & Rekomendasi
│   ├── riwayat.php              # Riwayat Pemeriksaan Pasien
│   ├── psikolog.php             # Direktori & Profil Psikolog Berlisensi
│   ├── konsultasi_booking.php   # Form Pengajuan Konsultasi & Jadwal
│   ├── konsultasi_riwayat.php   # Status & Riwayat Konsultasi Pasien
│   └── profil.php               # Edit Profil & Ubah Password Pasien
├── psikolog/
│   ├── index.php                # Dashboard Praktik Psikolog
│   ├── konsultasi.php           # Manajemen & Persetujuan Konsultasi
│   ├── jadwal.php               # Manajemen Slot Waktu Praktik
│   ├── pasien.php               # Daftar Pasien Bimbingan
│   ├── pasien_detail.php        # Rekam Medis & Riwayat Skrining (Privasi)
│   ├── catatan.php              # Catatan Sesi & Rekomendasi Psikologis
│   └── profil.php               # Kredensial SIPP & Profil Profesional
└── admin/
    ├── index.php                # Dashboard Admin & Grafik Statistik (Chart.js)
    ├── pasien.php               # Manajemen Pengguna Pasien (CRUD)
    ├── psikolog.php             # Manajemen Psikolog & Verifikasi (CRUD)
    ├── kategori.php             # Manajemen Kategori Assessment (CRUD)
    ├── pertanyaan.php           # Manajemen Butir Pertanyaan Kuisioner
    ├── aturan_skor.php          # Aturan Rentang Skor & Rekomendasi Dinamis
    ├── konsultasi.php           # Manajemen Seluruh Sesi Konsultasi
    ├── laporan.php              # Laporan Rekapitulasi & Cetak PDF
    ├── artikel.php              # Kelola Artikel & Konten Edukasi
    └── profil.php               # Pengaturan Akun Admin
```

---

## 🧪 Skenario Pengujian Alur Sistem

1. **Uji Coba Pasien:**
   - Masuk sebagai Pasien (`pasien@mentalcare.test` / `Pasien123!`).
   - Masuk ke menu **Mulai Pemeriksaan**, pilih kategori misal *Stres* atau *Kecemasan (GAD-7)*.
   - Jawab seluruh pertanyaan (perhatikan bilah progres bertambah secara *real-time*).
   - Klik **Selesai & Hitung Hasil** &rarr; Sistem menghitung total skor dan menampilkan kartu hasil, tingkat risiko, penafian medis, rekomendasi, serta rincian butir jawaban.
   - Klik tombol **Konsultasi dengan Psikolog** &rarr; Pilih psikolog dan ajukan jadwal sesi.

2. **Uji Coba Psikolog:**
   - Masuk sebagai Psikolog (`psikolog@mentalcare.test` / `Psikolog123!`).
   - Pada menu **Manajemen Konsultasi**, klik **Setujui** dan masukkan tautan *Google Meet*.
   - Buka **Rekam Pasien** &rarr; Jika pasien memberikan izin, psikolog dapat melihat seluruh riwayat skrining pasien.
   - Ubah status menjadi **Selesai** &rarr; Tuliskan hasil konsultasi, rekomendasi, dan tindak lanjut pada formulir catatan sesi.

3. **Uji Coba Administrator:**
   - Masuk sebagai Admin (`admin@mentalcare.test` / `Admin123!`).
   - Buka **Dashboard** untuk memantau grafik statistik dan metrik pengguna.
   - Buka menu **Aturan Rentang Skor** untuk mengubah ambang batas skor atau teks rekomendasi tanpa perlu menyentuh kode program.
   - Buka menu **Laporan & Cetak** &rarr; Filter rentang tanggal lalu klik **Cetak / Ekspor PDF**.

---

© 2026 **MentalCare**. Hak Cipta Dilindungi Undang-Undang.
