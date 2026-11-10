<?php
require_once __DIR__ . '/../config/config.php';
$u = require_login(['pasien']);

$pageTitle = 'Jadwal & Riwayat Konsultasi';
$activeMenu = 'konsultasi';

// Pembatalan Konsultasi oleh Pasien
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi']) && $_POST['aksi'] === 'batal') {
    csrf_check();
    $konsul_id = (int)($_POST['konsultasi_id'] ?? 0);

    $stmtCek = db()->prepare('SELECT * FROM konsultasi WHERE id = ? AND pasien_id = ? AND status = "menunggu"');
    $stmtCek->execute([$konsul_id, $u['id']]);
    $k = $stmtCek->fetch();

    if ($k) {
        db()->prepare('UPDATE konsultasi SET status = "dibatalkan" WHERE id = ?')->execute([$konsul_id]);
        // Kembalikan slot jadwal jika ada
        if (!empty($k['jadwal_id'])) {
            db()->prepare('UPDATE jadwal_psikolog SET status = "tersedia" WHERE id = ?')->execute([$k['jadwal_id']]);
        }
        flash('Permintaan konsultasi telah dibatalkan.', 'info');
    } else {
        flash('Hanya konsultasi yang berstatus "menunggu" yang dapat dibatalkan.', 'warning');
    }
    redirect('pasien/konsultasi_riwayat.php');
}

