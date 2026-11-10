<?php
require_once __DIR__ . '/../config/config.php';
$u = require_login(['pasien']);

$selectedPsikologId = (int)($_GET['psikolog_id'] ?? $_POST['psikolog_id'] ?? 0);

// Ambil daftar psikolog aktif
$stmtPsi = db()->query('
    SELECT p.*, u.nama, u.email
    FROM psikolog p
    JOIN users u ON u.id = p.user_id
    WHERE p.status_verifikasi = "terverifikasi" AND u.status = "aktif"
    ORDER BY u.nama ASC
');
$psikologs = $stmtPsi->fetchAll();

// Ambil jadwal slot psikolog jika ada
$schedules = [];
if ($selectedPsikologId > 0) {
    $stmtSch = db()->prepare('
        SELECT * FROM jadwal_psikolog 
        WHERE psikolog_id = ? AND tanggal >= CURDATE() AND status = "tersedia"
        ORDER BY tanggal ASC, jam_mulai ASC
    ');
    $stmtSch->execute([$selectedPsikologId]);
    $schedules = $stmtSch->fetchAll();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $psikolog_id = (int)($_POST['psikolog_id'] ?? 0);
    $jadwal_id = !empty($_POST['jadwal_id']) ? (int)$_POST['jadwal_id'] : null;
    $tanggal = trim($_POST['tanggal'] ?? '');
    $waktu = trim($_POST['waktu'] ?? '');
    $metode = trim($_POST['metode'] ?? 'Online (Chat/Video Call)');
    $keluhan = trim($_POST['keluhan'] ?? '');
    $izinkan_akses = !empty($_POST['izinkan_akses_riwayat']) ? 1 : 0;

    // Jika memilih dari slot jadwal
    if ($jadwal_id) {
        $stmtJ = db()->prepare('SELECT * FROM jadwal_psikolog WHERE id = ? AND psikolog_id = ? AND status = "tersedia"');
        $stmtJ->execute([$jadwal_id, $psikolog_id]);
        $jadwalSlot = $stmtJ->fetch();
        if ($jadwalSlot) {
            $tanggal = $jadwalSlot['tanggal'];
            $waktu = $jadwalSlot['jam_mulai'];
        }
    }

    // Validasi
    if ($psikolog_id <= 0) {
        $error = 'Pilih psikolog terlebih dahulu.';
    } elseif (!$tanggal || $tanggal < date('Y-m-d')) {
        $error = 'Pilih tanggal konsultasi yang valid (mulai hari ini ke depan).';
    } elseif (!$waktu) {
        $error = 'Pilih waktu/jam konsultasi.';
    } elseif (empty($keluhan)) {
        $error = 'Jelaskan keluhan atau alasan Anda ingin berkonsultasi.';
    } else {
        $pdo = db();
        try {
            $pdo->beginTransaction();

            $stmtIns = $pdo->prepare('
                INSERT INTO konsultasi (pasien_id, psikolog_id, jadwal_id, tanggal, waktu, keluhan, metode, status, izinkan_akses_riwayat, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, "menunggu", ?, NOW())
            ');
            $stmtIns->execute([
                $u['id'],
                $psikolog_id,
                $jadwal_id,
                $tanggal,
                $waktu,
                $keluhan,
                $metode,
                $izinkan_akses
            ]);

            // Jika memilih slot jadwal, tandai slot sebagai dipesan
            if ($jadwal_id) {
                $stmtUpd = $pdo->prepare('UPDATE jadwal_psikolog SET status = "dipesan" WHERE id = ?');
                $stmtUpd->execute([$jadwal_id]);
            }

            $pdo->commit();

            flash('Permintaan konsultasi Anda berhasil dikirim! Menunggu konfirmasi dari psikolog.', 'success');
            redirect('pasien/konsultasi_riwayat.php');

        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Terjadi kesalahan sistem: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Form Pengajuan Konsultasi';
$activeMenu = 'konsultasi';
require __DIR__ . '/../includes/sidebar_pasien.php';
?>

<div class="container container-narrow">
  <div class="card" style="padding: 36px;">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; flex-wrap: wrap; gap: 10px;">
      <div>
        <h2 style="margin: 0; font-size: 1.5rem;">Ajukan Sesi Konsultasi</h2>
        <p class="muted" style="margin-top: 4px; font-size: 0.9rem;">
          Jadwalkan sesi privat dengan psikolog pilihan Anda.
        </p>
      </div>
      <a href="<?= url('pasien/psikolog.php') ?>" class="btn btn-light btn-sm">
        <i class="fa-solid fa-arrow-left"></i> Kembali ke Daftar Psikolog
      </a>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-danger">
        <i class="fa-solid fa-circle-exclamation"></i>
        <div><?= e($error) ?></div>
      </div>
    <?php endif; ?>

    <form method="post" action="">
      <?= csrf_field() ?>

      <!-- Pilih Psikolog -->
      <div class="form-group">
        <label class="form-label">Pilih Psikolog Profesional <span class="text-danger">*</span></label>
        <select name="psikolog_id" class="form-control" required onchange="window.location.href='<?= url('pasien/konsultasi_booking.php?psikolog_id=') ?>' + this.value">
          <option value="">-- Pilih Psikolog --</option>
          <?php foreach ($psikologs as $p): ?>
            <option value="<?= $p['id'] ?>" <?= $selectedPsikologId === (int)$p['id'] ? 'selected' : '' ?>>
              <?= e($p['nama']) ?> — <?= e($p['spesialisasi']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Jika Psikolog memiliki slot jadwal tersedia -->
      <?php if (!empty($schedules)): ?>
        <div class="form-group">
          <label class="form-label">Slot Jadwal Tersedia (Opsional / Praktis)</label>
          <select name="jadwal_id" class="form-control" id="selectJadwal" onchange="autoFillSchedule(this)">
            <option value="">-- Pilih Slot Tersedia atau Masukkan Manual di Bawah --</option>
            <?php foreach ($schedules as $sc): ?>
              <option value="<?= $sc['id'] ?>" data-tanggal="<?= $sc['tanggal'] ?>" data-waktu="<?= substr($sc['jam_mulai'], 0, 5) ?>">
                <?= format_tanggal($sc['tanggal']) ?> | Jam: <?= substr($sc['jam_mulai'], 0, 5) ?> - <?= substr($sc['jam_selesai'], 0, 5) ?> WIB
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      <?php endif; ?>

      <!-- Tanggal & Waktu Manual / Terisi -->
      <div class="form-row">
        <div class="form-group" style="flex: 1;">
          <label class="form-label">Tanggal Konsultasi <span class="text-danger">*</span></label>
          <input type="date" name="tanggal" id="inputTanggal" class="form-control" required min="<?= date('Y-m-d') ?>" value="<?= e($_POST['tanggal'] ?? '') ?>">
        </div>

        <div class="form-group" style="flex: 1;">
          <label class="form-label">Waktu / Jam Konsultasi <span class="text-danger">*</span></label>
          <input type="time" name="waktu" id="inputWaktu" class="form-control" required value="<?= e($_POST['waktu'] ?? '10:00') ?>">
        </div>
      </div>

      <!-- Metode Konsultasi -->
      <div class="form-group">
        <label class="form-label">Metode Konsultasi <span class="text-danger">*</span></label>
        <select name="metode" class="form-control" required>
          <option value="Online (Chat/Video Call)">Online (Video Call / Google Meet)</option>
          <option value="Tatap Muka">Tatap Muka (Offline di Klinik Mitra)</option>
        </select>
      </div>

      <!-- Keluhan Pasien -->
      <div class="form-group">
        <label class="form-label">Ceritakan Keluhan / Hal yang Ingin Dikonsultasikan <span class="text-danger">*</span></label>
        <textarea name="keluhan" class="form-control" rows="4" required placeholder="Contoh: Sering merasa gelisah berlebihan saat menghadapi pekerjaan, mengalami gangguan tidur selama 2 minggu, dan merasa kehilangan motivasi..."><?= e($_POST['keluhan'] ?? '') ?></textarea>
        <small class="muted">Informasi ini dijaga kerahasiaannya dan hanya dibaca oleh psikolog terkait untuk persiapan sesi.</small>
      </div>

      <!-- Izin Akses Riwayat Hasil Pemeriksaan -->
      <div style="background: var(--primary-light); padding: 16px; border-radius: var(--radius-sm); margin: 20px 0; border-left: 4px solid var(--primary);">
        <label style="display: flex; gap: 10px; align-items: flex-start; cursor: pointer; font-size: 0.9rem; margin: 0; font-weight: 600; color: var(--primary-dark);">
          <input type="checkbox" name="izinkan_akses_riwayat" value="1" checked style="width: auto; margin-top: 4px;">
          <span>
            Izinkan psikolog melihat riwayat hasil pemeriksaan kesehatan mental saya di MentalCare.
            <span style="display: block; font-weight: 400; font-size: 0.825rem; color: var(--text-muted); margin-top: 2px;">
              Membantu psikolog memahami gambaran awal kondisi emosional Anda sebelum sesi dimulai.
            </span>
          </span>
        </label>
      </div>

      <button type="submit" class="btn btn-primary btn-block btn-lg">
        <i class="fa-solid fa-paper-plane"></i> Kirim Permintaan Konsultasi
      </button>
    </form>
  </div>
</div>

<script>
function autoFillSchedule(select) {
  const opt = select.options[select.selectedIndex];
  if (opt && opt.dataset.tanggal) {
    document.getElementById('inputTanggal').value = opt.dataset.tanggal;
    document.getElementById('inputWaktu').value = opt.dataset.waktu;
  }
}
</script>

<?php require __DIR__ . '/../includes/dashboard_footer.php'; ?>
