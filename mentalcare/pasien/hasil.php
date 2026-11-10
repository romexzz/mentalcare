<?php
require_once __DIR__ . '/../config/config.php';
$u = require_login(['pasien']);

$id = (int)($_GET['id'] ?? 0);

// Ambil data pemeriksaan
$stmt = db()->prepare('
    SELECT p.*, k.nama as nama_kategori, k.deskripsi as deskripsi_kategori, k.icon
    FROM pemeriksaan p
    JOIN kategori_assessment k ON k.id = p.kategori_id
    WHERE p.id = ? AND p.pasien_id = ?
');
$stmt->execute([$id, $u['id']]);
$exam = $stmt->fetch();

if (!$exam) {
    flash('Hasil pemeriksaan tidak ditemukan.', 'warning');
    redirect('pasien/riwayat.php');
}

// Ambil rincian jawaban
$stmtAns = db()->prepare('
    SELECT j.*, q.pertanyaan, q.urutan
    FROM jawaban_pemeriksaan j
    JOIN pertanyaan q ON q.id = j.pertanyaan_id
    WHERE j.pemeriksaan_id = ?
    ORDER BY q.urutan ASC
');
$stmtAns->execute([$id]);
$answers = $stmtAns->fetchAll();

$pageTitle = 'Hasil Pemeriksaan - ' . $exam['nama_kategori'];
$activeMenu = 'riwayat';
require __DIR__ . '/../includes/sidebar_pasien.php';
?>

<div class="assessment-container">
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
    <a href="<?= url('pasien/riwayat.php') ?>" class="btn btn-light btn-sm btn-no-print">
      <i class="fa-solid fa-arrow-left"></i> Kembali ke Riwayat
    </a>
    <div class="btn-no-print">
      <button onclick="window.print()" class="btn btn-outline btn-sm">
        <i class="fa-solid fa-print"></i> Cetak / Simpan PDF
      </button>
    </div>
  </div>

  <!-- Hero Card Hasil Pemeriksaan -->
  <div class="result-hero-card">
    <span class="badge badge-info" style="margin-bottom: 12px;">
      <i class="fa-solid <?= e($exam['icon'] ?: 'fa-brain') ?>"></i> <?= e($exam['nama_kategori']) ?>
    </span>

    <h2 style="font-size: 1.85rem; margin-bottom: 6px;">Hasil Pemeriksaan Mental Health</h2>
    <p class="muted" style="margin-bottom: 24px;">
      Tanggal Pemeriksaan: <?= format_tanggal($exam['tanggal_pemeriksaan'], true) ?>
    </p>

    <!-- Lingkaran Skor -->
    <div class="result-score-circle">
      <span class="result-score-number"><?= (int)$exam['total_skor'] ?></span>
      <span class="result-score-label">Total Skor</span>
    </div>

    <!-- Status & Tingkat Risiko -->
    <div style="display: flex; justify-content: center; gap: 24px; margin-bottom: 20px; flex-wrap: wrap;">
      <div>
        <span class="muted" style="font-size: 0.85rem; display: block;">Kategori Hasil</span>
        <strong style="font-size: 1.25rem; color: var(--primary-dark);"><?= e($exam['kategori_hasil']) ?></strong>
      </div>
      <div style="border-left: 1px solid var(--border-color); padding-left: 24px;">
        <span class="muted" style="font-size: 0.85rem; display: block;">Tingkat Risiko</span>
        <div><?= badge_risiko($exam['kategori_hasil'], $exam['tingkat_risiko']) ?></div>
      </div>
    </div>

    <!-- Tombol Aksi Cepat -->
    <div class="btn-no-print" style="display: flex; justify-content: center; gap: 12px; margin-top: 24px; flex-wrap: wrap;">
      <a href="<?= url('pasien/psikolog.php') ?>" class="btn btn-primary btn-lg">
        <i class="fa-solid fa-user-doctor"></i> Konsultasi dengan Psikolog
      </a>
      <a href="<?= url('pasien/index.php') ?>" class="btn btn-light btn-lg">
        <i class="fa-solid fa-house"></i> Kembali ke Dashboard
      </a>
    </div>
  </div>

  <!-- Disclaimer Medis (Wajib & Sangat Jelas) -->
  <div class="disclaimer-card">
    <i class="fa-solid fa-triangle-exclamation"></i>
    <div>
      <h4 style="margin-bottom: 4px; color: #92400e;">Catatan & Penafian Medis Penting</h4>
      <p style="margin: 0; font-size: 0.925rem;">
        Hasil kuisioner ini merupakan skrining awal indikatif untuk kebutuhan psikoedukasi dan <strong>bukan diagnosis medis</strong>. Hasil ini tidak menggantikan pemeriksaan, diagnosis, atau penanganan langsung oleh profesional kesehatan mental (psikolog/psikiater).
      </p>
    </div>
  </div>

  <!-- Card Rekomendasi Awal -->
  <div class="card" style="padding: 30px;">
    <div class="card-title" style="margin-bottom: 16px;">
      <i class="fa-solid fa-heart-pulse text-primary"></i> Saran & Rekomendasi Awal Untuk Anda
    </div>

    <div style="background: var(--bg-body); padding: 18px; border-radius: var(--radius-sm); border-left: 4px solid var(--primary); margin-bottom: 20px;">
      <p style="font-size: 1rem; color: var(--text-main); margin: 0; line-height: 1.7;">
        <?= nl2br(e($exam['rekomendasi'])) ?>
      </p>
    </div>

    <h4 style="margin-bottom: 12px; color: var(--primary-dark);">Langkah-langkah Praktis Pemulihan Mandiri:</h4>
    <ul class="recommendations-list">
      <li><strong>Menjaga Pola Tidur Teratur:</strong> Usahakan tidur 7-8 jam per malam pada jam yang konsisten dan hindari layar gadget 1 jam sebelum tidur.</li>
      <li><strong>Melakukan Aktivitas Fisik:</strong> Olahraga ringan seperti jalan kaki santai 20-30 menit dapat merangsang pelepasan hormon endorfin dan meredakan ketegangan.</li>
      <li><strong>Mengurangi Beban Aktivitas:</strong> Berani menetapkan batasan (boundaries) pada tugas yang tidak mendesak dan sediakan waktu jeda istirahat (microbreaks).</li>
      <li><strong>Melakukan Relaksasi:</strong> Latih teknik pernapasan dalam (teknik 4-7-8) atau meditasi mindfulness saat merasa cemas atau tegang.</li>
      <li><strong>Berbicara dengan Orang Terpercaya:</strong> Bagikan perasaan Anda kepada sahabat, keluarga, atau pasangan untuk mendapatkan dukungan emosional.</li>
      <li><strong>Mempertimbangkan Konsultasi Psikolog:</strong> Jika kondisi ini berlanjut lebih dari 2 minggu atau mulai mengganggu aktivitas sehari-hari, segera jadwalkan sesi bersama psikolog berlisensi di MentalCare.</li>
    </ul>
  </div>

  <!-- Detail Jawaban Butir Kuisioner -->
  <?php if (!empty($answers)): ?>
    <div class="card">
      <div class="card-header">
        <div class="card-title">
          <i class="fa-solid fa-list-check text-primary"></i> Rincian Jawaban Kuisioner
        </div>
      </div>
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th style="width: 50px;">No</th>
              <th>Pertanyaan</th>
              <th>Pilihan Jawaban Anda</th>
              <th style="width: 80px; text-align: center;">Skor</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($answers as $idx => $ans): ?>
              <tr>
                <td><?= $idx + 1 ?></td>
                <td><?= e($ans['pertanyaan']) ?></td>
                <td><span class="badge badge-light" style="background:#eef8f6; color:var(--primary-dark); font-weight:600;"><?= e($ans['jawaban_teks']) ?></span></td>
                <td style="text-align: center; font-weight: 700;"><?= (int)$ans['skor'] ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/dashboard_footer.php'; ?>
