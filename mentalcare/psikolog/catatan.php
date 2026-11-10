<?php
require_once __DIR__ . '/../config/config.php';
$u = require_login(['psikolog']);

$konsulId = (int)($_GET['konsultasi_id'] ?? $_POST['konsultasi_id'] ?? 0);

$stmtPsi = db()->prepare('SELECT id FROM psikolog WHERE user_id = ?');
$stmtPsi->execute([$u['id']]);
$psiId = (int)$stmtPsi->fetchColumn();

// Ambil data konsultasi
$stmtKonsul = db()->prepare('
    SELECT c.*, u.nama as nama_pasien, u.email, u.no_hp
    FROM konsultasi c
    JOIN users u ON u.id = c.pasien_id
    WHERE c.id = ? AND c.psikolog_id = ?
');
$stmtKonsul->execute([$konsulId, $psiId]);
$konsul = $stmtKonsul->fetch();

if (!$konsul) {
    flash('Data sesi konsultasi tidak ditemukan.', 'warning');
    redirect('psikolog/konsultasi.php');
}

// Cek catatan yang sudah ada
$stmtCatatan = db()->prepare('SELECT * FROM catatan_konsultasi WHERE konsultasi_id = ?');
$stmtCatatan->execute([$konsulId]);
$catatan = $stmtCatatan->fetch();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $keluhan = trim($_POST['keluhan'] ?? '');
    $hasil_konsultasi = trim($_POST['hasil_konsultasi'] ?? '');
    $rekomendasi = trim($_POST['rekomendasi'] ?? '');
    $tindak_lanjut = trim($_POST['tindak_lanjut'] ?? '');

    if (empty($hasil_konsultasi) || empty($rekomendasi) || empty($tindak_lanjut)) {
        $error = 'Harap isi hasil konsultasi, rekomendasi, dan rencana tindak lanjut.';
    } else {
        $pdo = db();
        try {
            $pdo->beginTransaction();

            if ($catatan) {
                // Update catatan
                $stmtUpd = $pdo->prepare('
                    UPDATE catatan_konsultasi 
                    SET keluhan = ?, hasil_konsultasi = ?, rekomendasi = ?, tindak_lanjut = ?, updated_at = NOW()
                    WHERE id = ?
                ');
                $stmtUpd->execute([$keluhan, $hasil_konsultasi, $rekomendasi, $tindak_lanjut, $catatan['id']]);
            } else {
                // Insert catatan baru
                $stmtIns = $pdo->prepare('
                    INSERT INTO catatan_konsultasi (konsultasi_id, keluhan, hasil_konsultasi, rekomendasi, tindak_lanjut, created_at)
                    VALUES (?, ?, ?, ?, ?, NOW())
                ');
                $stmtIns->execute([$konsulId, $keluhan, $hasil_konsultasi, $rekomendasi, $tindak_lanjut]);
            }

            // Pastikan status konsultasi menjadi 'selesai'
            $pdo->prepare('UPDATE konsultasi SET status = "selesai" WHERE id = ?')->execute([$konsulId]);

            $pdo->commit();
            flash('Catatan sesi konsultasi berhasil disimpan!', 'success');
            redirect('psikolog/konsultasi.php');

        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Gagal menyimpan catatan: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Catatan Konsultasi - ' . $konsul['nama_pasien'];
$activeMenu = 'konsultasi';
require __DIR__ . '/../includes/sidebar_psikolog.php';
?>

<div class="container container-narrow">
  <div style="margin-bottom: 20px;">
    <a href="<?= url('psikolog/konsultasi.php') ?>" class="btn btn-light btn-sm">
      <i class="fa-solid fa-arrow-left"></i> Kembali ke Manajemen Konsultasi
    </a>
  </div>

  <div class="card" style="padding: 36px;">
    <div style="margin-bottom: 24px; border-bottom: 1px solid var(--border-color); padding-bottom: 16px;">
      <span class="badge badge-primary" style="margin-bottom: 8px;">
        <i class="fa-solid fa-notes-medical"></i> Rekam Sesi Konseling
      </span>
      <h2 style="margin: 0; font-size: 1.5rem; color: var(--primary-dark);">Catatan Hasil Konsultasi</h2>
      <p class="muted" style="margin-top: 6px; font-size: 0.9rem;">
        Pasien: <strong><?= e($konsul['nama_pasien']) ?></strong> &bull; 
        Tanggal Sesi: <?= format_tanggal($konsul['tanggal']) ?> (<?= substr($konsul['waktu'], 0, 5) ?> WIB)
      </p>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-danger">
        <i class="fa-solid fa-circle-exclamation"></i>
        <div><?= e($error) ?></div>
      </div>
    <?php endif; ?>

    <form method="post" action="">
      <?= csrf_field() ?>
      <input type="hidden" name="konsultasi_id" value="<?= $konsulId ?>">

      <div class="form-group">
        <label class="form-label">Keluhan Pasien (Dikonfirmasi / Dieksplorasi)</label>
        <textarea name="keluhan" class="form-control" rows="3"><?= e($catatan['keluhan'] ?? $konsul['keluhan']) ?></textarea>
      </div>

      <div class="form-group">
        <label class="form-label">Hasil Konsultasi / Dinamika Psikologis <span class="text-danger">*</span></label>
        <textarea name="hasil_konsultasi" class="form-control" rows="5" required placeholder="Catatan observasi klinis, dinamika masalah yang ditemukan, serta respon kognitif/afektif pasien..."><?= e($catatan['hasil_konsultasi'] ?? '') ?></textarea>
        <small class="muted">Tuliskan analisis observasi dan temuan utama selama sesi berlangsung.</small>
      </div>

      <div class="form-group">
        <label class="form-label">Saran & Rekomendasi Psikolog <span class="text-danger">*</span></label>
        <textarea name="rekomendasi" class="form-control" rows="4" required placeholder="Contoh: Terapi restrukturisasi kognitif, teknik relaksasi otot progresif, dan penjadwalan aktivitas harian..."><?= e($catatan['rekomendasi'] ?? '') ?></textarea>
        <small class="muted">Rekomendasi ini akan dapat dibaca oleh pasien di dashboard riwayat konsultasinya.</small>
      </div>

      <div class="form-group">
        <label class="form-label">Rencana Tindak Lanjut (Follow-Up) <span class="text-danger">*</span></label>
        <textarea name="tindak_lanjut" class="form-control" rows="3" required placeholder="Contoh: Evaluasi perkembangan 2 minggu ke depan, atau rekomendasi rujukan psikiater bila diperlukan farmakoterapi..."><?= e($catatan['tindak_lanjut'] ?? '') ?></textarea>
      </div>

      <div style="margin-top: 28px;">
        <button type="submit" class="btn btn-primary btn-lg btn-block">
          <i class="fa-solid fa-floppy-disk"></i> Simpan Catatan & Tandai Sesi Selesai
        </button>
      </div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/../includes/dashboard_footer.php'; ?>
