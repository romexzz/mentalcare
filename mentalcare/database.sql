-- ========================================================
-- Database: db_mentalcare
-- Platform Kesehatan Mental Modern & Terpercaya
-- ========================================================

CREATE DATABASE IF NOT EXISTS db_mentalcare CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE db_mentalcare;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS catatan_konsultasi;
DROP TABLE IF EXISTS konsultasi;
DROP TABLE IF EXISTS jadwal_psikolog;
DROP TABLE IF EXISTS jawaban_pemeriksaan;
DROP TABLE IF EXISTS pemeriksaan;
DROP TABLE IF EXISTS kategori_skor_aturan;
DROP TABLE IF EXISTS pilihan_jawaban;
DROP TABLE IF EXISTS pertanyaan;
DROP TABLE IF EXISTS kategori_assessment;
DROP TABLE IF EXISTS psikolog;
DROP TABLE IF EXISTS artikel;
DROP TABLE IF EXISTS mood_logs;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. TABEL USERS
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(120) NOT NULL,
  identitas_nik VARCHAR(50) NULL COMMENT 'NIK atau NIM pasien/pengguna',
  email VARCHAR(120) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin', 'psikolog', 'pasien') NOT NULL DEFAULT 'pasien',
  no_hp VARCHAR(30) NULL,
  tanggal_lahir DATE NULL,
  jenis_kelamin ENUM('L', 'P') NULL,
  foto VARCHAR(255) NULL DEFAULT 'default.png',
  status ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'aktif',
  consent_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. TABEL PROFIL PSIKOLOG