// Ambil daftar konsultasi pasien beserta catatan dari psikolog
$stmt = db()->prepare('
    SELECT c.*, u.nama as nama_psikolog, p.spesialisasi, p.nomor_izin,
           ck.hasil_konsultasi, ck.rekomendasi as rekomendasi_psikolog, ck.tindak_lanjut
    FROM konsultasi c
    JOIN psikolog p ON p.id = c.psikolog_id
    JOIN users u ON u.id = p.user_id
    LEFT JOIN catatan_konsultasi ck ON ck.konsultasi_id = c.id
    WHERE c.pasien_id = ?
    ORDER BY c.tanggal DESC, c.waktu DESC
');
$stmt->execute([$u['id']]);
$consultations = $stmt->fetchAll();

require __DIR__ . '/../includes/sidebar_pasien.php';
?>

<div class="card">
  <div class="card-header" style="flex-wrap: wrap; gap: 12px;">
    <div>
      <h2 style="font-size: 1.35rem; margin: 0;">Jadwal & Riwayat Konsultasi</h2>
      <p class="muted" style="margin-top: 4px; font-size: 0.9rem;">
        Pantau status persetujuan, tautan sesi online, dan catatan hasil rekomendasi dari psikolog Anda.
      </p>
    </div>
    <a href="<?= url('pasien/psikolog.php') ?>" class="btn btn-primary btn-sm">
      <i class="fa-solid fa-plus"></i> Ajukan Konsultasi Baru
    </a>
  </div>

  <?php if (!empty($consultations)): ?>
    <div style="display: flex; flex-direction: column; gap: 18px;">
      <?php foreach ($consultations as $c): ?>
        <div style="border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 22px; background: #fff;">
          <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 10px; margin-bottom: 12px;">
            <div>
              <span class="badge badge-secondary" style="font-size: 0.75rem; margin-bottom: 4px;">
                <i class="fa-regular fa-clock"></i> Diajukan: <?= format_tanggal($c['created_at']) ?>
              </span>
              <h3 style="font-size: 1.15rem; color: var(--primary-dark); margin: 4px 0;">
                <i class="fa-solid fa-user-doctor text-secondary"></i> <?= e($c['nama_psikolog']) ?>
              </h3>
              <p class="muted" style="font-size: 0.85rem; margin: 0;">
                Spesialisasi: <?= e($c['spesialisasi']) ?>
              </p>
            </div>
            <div>
              <?= badge_status($c['status']) ?>
            </div>
          </div>

          <!-- Rincian Waktu & Metode -->
          <div style="display: flex; gap: 20px; font-size: 0.9rem; margin-bottom: 14px; flex-wrap: wrap; background: var(--bg-body); padding: 12px 16px; border-radius: var(--radius-sm);">
            <div>
              <strong class="muted"><i class="fa-regular fa-calendar text-primary"></i> Tanggal:</strong> <?= format_tanggal($c['tanggal']) ?>
            </div>
            <div>
              <strong class="muted"><i class="fa-regular fa-clock text-primary"></i> Waktu:</strong> <?= substr($c['waktu'], 0, 5) ?> WIB
            </div>
            <div>
              <strong class="muted"><i class="fa-solid fa-video text-primary"></i> Metode:</strong> <?= e($c['metode']) ?>
            </div>
            <div>
              <strong class="muted"><i class="fa-solid fa-eye text-primary"></i> Izin Riwayat:</strong> <?= $c['izinkan_akses_riwayat'] ? 'Diizinkan' : 'Tertutup' ?>
            </div>
          </div>

          <div style="font-size: 0.9rem; margin-bottom: 14px;">
            <strong>Keluhan yang Disampaikan:</strong>
            <p class="muted" style="margin-top: 4px;"><?= nl2br(e($c['keluhan'])) ?></p>
          </div>

          <!-- Jika Disetujui & Ada Link Pertemuan Online -->
          <?php if (in_array($c['status'], ['disetujui', 'berlangsung']) && !empty($c['link_konsultasi'])): ?>
            <div style="background: var(--primary-light); padding: 14px 18px; border-radius: var(--radius-sm); margin-bottom: 14px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
              <div>
                <strong style="color: var(--primary-dark);"><i class="fa-solid fa-link"></i> Link Ruang Konsultasi:</strong>
                <div style="font-size: 0.85rem; color: var(--text-muted);"><?= e($c['link_konsultasi']) ?></div>
              </div>
              <a href="<?= e($c['link_konsultasi']) ?>" target="_blank" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-video"></i> Buka Ruang Konsultasi
              </a>
            </div>
          <?php endif; ?>

          <!-- Jika Konsultasi Telah Selesai & Memiliki Catatan Psikolog -->
          <?php if ($c['status'] === 'selesai' && !empty($c['hasil_konsultasi'])): ?>
            <div style="background: #fcfffd; border: 1.5px solid #a7f3d0; border-radius: var(--radius-sm); padding: 18px; margin-top: 14px;">
              <h4 style="color: var(--primary-dark); margin-bottom: 12px; font-size: 1rem;">
                <i class="fa-solid fa-clipboard-check text-success"></i> Catatan & Rekomendasi Hasil Konsultasi dari Psikolog:
              </h4>

              <div style="margin-bottom: 10px;">
                <strong style="font-size: 0.85rem; color: var(--text-muted);">Hasil Konsultasi / Evaluasi:</strong>
                <p style="font-size: 0.925rem; margin-top: 2px;"><?= nl2br(e($c['hasil_konsultasi'])) ?></p>
              </div>

              <div style="margin-bottom: 10px;">
                <strong style="font-size: 0.85rem; color: var(--text-muted);">Saran & Rekomendasi Psikologis:</strong>
                <p style="font-size: 0.925rem; margin-top: 2px; color: var(--primary-dark); font-weight: 500;"><?= nl2br(e($c['rekomendasi_psikolog'])) ?></p>
              </div>

              <?php if (!empty($c['tindak_lanjut'])): ?>
                <div>
                  <strong style="font-size: 0.85rem; color: var(--text-muted);">Rencana Tindak Lanjut (Follow-Up):</strong>
                  <p style="font-size: 0.925rem; margin-top: 2px;"><?= nl2br(e($c['tindak_lanjut'])) ?></p>
                </div>
              <?php endif; ?>
            </div>
          <?php endif; ?>

          <!-- Tombol Batalkan (Jika masih menunggu) -->
          <?php if ($c['status'] === 'menunggu'): ?>
            <div style="text-align: right; margin-top: 12px;">
              <form method="post" action="" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan permintaan konsultasi ini?');" style="display: inline;">
                <?= csrf_field() ?>
                <input type="hidden" name="aksi" value="batal">
                <input type="hidden" name="konsultasi_id" value="<?= $c['id'] ?>">
                <button type="submit" class="btn btn-outline btn-sm" style="color: var(--danger) !important; border-color: var(--danger);">
                  <i class="fa-solid fa-xmark"></i> Batalkan Permintaan
                </button>
              </form>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="text-center" style="padding: 40px 10px;">
      <i class="fa-regular fa-calendar-xmark" style="font-size: 2.5rem; color: var(--text-muted); margin-bottom: 12px;"></i>
      <h4>Belum Ada Riwayat Konsultasi</h4>
      <p class="muted" style="font-size: 0.9rem; margin-bottom: 18px;">
        Anda belum pernah mengajukan jadwal konsultasi dengan psikolog kami.
      </p>
      <a href="<?= url('pasien/psikolog.php') ?>" class="btn btn-primary btn-sm">
        <i class="fa-solid fa-user-doctor"></i> Temukan Psikolog
      </a>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/dashboard_footer.php'; ?>
