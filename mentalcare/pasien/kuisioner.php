<?php
require_once __DIR__ . '/../config/config.php';
$u = require_login(['pasien']);

$kategori_id = (int)($_GET['id'] ?? $_POST['kategori_id'] ?? 0);

// Ambil data kategori
$stmtKat = db()->prepare('SELECT * FROM kategori_assessment WHERE id = ? AND status = "aktif"');
$stmtKat->execute([$kategori_id]);
$kategori = $stmtKat->fetch();

if (!$kategori) {
    flash('Kategori pemeriksaan tidak ditemukan atau tidak aktif.', 'warning');
    redirect('pasien/assessment.php');
}

// Ambil pertanyaan aktif
$stmtQ = db()->prepare('
    SELECT * FROM pertanyaan 
    WHERE kategori_id = ? AND status = "aktif"
    ORDER BY urutan ASC, id ASC
');
$stmtQ->execute([$kategori_id]);
$questions = $stmtQ->fetchAll();

if (empty($questions)) {
    flash('Belum ada pertanyaan pada kategori ini.', 'warning');
    redirect('pasien/assessment.php');
}

// Ambil pilihan jawaban untuk setiap pertanyaan dari database (tabel pilihan_jawaban)
$qIds = array_column($questions, 'id');
$placeholders = implode(',', array_fill(0, count($qIds), '?'));
$stmtOpts = db()->prepare("
    SELECT * FROM pilihan_jawaban 
    WHERE pertanyaan_id IN ($placeholders)
    ORDER BY urutan ASC, skor ASC
");
$stmtOpts->execute($qIds);
$allOpts = $stmtOpts->fetchAll();

// Group options by pertanyaan_id
$optionsByQ = [];
foreach ($allOpts as $opt) {
    $optionsByQ[$opt['pertanyaan_id']][] = $opt;
}

// Default 5 skala jika pertanyaan belum memiliki entri pilihan khusus
$defaultOptions = [
    ['pilihan' => 'Tidak pernah', 'skor' => 0],
    ['pilihan' => 'Jarang', 'skor' => 1],
    ['pilihan' => 'Kadang-kadang', 'skor' => 2],
    ['pilihan' => 'Sering', 'skor' => 3],
    ['pilihan' => 'Sangat sering', 'skor' => 4],
];

// Proses POST Jawaban
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $submittedAnswers = $_POST['jawaban'] ?? [];
    $totalSkor = 0;
    $adaItemKritis = 0;
    $missingCount = 0;
    $recordsToInsert = [];

    foreach ($questions as $q) {
        $qid = $q['id'];
        if (!isset($submittedAnswers[$qid]) || $submittedAnswers[$qid] === '') {
            $missingCount++;
            continue;
        }

        $skor = (int)$submittedAnswers[$qid];
        $totalSkor += $skor;

        // Ambil label teks pilihan
        $qOpts = $optionsByQ[$qid] ?? $defaultOptions;
        $labelTeks = 'Skor ' . $skor;
        $pilihanId = null;
        foreach ($qOpts as $opt) {
            if ((int)$opt['skor'] === $skor) {
                $labelTeks = $opt['pilihan'];
                $pilihanId = $opt['id'] ?? null;
                break;
            }
        }

        if ($q['kritis'] && $skor > 0) {
            $adaItemKritis = 1;
        }

        $recordsToInsert[] = [
            'pertanyaan_id' => $qid,
            'pilihan_id'    => $pilihanId,
            'jawaban_teks'  => $labelTeks,
            'skor'          => $skor
        ];
    }

    if ($missingCount > 0) {
        $error = "Harap jawab seluruh pertanyaan. Masih ada $missingCount pertanyaan yang belum Anda isi.";
    } else {
        // Hitung hasil assessment berdasarkan aturan yang dikelola Admin
        $hasil = hitung_hasil_assessment($kategori_id, $totalSkor);

        // Jika terdapat item kritis (seperti pikiran melukai diri), berikan peringatan tambahan pada rekomendasi
        $rekomendasiFinal = $hasil['rekomendasi'];
        if ($adaItemKritis) {
            $rekomendasiFinal = "PERHATIAN KHUSUS: Terdeteksi indikasi krisis / pikiran berisiko tinggi. Kami sangat menganjurkan Anda segera menghubungi psikolog kami atau saluran darurat 119 Ext 8 untuk mendapatkan pertolongan profesional segera.\n\n" . $rekomendasiFinal;
        }

        $pdo = db();
        try {
            $pdo->beginTransaction();

            // 1. Simpan ke tabel pemeriksaan
            $stmtIns = $pdo->prepare('
                INSERT INTO pemeriksaan (pasien_id, kategori_id, total_skor, kategori_hasil, tingkat_risiko, rekomendasi, ada_item_kritis, tanggal_pemeriksaan)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ');
            $stmtIns->execute([
                $u['id'],
                $kategori_id,
                $totalSkor,
                $hasil['kategori_hasil'],
                $hasil['tingkat_risiko'],
                $rekomendasiFinal,
                $adaItemKritis
            ]);
            $pemeriksaanId = $pdo->lastInsertId();

            // 2. Simpan setiap butir jawaban ke tabel jawaban_pemeriksaan
            $stmtAns = $pdo->prepare('
                INSERT INTO jawaban_pemeriksaan (pemeriksaan_id, pertanyaan_id, pilihan_id, jawaban_teks, skor)
                VALUES (?, ?, ?, ?, ?)
            ');
            foreach ($recordsToInsert as $r) {
                $stmtAns->execute([
                    $pemeriksaanId,
                    $r['pertanyaan_id'],
                    $r['pilihan_id'],
                    $r['jawaban_teks'],
                    $r['skor']
                ]);
            }

            $pdo->commit();

            flash('Pemeriksaan berhasil diselesaikan! Berikut adalah hasil dan rekomendasi untuk Anda.', 'success');
            redirect('pasien/hasil.php?id=' . $pemeriksaanId);

        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Gagal menyimpan hasil pemeriksaan. Silakan coba kembali. ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Kuisioner ' . $kategori['nama'];
$activeMenu = 'assessment';
require __DIR__ . '/../includes/sidebar_pasien.php';
?>

<div class="assessment-container">
  <!-- Header Kuisioner -->
  <div class="card" style="margin-bottom: 20px;">
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
      <div>
        <span class="badge badge-info" style="margin-bottom: 6px;">
          <i class="fa-solid <?= e($kategori['icon'] ?: 'fa-brain') ?>"></i> Skrining Mandiri
        </span>
        <h2><?= e($kategori['nama']) ?></h2>
        <p class="muted" style="margin-top: 4px;"><?= e($kategori['deskripsi']) ?></p>
      </div>
      <a href="<?= url('pasien/assessment.php') ?>" class="btn btn-outline btn-sm">
        <i class="fa-solid fa-arrow-left"></i> Ganti Kategori
      </a>
    </div>

    <?php if (!empty($kategori['instruksi'])): ?>
      <div style="background: var(--primary-light); padding: 12px 16px; border-radius: var(--radius-sm); margin-top: 14px; font-size: 0.9rem; color: var(--primary-dark); border-left: 3px solid var(--primary);">
        <i class="fa-solid fa-circle-info text-primary"></i> <strong>Petunjuk Pengisian:</strong> <?= e($kategori['instruksi']) ?>
      </div>
    <?php endif; ?>

    <!-- Progress Bar Interaktif -->
    <div style="margin-top: 20px;">
      <div style="display: flex; justify-content: space-between; font-size: 0.85rem; font-weight: 600; color: var(--text-muted); margin-bottom: 6px;">
        <span>Kemajuan Pengerjaan:</span>
        <span id="progressPercentText">0 dari <?= count($questions) ?> terjawab (0%)</span>
      </div>
      <div class="progress-bar-wrapper">
        <div class="progress-bar-fill" style="width: 0%;"></div>
      </div>
    </div>
  </div>

  <?php if ($error): ?>
    <div class="alert alert-danger" style="margin-bottom: 20px;">
      <i class="fa-solid fa-circle-exclamation"></i>
      <div><?= e($error) ?></div>
    </div>
  <?php endif; ?>

  <!-- Form Kuisioner -->
  <form method="post" action="" id="kuisionerForm">
    <?= csrf_field() ?>
    <input type="hidden" name="kategori_id" value="<?= $kategori_id ?>">

    <?php foreach ($questions as $idx => $q): ?>
      <?php 
        $qid = $q['id'];
        $qOpts = $optionsByQ[$qid] ?? $defaultOptions;
        $selectedVal = $_POST['jawaban'][$qid] ?? '';
      ?>
      <div class="question-box <?= $selectedVal !== '' ? 'answered' : '' ?>" id="qbox-<?= $qid ?>">
        <span class="question-number">Pertanyaan <?= $idx + 1 ?> dari <?= count($questions) ?></span>
        <div class="question-text"><?= e($q['pertanyaan']) ?></div>

        <div class="radio-options-grid">
          <?php foreach ($qOpts as $opt): ?>
            <?php 
              $skorVal = (string)$opt['skor'];
              $isChecked = ($selectedVal !== '' && (string)$selectedVal === $skorVal);
            ?>
            <label class="radio-card">
              <input type="radio" name="jawaban[<?= $qid ?>]" value="<?= $skorVal ?>" <?= $isChecked ? 'checked' : '' ?> required>
              <div class="radio-card-body">
                <?= e($opt['pilihan']) ?>
              </div>
            </label>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endforeach; ?>

    <!-- Tombol Submit -->
    <div class="card" style="margin-top: 24px; text-align: center; padding: 28px;">
      <p class="muted" style="font-size: 0.9rem; margin-bottom: 18px;">
        Pastikan semua pertanyaan telah Anda jawab dengan jujur sesuai apa yang Anda alami.
      </p>
      <button type="submit" class="btn btn-primary btn-lg" style="min-width: 260px;">
        <i class="fa-solid fa-calculator"></i> Selesai & Hitung Hasil
      </button>
    </div>
  </form>
</div>

<?php require __DIR__ . '/../includes/dashboard_footer.php'; ?>