CREATE TABLE psikolog (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL UNIQUE,
  nomor_izin VARCHAR(100) NULL COMMENT 'Nomor SIPP / STR Psikolog',
  spesialisasi VARCHAR(180) NOT NULL,
  pendidikan VARCHAR(200) NOT NULL,
  pengalaman VARCHAR(50) NOT NULL DEFAULT '3+ Tahun',
  deskripsi TEXT NULL,
  status_verifikasi ENUM('menunggu', 'terverifikasi', 'ditolak') NOT NULL DEFAULT 'terverifikasi',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 3. TABEL KATEGORI ASSESSMENT (KUISIONER)
CREATE TABLE kategori_assessment (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(100) NOT NULL,
  kode VARCHAR(40) NOT NULL UNIQUE,
  deskripsi TEXT NOT NULL,
  instruksi TEXT NULL,
  icon VARCHAR(50) DEFAULT 'fa-brain',
  status ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'aktif',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 4. TABEL PERTANYAAN
CREATE TABLE pertanyaan (
  id INT AUTO_INCREMENT PRIMARY KEY,
  kategori_id INT NOT NULL,
  pertanyaan TEXT NOT NULL,
  urutan INT NOT NULL DEFAULT 1,
  kritis TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 jika indikator risiko tinggi/self-harm',
  status ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'aktif',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (kategori_id) REFERENCES kategori_assessment(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 5. TABEL PILIHAN JAWABAN (DAPAT DISESUAIKAN PER PERTANYAAN / KATEGORI)
CREATE TABLE pilihan_jawaban (
  id INT AUTO_INCREMENT PRIMARY KEY,
  pertanyaan_id INT NULL COMMENT 'NULL jika digunakan untuk semua pertanyaan pada kategori',
  kategori_id INT NULL,
  pilihan VARCHAR(120) NOT NULL,
  skor INT NOT NULL DEFAULT 0,
  urutan INT NOT NULL DEFAULT 1,
  FOREIGN KEY (pertanyaan_id) REFERENCES pertanyaan(id) ON DELETE CASCADE,
  FOREIGN KEY (kategori_id) REFERENCES kategori_assessment(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 6. TABEL ATURAN SKOR & REKOMENDASI (ADMIN DAPAT MENGUBAH DARI WEB)
CREATE TABLE kategori_skor_aturan (
  id INT AUTO_INCREMENT PRIMARY KEY,
  kategori_id INT NOT NULL,
  skor_min INT NOT NULL,
  skor_max INT NOT NULL,
  nama_kategori VARCHAR(80) NOT NULL COMMENT 'Normal, Ringan, Sedang, Tinggi',
  tingkat_risiko VARCHAR(80) NOT NULL COMMENT 'Rendah, Sedang, Perlu diperhatikan, Tinggi',
  warna_badge VARCHAR(30) NOT NULL DEFAULT 'success' COMMENT 'success, info, warning, danger',
  saran_rekomendasi TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (kategori_id) REFERENCES kategori_assessment(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 7. TABEL PEMERIKSAAN (HASIL SKRINING PASIEN)
CREATE TABLE pemeriksaan (
  id INT AUTO_INCREMENT PRIMARY KEY,
  pasien_id INT NOT NULL,
  kategori_id INT NOT NULL,
  total_skor INT NOT NULL,
  kategori_hasil VARCHAR(80) NOT NULL,
  tingkat_risiko VARCHAR(80) NOT NULL,
  rekomendasi TEXT NOT NULL,
  ada_item_kritis TINYINT(1) NOT NULL DEFAULT 0,
  tanggal_pemeriksaan DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (pasien_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (kategori_id) REFERENCES kategori_assessment(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 8. TABEL JAWABAN PEMERIKSAAN
CREATE TABLE jawaban_pemeriksaan (
  id INT AUTO_INCREMENT PRIMARY KEY,
  pemeriksaan_id INT NOT NULL,
  pertanyaan_id INT NOT NULL,
  pilihan_id INT NULL,
  jawaban_teks VARCHAR(150) NULL,
  skor INT NOT NULL DEFAULT 0,
  FOREIGN KEY (pemeriksaan_id) REFERENCES pemeriksaan(id) ON DELETE CASCADE,
  FOREIGN KEY (pertanyaan_id) REFERENCES pertanyaan(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 9. TABEL JADWAL KETERSEDIAAN PSIKOLOG
CREATE TABLE jadwal_psikolog (
  id INT AUTO_INCREMENT PRIMARY KEY,
  psikolog_id INT NOT NULL,
  tanggal DATE NOT NULL,
  jam_mulai TIME NOT NULL,
  jam_selesai TIME NOT NULL,
  status ENUM('tersedia', 'dipesan', 'selesai', 'dibatalkan') NOT NULL DEFAULT 'tersedia',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (psikolog_id) REFERENCES psikolog(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 10. TABEL KONSULTASI
CREATE TABLE konsultasi (
  id INT AUTO_INCREMENT PRIMARY KEY,
  pasien_id INT NOT NULL,
  psikolog_id INT NOT NULL,
  jadwal_id INT NULL,
  tanggal DATE NOT NULL,
  waktu TIME NOT NULL,
  keluhan TEXT NOT NULL,
  metode ENUM('Online (Chat/Video Call)', 'Tatap Muka') NOT NULL DEFAULT 'Online (Chat/Video Call)',
  link_konsultasi VARCHAR(255) NULL,
  status ENUM('menunggu', 'disetujui', 'berlangsung', 'selesai', 'dibatalkan') NOT NULL DEFAULT 'menunggu',
  izinkan_akses_riwayat TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 jika psikolog boleh melihat hasil skrining pasien',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (pasien_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (psikolog_id) REFERENCES psikolog(id) ON DELETE CASCADE,
  FOREIGN KEY (jadwal_id) REFERENCES jadwal_psikolog(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 11. TABEL CATATAN KONSULTASI
CREATE TABLE catatan_konsultasi (
  id INT AUTO_INCREMENT PRIMARY KEY,
  konsultasi_id INT NOT NULL UNIQUE,
  keluhan TEXT NULL,
  hasil_konsultasi TEXT NOT NULL,
  rekomendasi TEXT NOT NULL,
  tindak_lanjut TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (konsultasi_id) REFERENCES konsultasi(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 12. TABEL ARTIKEL / EDUKASI
CREATE TABLE artikel (
  id INT AUTO_INCREMENT PRIMARY KEY,
  judul VARCHAR(220) NOT NULL,
  slug VARCHAR(240) NOT NULL UNIQUE,
  kategori VARCHAR(80) NOT NULL DEFAULT 'Edukasi',
  ringkasan TEXT NULL,
  isi MEDIUMTEXT NOT NULL,
  gambar VARCHAR(255) NULL,
  penulis_id INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (penulis_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 13. TABEL CATATAN MOOD PENGGUNA
CREATE TABLE mood_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  skor_mood TINYINT NOT NULL,
  catatan VARCHAR(255) NULL,
  tanggal DATE NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;


-- ========================================================
-- DATA AWAL (SEED DATA)
-- ========================================================

-- Akun Pengguna Default:
-- 1. Admin: admin@mentalcare.test / Admin123!
-- 2. Psikolog 1: psikolog@mentalcare.test / Psikolog123!
-- 3. Psikolog 2: dian.psikolog@mentalcare.test / Psikolog123!
-- 4. Pasien 1: pasien@mentalcare.test / Pasien123!
-- 5. Pasien 2: budi.pasien@mentalcare.test / Pasien123!

INSERT INTO users (id, nama, identitas_nik, email, password, role, no_hp, tanggal_lahir, jenis_kelamin, foto, status, consent_at) VALUES
(1, 'Administrator MentalCare', '3171010001010001', 'admin@mentalcare.test', '$2y$12$EbFLBCWiAJBt8Q3dKUfiLOLTd.oq2zqkZN51e4yRPqoq579BJAo7C', 'admin', '081299990001', '1990-01-15', 'L', 'default.png', 'aktif', NOW()),
(2, 'Sarah Amanda, M.Psi., Psikolog', '3271020002020002', 'psikolog@mentalcare.test', '$2y$12$9WvOxnuvHG1WMhdH5BLzNuV.Nj0kE7TijJkrSUXb60cq.03sWYsQy', 'psikolog', '081288880002', '1988-05-20', 'P', 'default.png', 'aktif', NOW()),
(3, 'Dian Wijaya, S.Psi., M.Psi.', '3371030003030003', 'dian.psikolog@mentalcare.test', '$2y$12$9WvOxnuvHG1WMhdH5BLzNuV.Nj0kE7TijJkrSUXb60cq.03sWYsQy', 'psikolog', '081277770003', '1992-08-14', 'L', 'default.png', 'aktif', NOW()),
(4, 'Siti Rahmawati', '3174055504950004', 'pasien@mentalcare.test', '$2y$12$eVTiX7t9fnByyBJsSjdySuc4yencNLuki4rXe7WI9z2y0kWP5EFmG', 'pasien', '081312345678', '1998-03-25', 'P', 'default.png', 'aktif', NOW()),
(5, 'Budi Santoso', '3275061208970005', 'budi.pasien@mentalcare.test', '$2y$12$eVTiX7t9fnByyBJsSjdySuc4yencNLuki4rXe7WI9z2y0kWP5EFmG', 'pasien', '081398765432', '1997-07-12', 'L', 'default.png', 'aktif', NOW());

-- Data Profil Psikolog
INSERT INTO psikolog (id, user_id, nomor_izin, spesialisasi, pendidikan, pengalaman, deskripsi, status_verifikasi) VALUES
(1, 2, 'SIPP-1284-PSI-2018', 'Stres Kerja, Depresi Ringan & Kecemasan', 'S2 Magister Psikologi Profesi Klinis Dewasa - Universitas Indonesia', '6 Tahun', 'Berpengalaman menangani klien dewasa muda dan pekerja dengan teknik Cognitive Behavioral Therapy (CBT) dan mindfulness.', 'terverifikasi'),
(2, 3, 'SIPP-2041-PSI-2020', 'Manajemen Emosi, Burnout & Masalah Relasi', 'S2 Magister Profesi Psikologi - Universitas Gadjah Mada', '4 Tahun', 'Fokus pada pendampingan pengelolaan stres akademik, burnout, serta penataan keseimbangan hidup dan karir.', 'terverifikasi');

-- Kategori Assessment
INSERT INTO kategori_assessment (id, nama, kode, deskripsi, instruksi, icon, status) VALUES
(1, 'Stres', 'STRESS', 'Pemeriksaan tingkat stres akibat beban kerja, akademik, atau rutinitas harian.', 'Bacalah setiap pernyataan dan pilih seberapa sering kamu merasakan kondisi berikut dalam 2 minggu terakhir.', 'fa-bolt', 'aktif'),
(2, 'Kecemasan (GAD-7)', 'GAD7', 'Skrining tingkat kecemasan umum untuk mendeteksi rasa khawatir berlebihan dan kegelisahan.', 'Pilih opsi yang paling menggambarkan frekuensi gangguan kecemasan dalam 2 minggu terakhir.', 'fa-heart-pulse', 'aktif'),
(3, 'Depresi (PHQ-9)', 'PHQ9', 'Pemeriksaan suasana hati, kehilangan minat, energi, dan gejala suasana hati depresif.', 'Selama 2 minggu terakhir, seberapa sering kamu terganggu oleh masalah-masalah berikut?', 'fa-cloud-rain', 'aktif'),
(4, 'Burnout', 'BURNOUT', 'Pengukuran tingkat kejenuhan, kelelahan fisik-emosional, dan hilangnya motivasi.', 'Pilih seberapa sesuai pernyataan ini dengan kondisi belajar atau pekerjaanmu saat ini.', 'fa-fire', 'aktif'),
(5, 'Kesejahteraan Mental', 'WELLBEING', 'Evaluasi aspek positif kesehatan mental, optimisme, dan kebermaknaan hidup.', 'Pilih frekuensi perasaan positif dan rasa bermakna yang kamu rasakan akhir-akhir ini.', 'fa-spa', 'aktif');

-- Aturan Rentang Skor untuk Kategori 1: Stres
INSERT INTO kategori_skor_aturan (kategori_id, skor_min, skor_max, nama_kategori, tingkat_risiko, warna_badge, saran_rekomendasi) VALUES
(1, 0, 7, 'Normal', 'Rendah', 'success', 'Kondisi Anda berada dalam batas stres normal dan adaptif. Pertahankan pola hidup sehat, waktu istirahat yang cukup, dan luangkan waktu untuk hobi atau relaksasi santai.'),
(1, 8, 14, 'Ringan', 'Sedang', 'info', 'Anda mengalami indikasi stres ringan. Disarankan untuk menjaga pola tidur 7-8 jam per hari, kurangi konsumsi kafein berlebih, lakukan peregangan fisik ringan, dan bagikan cerita kepada teman atau keluarga.'),
(1, 15, 21, 'Sedang', 'Perlu diperhatikan', 'warning', 'Tingkat stres Anda tergolong sedang dan berpotensi mengganggu produktivitas. Kurangi beban aktivitas yang tidak mendesak, praktikkan teknik pernapasan dalam (deep breathing), dan pertimbangkan berkonsultasi dengan psikolog untuk menyusun strategi coping.'),
(1, 22, 28, 'Tinggi', 'Tinggi', 'danger', 'Tingkat stres Anda berada dalam rentang tinggi. Sangat dianjurkan untuk segera melakukan konsultasi dengan psikolog guna mendapatkan pendampingan profesional dan mencegah komplikasi kesehatan.');

-- Aturan Rentang Skor untuk Kategori 2: Kecemasan (GAD-7)
INSERT INTO kategori_skor_aturan (kategori_id, skor_min, skor_max, nama_kategori, tingkat_risiko, warna_badge, saran_rekomendasi) VALUES
(2, 0, 4, 'Normal', 'Rendah', 'success', 'Tingkat kecemasan Anda sangat minim atau normal. Tetap pelihara rutinitas positif dan kelola waktu dengan seimbang.'),
(2, 5, 9, 'Ringan', 'Sedang', 'info', 'Ada gejala kecemasan ringan yang muncul sesekali. Lakukan latihan relaksasi seperti teknik grounding 5-4-3-2-1 atau jalan santai di ruang terbuka.'),
(2, 10, 14, 'Sedang', 'Perlu diperhatikan', 'warning', 'Kecemasan Anda berada pada level sedang. Anda mungkin sering merasa gelisah dan tegang. Sangat dianjurkan menjadwalkan konsultasi dengan psikolog untuk belajar mengelola kecemasan secara konstruktif.'),
(2, 15, 21, 'Tinggi', 'Tinggi', 'danger', 'Tingkat kecemasan tergolong tinggi dan membutuhkan perhatian serius. Segera konsultasikan dengan psikolog profesional MentalCare.');

-- Aturan Rentang Skor untuk Kategori 3: Depresi (PHQ-9)
INSERT INTO kategori_skor_aturan (kategori_id, skor_min, skor_max, nama_kategori, tingkat_risiko, warna_badge, saran_rekomendasi) VALUES
(3, 0, 4, 'Normal', 'Rendah', 'success', 'Tidak tampak indikasi gejala depresi yang signifikan. Tetap jaga keseimbangan hidup, hubungan sosial, dan istirahat berkualitas.'),
(3, 5, 9, 'Ringan', 'Sedang', 'info', 'Terdapat gejala suasana hati yang sedikit menurun. Cobalah berjalan pagi, berjemur di bawah sinar matahari, kurangi waktu layar sebelum tidur, dan bicarakan perasaan Anda dengan orang terpercaya.'),
(3, 10, 14, 'Sedang', 'Perlu diperhatikan', 'warning', 'Gejala depresi berada pada tingkat sedang. Disarankan untuk membatasi beban pekerjaan berlebih dan segera melakukan sesi konsultasi dengan psikolog untuk mendapatkan evaluasi menyeluruh.'),
(3, 15, 27, 'Tinggi', 'Tinggi', 'danger', 'Tingkat depresi tergolong tinggi. Segera cari pertolongan profesional. Jangan ragu menghubungi psikolog MentalCare atau layanan dukungan darurat.');

-- Aturan Rentang Skor untuk Kategori 4: Burnout
INSERT INTO kategori_skor_aturan (kategori_id, skor_min, skor_max, nama_kategori, tingkat_risiko, warna_badge, saran_rekomendasi) VALUES
(4, 0, 7, 'Normal', 'Rendah', 'success', 'Anda memiliki energi yang baik dan tidak menunjukkan tanda-tanda burnout. Pertahankan keseimbangan antara kerja/studi dan waktu istirahat.'),
(4, 8, 14, 'Ringan', 'Sedang', 'info', 'Kelelahan mulai terasa sesekali. Berikan jeda istirahat (microbreaks) di sela pekerjaan dan hindari lembur berturut-turut.'),
(4, 15, 21, 'Sedang', 'Perlu diperhatikan', 'warning', 'Anda berada dalam fase burnout sedang. Antusiasme dan energi mulai terkuras. Luangkan waktu untuk cuti/istirahat, tetapkan batasan pekerjaan yang tegas, dan konsultasikan dengan psikolog.'),
(4, 22, 28, 'Tinggi', 'Tinggi', 'danger', 'Burnout tingkat tinggi terdeteksi. Kelelahan emosional dan fisik yang berat membutuhkan intervensi dan pemulihan komprehensif bersama profesional.');

-- Aturan Rentang Skor untuk Kategori 5: Kesejahteraan Mental
INSERT INTO kategori_skor_aturan (kategori_id, skor_min, skor_max, nama_kategori, tingkat_risiko, warna_badge, saran_rekomendasi) VALUES
(5, 0, 10, 'Rendah', 'Perlu diperhatikan', 'warning', 'Kesejahteraan mental Anda saat ini dirasa rendah. Luangkan waktu untuk merawat diri (self-care), kenali hal-hal yang membuat Anda bahagia, dan diskusikan dengan psikolog jika diperlukan.'),
(5, 11, 19, 'Sedang', 'Sedang', 'info', 'Tingkat kesejahteraan mental cukup baik namun masih ada ruang untuk ditingkatkan melalui hubungan sosial yang hangat dan aktivitas bermakna.'),
(5, 20, 28, 'Tinggi (Sangat Baik)', 'Rendah', 'success', 'Kesejahteraan mental Anda optimal! Anda merasa optimis, terhubung dengan lingkungan, dan mampu mengatasi tantangan dengan positif.');

-- Pertanyaan Kategori 1: Stres (7 pertanyaan)
INSERT INTO pertanyaan (id, kategori_id, pertanyaan, urutan, kritis, status) VALUES
(1, 1, 'Saya merasa sulit untuk menenangkan diri dan merasa gelisah setelah mengalami ketegangan.', 1, 0, 'aktif'),
(2, 1, 'Saya cenderung bereaksi berlebihan terhadap situasi yang tidak terduga atau menjengkelkan.', 2, 0, 'aktif'),
(3, 1, 'Saya merasa menghabiskan banyak energi mental karena rasa khawatir dan tegang.', 3, 0, 'aktif'),
(4, 1, 'Saya mudah merasa tersinggung, gusar, atau kesal terhadap hal-hal kecil.', 4, 0, 'aktif'),
(5, 1, 'Saya merasa sulit untuk beristirahat dengan tenang di waktu luang.', 5, 0, 'aktif'),
(6, 1, 'Saya merasa sangat sensitif dan mudah merasa kewalahan oleh tuntutan sekitar.', 6, 0, 'aktif'),
(7, 1, 'Saya merasa tidak sabar ketika mengalami penundaan (misal antrean atau kemacetan).', 7, 0, 'aktif');

-- Pertanyaan Kategori 2: Kecemasan (GAD-7) (7 pertanyaan)
INSERT INTO pertanyaan (id, kategori_id, pertanyaan, urutan, kritis, status) VALUES
(8, 2, 'Merasa gugup, cemas, atau sangat gelisah.', 1, 0, 'aktif'),
(9, 2, 'Tidak mampu menghentikan atau mengendalikan rasa khawatir yang terus muncul.', 2, 0, 'aktif'),
(10, 2, 'Terlalu mengkhawatirkan berbagai hal yang belum tentu terjadi.', 3, 0, 'aktif'),
(11, 2, 'Mengalami kesulitan untuk bersantai atau merasa relaks.', 4, 0, 'aktif'),
(12, 2, 'Sangat gelisah sehingga sulit untuk duduk diam dengan tenang.', 5, 0, 'aktif'),
(13, 2, 'Mudah merasa kesal, jengkel, atau gampang marah.', 6, 0, 'aktif'),
(14, 2, 'Merasa takut seolah-olah sesuatu yang buruk atau mengerikan akan terjadi.', 7, 0, 'aktif');

-- Pertanyaan Kategori 3: Depresi (PHQ-9) (9 pertanyaan)
INSERT INTO pertanyaan (id, kategori_id, pertanyaan, urutan, kritis, status) VALUES
(15, 3, 'Kurang berminat atau tidak bergairah dalam melakukan kegiatan sehari-hari.', 1, 0, 'aktif'),
(16, 3, 'Merasa sedih, murung, putus asa, atau tertekan.', 2, 0, 'aktif'),
(17, 3, 'Sulit tidur, sering terbangun di malam hari, atau justru tidur berlebihan.', 3, 0, 'aktif'),
(18, 3, 'Merasa lelah, kurang bertenaga, atau cepat capek sepanjang hari.', 4, 0, 'aktif'),
(19, 3, 'Nafsu makan menurun drastis atau sebaliknya makan berlebihan secara emosional.', 5, 0, 'aktif'),
(20, 3, 'Merasa buruk tentang diri sendiri, merasa gagal, atau merasa mengecewakan keluarga.', 6, 0, 'aktif'),
(21, 3, 'Sulit berkonsentrasi pada hal seperti membaca buku atau menonton tayangan.', 7, 0, 'aktif'),
(22, 3, 'Bergerak atau berbicara sangat lambat, atau sebaliknya sangat gelisah tidak bisa diam.', 8, 0, 'aktif'),
(23, 3, 'Merasa lebih baik mati atau memiliki pikiran untuk melukai diri sendiri.', 9, 1, 'aktif');

-- Pertanyaan Kategori 4: Burnout (7 pertanyaan)
INSERT INTO pertanyaan (id, kategori_id, pertanyaan, urutan, kritis, status) VALUES
(24, 4, 'Saya merasa lelah secara fisik dan emosional ketika memikirkan hari esok.', 1, 0, 'aktif'),
(25, 4, 'Saya merasa kehilangan antusiasme terhadap pekerjaan atau studi saya.', 2, 0, 'aktif'),
(26, 4, 'Saya merasa hasil kerja/studi saya tidak lagi memiliki dampak yang berarti.', 3, 0, 'aktif'),
(27, 4, 'Saya merasa ingin menarik diri dari rekan kerja, teman, atau lingkungan sosial.', 4, 0, 'aktif'),
(28, 4, 'Saya merasa rentang konsentrasi saya menurun drastis akhir-akhir ini.', 5, 0, 'aktif'),
(29, 4, 'Saya merasa frustrasi dan sinis terhadap rutinitas harian saya.', 6, 0, 'aktif'),
(30, 4, 'Waktu istirahat di akhir pekan terasa tidak cukup untuk memulihkan energi saya.', 7, 0, 'aktif');

-- Pertanyaan Kategori 5: Kesejahteraan Mental (7 pertanyaan)
INSERT INTO pertanyaan (id, kategori_id, pertanyaan, urutan, kritis, status) VALUES
(31, 5, 'Saya merasa optimis tentang masa depan saya.', 1, 0, 'aktif'),
(32, 5, 'Saya merasa berguna dan dapat memberikan kontribusi kepada orang lain.', 2, 0, 'aktif'),
(33, 5, 'Saya merasa santai dan damai dalam menjalani hidup.', 3, 0, 'aktif'),
(34, 5, 'Saya merasa tertarik dengan orang-orang baru dan lingkungan di sekitar saya.', 4, 0, 'aktif'),
(35, 5, 'Saya memiliki energi yang cukup untuk menyelesaikan apa yang perlu dilakukan.', 5, 0, 'aktif'),
(36, 5, 'Saya dapat berpikir jernih saat menghadapi masalah atau mengambil keputusan.', 6, 0, 'aktif'),
(37, 5, 'Saya merasa dekat dan terhubung secara hangat dengan orang lain.', 7, 0, 'aktif');

-- Pilihan Jawaban Skala (Dapat diterapkan pada tiap pertanyaan)
-- Standar Skala 5 Tingkat:
-- 0: Tidak pernah
-- 1: Jarang
-- 2: Kadang-kadang
-- 3: Sering
-- 4: Sangat sering
-- Kita isi default template pilihan jawaban untuk setiap pertanyaan:
INSERT INTO pilihan_jawaban (pertanyaan_id, pilihan, skor, urutan)
SELECT p.id, opts.pilihan, opts.skor, opts.urutan
FROM pertanyaan p
CROSS JOIN (
    SELECT 'Tidak pernah' AS pilihan, 0 AS skor, 1 AS urutan UNION ALL
    SELECT 'Jarang', 1, 2 UNION ALL
    SELECT 'Kadang-kadang', 2, 3 UNION ALL
    SELECT 'Sering', 3, 4 UNION ALL
    SELECT 'Sangat sering', 4, 5
) opts;

-- Data Jadwal Ketersediaan Psikolog
INSERT INTO jadwal_psikolog (id, psikolog_id, tanggal, jam_mulai, jam_selesai, status) VALUES
(1, 1, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '09:00:00', '10:00:00', 'tersedia'),
(2, 1, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '10:30:00', '11:30:00', 'tersedia'),
(3, 1, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '14:00:00', '15:00:00', 'dipesan'),
(4, 2, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '13:00:00', '14:00:00', 'tersedia'),
(5, 2, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '09:30:00', '10:30:00', 'tersedia');

-- Data Konsultasi Contoh
INSERT INTO konsultasi (id, pasien_id, psikolog_id, jadwal_id, tanggal, waktu, keluhan, metode, link_konsultasi, status, izinkan_akses_riwayat, created_at) VALUES
(1, 4, 1, 3, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '14:00:00', 'Sering merasa cemas berlebihan saat presentasi dan sulit tidur nyenyak beberapa minggu terakhir.', 'Online (Chat/Video Call)', 'https://meet.google.com/xyz-care-abc', 'disetujui', 1, NOW() - INTERVAL 1 DAY),
(2, 4, 2, NULL, CURDATE() - INTERVAL 5 DAY, '10:00:00', 'Merasa jenuh dan lelah luar biasa karena beban tugas akhir yang menumpuk.', 'Online (Chat/Video Call)', 'https://meet.google.com/def-care-ghi', 'selesai', 1, NOW() - INTERVAL 6 DAY),
(3, 5, 2, NULL, DATE_ADD(CURDATE(), INTERVAL 3 DAY), '15:00:00', 'Ingin berdiskusi mengenai teknik regulasi emosi dan komunikasi dengan pasangan.', 'Tatap Muka', NULL, 'menunggu', 1, NOW());

-- Catatan Konsultasi untuk Sesi yang sudah Selesai (Konsultasi id = 2)
INSERT INTO catatan_konsultasi (id, konsultasi_id, keluhan, hasil_konsultasi, rekomendasi, tindak_lanjut) VALUES
(1, 2, 'Kelelahan emosional dan fisik akibat penyusunan tugas akhir, merasa buntu dan kehilangan motivasi.', 
'Pasien menunjukkan indikasi academic burnout level sedang. Pola tidur tidak teratur (tidur rata-rata 4 jam). Kognisi irasional muncul berupa "harus sempurna". Pasien responsif saat dikenalkan dengan cognitive reframing.',
'1. Terapkan aturan Pomodoro (25 menit kerja, 5 menit jeda).\n2. Batasi waktu kerja di malam hari maksimal pukul 22.00.\n3. Latihan relaksasi napas 4-7-8 setiap sebelum tidur.',
'Evaluasi berkala setelah 2 minggu melalui skrining ulang Burnout. Pasien disarankan booking sesi lanjutan jika rasa cemas meningkat.');

-- Data Pemeriksaan Contoh
INSERT INTO pemeriksaan (id, pasien_id, kategori_id, total_skor, kategori_hasil, tingkat_risiko, rekomendasi, ada_item_kritis, tanggal_pemeriksaan, created_at) VALUES
(1, 4, 1, 16, 'Sedang', 'Perlu diperhatikan', 'Tingkat stres Anda tergolong sedang dan berpotensi mengganggu produktivitas. Kurangi beban aktivitas yang tidak mendesak, praktikkan teknik pernapasan dalam (deep breathing), dan pertimbangkan berkonsultasi dengan psikolog untuk menyusun strategi coping.', 0, NOW() - INTERVAL 7 DAY, NOW() - INTERVAL 7 DAY),
(2, 4, 2, 11, 'Sedang', 'Perlu diperhatikan', 'Kecemasan Anda berada pada level sedang. Anda mungkin sering merasa gelisah dan tegang. Sangat dianjurkan menjadwalkan konsultasi dengan psikolog untuk belajar mengelola kecemasan secara konstruktif.', 0, NOW() - INTERVAL 3 DAY, NOW() - INTERVAL 3 DAY),
(3, 5, 4, 9, 'Ringan', 'Sedang', 'Kelelahan mulai terasa sesekali. Berikan jeda istirahat (microbreaks) di sela pekerjaan dan hindari lembur berturut-turut.', 0, NOW() - INTERVAL 2 DAY, NOW() - INTERVAL 2 DAY);

-- Data Jawaban Pemeriksaan Sampel (untuk pemeriksaan id = 2)
INSERT INTO jawaban_pemeriksaan (pemeriksaan_id, pertanyaan_id, skor, jawaban_teks) VALUES
(2, 8, 2, 'Kadang-kadang'),
(2, 9, 2, 'Kadang-kadang'),
(2, 10, 2, 'Kadang-kadang'),
(2, 11, 1, 'Jarang'),
(2, 12, 1, 'Jarang'),
(2, 13, 2, 'Kadang-kadang'),
(2, 14, 1, 'Jarang');

-- Data Artikel Edukasi Kesehatan Mental
INSERT INTO artikel (id, judul, slug, kategori, ringkasan, isi, gambar, penulis_id, created_at) VALUES
(1, 'Mengenali Tanda-Tanda Stres dan Kapan Harus Mencari Bantuan', 'mengenali-tanda-stres', 'Stres', 
'Stres adalah reaksi alamiah tubuh, namun jika dibiarkan berlarut-larut dapat mempengaruhi kesehatan fisik dan mental.',
'<h3>Apa Itu Stres?</h3><p>Stres adalah reaksi alami tubuh ketika menghadapi tekanan, tantangan, atau perubahan tuntutan lingkungan. Pada tingkat tertentu, stres membantu kita tetap waspada dan termotivasi (eustress). Namun, ketika stres terjadi berkepanjangan tanpa waktu pemulihan yang cukup (distress), sistem kekebalan tubuh, pola tidur, dan kestabilan emosi kita dapat terganggu secara nyata.</p><h3>Tanda-Tanda Fisik dan Psikologis</h3><ul><li><strong>Fisik:</strong> Sakit kepala tegang, otot leher kaku, gangguan pencernaan, jantung berdebar, dan mudah lelah.</li><li><strong>Emosional:</strong> Mudah tersinggung, cemas tanpa alasan jelas, merasa kewalahan, dan kehilangan minat pada hal yang biasa dinikmati.</li><li><strong>Perilaku:</strong> Menunda-nunda pekerjaan, isolasi sosial, perubahan nafsu makan, dan konsumsi kafein berlebihan.</li></ul><h3>Langkah Awal Penanganan</h3><p>Kenali pemicu utama stres Anda, praktikkan teknik pernapasan dalam (deep breathing), dan batasi konsumsi informasi negatif di media sosial. Apabila stres berlangsung lebih dari dua minggu dan mengganggu aktivitas fungsional Anda, segera jadwalkan konsultasi dengan psikolog berlisensi di MentalCare.</p>', 'artikel-stres.jpg', 2, NOW() - INTERVAL 10 DAY),

(2, 'Teknik Relaksasi Pernapasan 4-7-8 untuk Meredakan Kecemasan', 'teknik-relaksasi-4-7-8', 'Relaksasi',
'Latihan pernapasan sederhana dan terbukti efektif untuk menurunkan ketegangan saraf simpatis hanya dalam hitungan menit.',
'<h3>Mengapa Pernapasan Mempengaruhi Kecemasan?</h3><p>Saat cemas, tubuh kita secara otomatis mengaktifkan respons fight-or-flight yang membuat napas menjadi pendek dan cepat. Dengan mengatur pernapasan secara sadar dan perlahan, kita mengirimkan sinyal ke otak bahwa kondisi saat ini aman, sehingga sistem saraf parasimpatis aktif untuk menurunkan detak jantung dan tekanan darah.</p><h3>Panduan Praktik 4-7-8</h3><ol><li>Duduklah dalam posisi nyaman dengan punggung tegak dan bahu rileks.</li><li>Keluarkan seluruh udara dari paru-paru lewat mulut.</li><li>Tutup mulut, lalu tarik napas perlahan lewat hidung selama <strong>4 hitungan</strong>.</li><li>Tahan napas Anda selama <strong>7 hitungan</strong>.</li><li>Hembuskan napas sepenuhnya lewat mulut dengan suara mendesir selama <strong>8 hitungan</strong>.</li><li>Ulangi siklus ini sebanyak 4 kali putaran.</li></ol><p>Lakukan teknik ini setiap pagi dan sesaat sebelum tidur untuk membantu tidur lebih lelap dan pikiran lebih tenang.</p>', 'artikel-napas.jpg', 2, NOW() - INTERVAL 8 DAY),

(3, 'Mengenal Burnout: Perbedaan Antara Lelah Biasa dan Kelelahan Emosional', 'mengenal-burnout-vs-lelah-biasa', 'Burnout',
'Banyak orang mengira burnout sekadar lelah bekerja. Padahal burnout melibatkan kelelahan mental, sinisme, dan hilangnya rasa keberhasilan.',
'<h3>Bukan Sekadar Lelah</h3><p>Lelah biasa umumnya dapat pulih setelah istirahat satu atau dua malam atau di akhir pekan. Namun burnout adalah kondisi kelelahan kronis akibat stres di tempat kerja atau akademik yang tidak berhasil dikelola dengan baik. World Health Organization (WHO) mengklasifikasikan burnout sebagai fenomena okupasional dengan tiga dimensi utama:</p><ul><li><strong>Kelelahan Energi:</strong> Merasa terkuras habis setiap hari bahkan saat bangun tidur.</li><li><strong>Sinisme dan Depersonalisasi:</strong> Mulai bersikap dingin, sinis, atau berjarak secara emosional dari tugas dan rekan kerja.</li><li><strong>Penurunan Efikasi Profesional:</strong> Merasa tidak kompeten dan kehilangan rasa bangga terhadap pencapaian diri.</li></ul><p>Pemulihan burnout memerlukan peninjauan kembali batas-batas (boundaries) pribadi, delegasi tugas, dan pendampingan psikologis untuk merestrukturisasi pola kerja dan hidup.</p>', 'artikel-burnout.jpg', 3, NOW() - INTERVAL 5 DAY),

(4, 'Pentingnya Menjaga Kebersihan Tidur (Sleep Hygiene) untuk Kesehatan Mental', 'kebersihan-tidur-sleep-hygiene', 'Kesejahteraan',
'Kualitas tidur dan kesehatan mental memiliki hubungan dua arah yang sangat erat. Temukan tips praktis memperbaiki tidur Anda.',
'<h3>Tidur dan Regulasi Emosi</h3><p>Selama fase tidur REM dan deep sleep, otak kita memproses memori emosional dan membersihkan racun metabolik. Kurang tidur kronis membuat amigdala (pusat pengatur emosi dan rasa takut) bekerja 60% lebih reaktif, sehingga kita menjadi jauh lebih mudah cemas dan tersinggung.</p><h3>Tips Kebersihan Tidur Efektif:</h3><ul><li>Bangun dan tidur pada jam yang sama setiap hari, termasuk di akhir pekan.</li><li>Hentikan paparan layar gadget (smartphone, laptop, TV) minimal 45-60 menit sebelum tidur.</li><li>Jadikan kamar tidur sejuk, gelap, dan hening.</li><li>Hindari konsumsi kafein setelah jam 14.00 siang.</li><li>Gunakan tempat tidur hanya untuk tidur dan beristirahat, bukan untuk bekerja.</li></ul>', 'artikel-tidur.jpg', 3, NOW() - INTERVAL 2 DAY);
