<?php
require_once __DIR__ . '/../config/config.php';
$u = require_login(['psikolog']);

$pageTitle = 'Manajemen Konsultasi';
$activeMenu = 'konsultasi';

$stmtPsi = db()->prepare('SELECT id FROM psikolog WHERE user_id = ?');
$stmtPsi->execute([$u['id']]);
$psiId = (int)$stmtPsi->fetchColumn();

// Proses Aksi Update Status Konsultasi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $konsulId = (int)($_POST['konsultasi_id'] ?? 0);
    $statusAksi = trim($_POST['status_aksi'] ?? '');
    $linkKonsul = trim($_POST['link_konsultasi'] ?? '');

    $stmtCek = db()->prepare('SELECT * FROM konsultasi WHERE id = ? AND psikolog_id = ?');
    $stmtCek->execute([$konsulId, $psiId]);
    $k = $stmtCek->fetch();

    if ($k) {
        if ($statusAksi === 'disetujui') {
            db()->prepare('UPDATE konsultasi SET status = "disetujui", link_konsultasi = ? WHERE id = ?')
                ->execute([$linkKonsul ?: 'https://meet.google.com/new', $konsulId]);
            flash('Konsultasi disetujui. Tautan sesi telah diperbarui.', 'success');
        } elseif ($statusAksi === 'berlangsung') {
            db()->prepare('UPDATE konsultasi SET status = "berlangsung", link_konsultasi = ? WHERE id = ?')
                ->execute([$linkKonsul ?: $k['link_konsultasi'], $konsulId]);
            flash('Sesi konsultasi kini berstatus sedang berlangsung.', 'info');
        } elseif ($statusAksi === 'selesai') {
            db()->prepare('UPDATE konsultasi SET status = "selesai" WHERE id = ?')->execute([$konsulId]);
            flash('Status konsultasi telah diubah menjadi Selesai. Silakan isi catatan hasil sesi.', 'success');
            redirect('psikolog/catatan.php?konsultasi_id=' . $konsulId);
        } elseif ($statusAksi === 'dibatalkan') {
            db()->prepare('UPDATE konsultasi SET status = "dibatalkan" WHERE id = ?')->execute([$konsulId]);
            // Bebaskan slot jadwal jika ada
            if (!empty($k['jadwal_id'])) {
                db()->prepare('UPDATE jadwal_psikolog SET status = "tersedia" WHERE id = ?')->execute([$k['jadwal_id']]);
            }
            flash('Konsultasi telah dibatalkan.', 'warning');
        }
    }
    redirect('psikolog/konsultasi.php');
}

// Filter Status
$filterStatus = trim($_GET['status'] ?? '');
$query = '
    SELECT c.*, u.nama as nama_pasien, u.no_hp, u.email, u.jenis_kelamin, u.tanggal_lahir,
           ck.id as catatan_id
    FROM konsultasi c
    JOIN users u ON u.id = c.pasien_id
    LEFT JOIN catatan_konsultasi ck ON ck.konsultasi_id = c.id
    WHERE c.psikolog_id = ?
';
$params = [$psiId];

if ($filterStatus && in_array($filterStatus, ['menunggu', 'disetujui', 'berlangsung', 'selesai', 'dibatalkan'])) {
    $query .= ' AND c.status = ?';
    $params[] = $filterStatus;
}
$query .= ' ORDER BY c.tanggal DESC, c.waktu DESC';

$stmt = db()->prepare($query);
$stmt->execute($params);
$consultations = $stmt->fetchAll();

require __DIR__ . '/../includes/sidebar_psikolog.php';
?>

<div class="card">
  <div class="card-header" style="flex-wrap: wrap; gap: 12px;">
    <div>
      <h2 style="font-size: 1.35rem; margin: 0;">Daftar Permintaan & Jadwal Konsultasi</h2>
      <p class="muted" style="margin-top: 4px; font-size: 0.9rem;">
        Kelola persetujuan sesi, perbarui tautan pertemuan daring, dan berikan catatan konsultasi.
      </p>
    </div>
  </div>

  <!-- Filter Status Tab -->
  <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 24px;">
    <a href="<?= url('psikolog/konsultasi.php') ?>" class="btn <?= empty($filterStatus) ? 'btn-primary' : 'btn-light' ?> btn-sm">
      Semua Status
    </a>
    <a href="<?= url('psikolog/konsultasi.php?status=menunggu') ?>" class="btn <?= $filterStatus === 'menunggu' ? 'btn-primary' : 'btn-light' ?> btn-sm">
      <i class="fa-solid fa-clock"></i> Menunggu
    </a>
    <a href="<?= url('psikolog/konsultasi.php?status=disetujui') ?>" class="btn <?= $filterStatus === 'disetujui' ? 'btn-primary' : 'btn-light' ?> btn-sm">
      <i class="fa-solid fa-check-circle"></i> Disetujui
    </a>
    <a href="<?= url('psikolog/konsultasi.php?status=berlangsung') ?>" class="btn <?= $filterStatus === 'berlangsung' ? 'btn-primary' : 'btn-light' ?> btn-sm">
      <i class="fa-solid fa-video"></i> Berlangsung
    </a>
    <a href="<?= url('psikolog/konsultasi.php?status=selesai') ?>" class="btn <?= $filterStatus === 'selesai' ? 'btn-primary' : 'btn-light' ?> btn-sm">
      <i class="fa-solid fa-circle-check"></i> Selesai
    </a>
    <a href="<?= url('psikolog/konsultasi.php?status=dibatalkan') ?>" class="btn <?= $filterStatus === 'dibatalkan' ? 'btn-primary' : 'btn-light' ?> btn-sm">
      <i class="fa-solid fa-ban"></i> Dibatalkan
    </a>
  </div>

  <?php if (!empty($consultations)): ?>
    <div style="display: flex; flex-direction: column; gap: 18px;">
      <?php foreach ($consultations as $c): ?>
        <div style="border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 22px; background: #fff;">
          <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 10px; margin-bottom: 12px;">
            <div>
              <h3 style="font-size: 1.15rem; color: var(--primary-dark); margin-bottom: 4px;">
                <i class="fa-solid fa-user"></i> <?= e($c['nama_pasien']) ?>
              </h3>
              <p class="muted" style="font-size: 0.85rem; margin: 0;">
                Kontak: <?= e($c['no_hp']) ?> &bull; Email: <?= e($c['email']) ?>
              </p>
            </div>
            <div>
              <?= badge_status($c['status']) ?>
            </div>
          </div>

          <div style="display: flex; gap: 18px; font-size: 0.875rem; margin-bottom: 12px; flex-wrap: wrap; background: var(--bg-body); padding: 10px 14px; border-radius: var(--radius-sm);">
            <div><strong class="muted"><i class="fa-regular fa-calendar text-primary"></i> Tanggal:</strong> <?= format_tanggal($c['tanggal']) ?></div>
            <div><strong class="muted"><i class="fa-regular fa-clock text-primary"></i> Waktu:</strong> <?= substr($c['waktu'], 0, 5) ?> WIB</div>
            <div><strong class="muted"><i class="fa-solid fa-video text-primary"></i> Metode:</strong> <?= e($c['metode']) ?></div>
            <div>
              <strong class="muted"><i class="fa-solid fa-file-waveform text-primary"></i> Akses Riwayat:</strong> 
              <?= $c['izinkan_akses_riwayat'] ? '<span class="text-success font-weight-bold">Diizinkan Pasien</span>' : '<span class="text-danger">Tidak Diizinkan</span>' ?>
            </div>
          </div>

          <div style="font-size: 0.9rem; margin-bottom: 14px;">
            <strong>Keluhan Pasien:</strong>
            <p class="muted" style="margin-top: 4px;"><?= nl2br(e($c['keluhan'])) ?></p>
          </div>

          <!-- Form Pembaruan Aksi Status -->
          <div style="border-top: 1px solid var(--border-light); padding-top: 14px; margin-top: 12px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
              <?php if ($c['izinkan_akses_riwayat']): ?>
                <a href="<?= url('psikolog/pasien_detail.php?id=' . $c['pasien_id']) ?>" class="btn btn-outline btn-sm">
                  <i class="fa-solid fa-file-waveform"></i> Buka Riwayat Skrining Pasien
                </a>
              <?php endif; ?>

              <?php if ($c['status'] === 'selesai' || !empty($c['catatan_id'])): ?>
                <a href="<?= url('psikolog/catatan.php?konsultasi_id=' . $c['id']) ?>" class="btn btn-secondary btn-sm">
                  <i class="fa-solid fa-notes-medical"></i> <?= !empty($c['catatan_id']) ? 'Edit Catatan Sesi' : 'Tulis Catatan Sesi' ?>
                </a>
              <?php endif; ?>
            </div>

            <!-- Panel Tombol Aksi Status -->
            <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
              <?php if ($c['status'] === 'menunggu'): ?>
                <!-- Form Setujui -->
                <form method="post" action="" style="display: inline-flex; gap: 6px; align-items: center;">
                  <?= csrf_field() ?>
                  <input type="hidden" name="konsultasi_id" value="<?= $c['id'] ?>">
                  <input type="hidden" name="status_aksi" value="disetujui">
                  <input type="text" name="link_konsultasi" placeholder="Link Google Meet / Zoom" class="form-control" style="width: 220px; padding: 6px 10px; font-size: 0.825rem;" value="<?= e($c['link_konsultasi']) ?>">
                  <button type="submit" class="btn btn-primary btn-sm">
                    <i class="fa-solid fa-check"></i> Setujui
                  </button>
                </form>

                <!-- Form Tolak -->
                <form method="post" action="" onsubmit="return confirm('Tolak/batalkan permintaan konsultasi ini?');" style="display: inline;">
                  <?= csrf_field() ?>
                  <input type="hidden" name="konsultasi_id" value="<?= $c['id'] ?>">
                  <input type="hidden" name="status_aksi" value="dibatalkan">
                  <button type="submit" class="btn btn-outline btn-sm" style="color: var(--danger)!important; border-color: var(--danger);">
                    <i class="fa-solid fa-xmark"></i> Tolak
                  </button>
                </form>

              <?php elseif ($c['status'] === 'disetujui'): ?>
                <form method="post" action="" style="display: inline-flex; gap: 6px; align-items: center;">
                  <?= csrf_field() ?>
                  <input type="hidden" name="konsultasi_id" value="<?= $c['id'] ?>">
                  <input type="hidden" name="status_aksi" value="berlangsung">
                  <button type="submit" class="btn btn-secondary btn-sm">
                    <i class="fa-solid fa-play"></i> Mulai Sesi (Berlangsung)
                  </button>
                </form>

                <form method="post" action="" style="display: inline;">
                  <?= csrf_field() ?>
                  <input type="hidden" name="konsultasi_id" value="<?= $c['id'] ?>">
                  <input type="hidden" name="status_aksi" value="selesai">
                  <button type="submit" class="btn btn-primary btn-sm">
                    <i class="fa-solid fa-circle-check"></i> Tandai Selesai
                  </button>
                </form>

              <?php elseif ($c['status'] === 'berlangsung'): ?>
                <form method="post" action="" style="display: inline;">
                  <?= csrf_field() ?>
                  <input type="hidden" name="konsultasi_id" value="<?= $c['id'] ?>">
                  <input type="hidden" name="status_aksi" value="selesai">
                  <button type="submit" class="btn btn-primary btn-sm">
                    <i class="fa-solid fa-circle-check"></i> Selesaikan Sesi & Beri Catatan
                  </button>
                </form>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="text-center" style="padding: 40px 10px;">
      <i class="fa-solid fa-clipboard-check" style="font-size: 2.5rem; color: var(--text-muted); margin-bottom: 12px;"></i>
      <h4>Tidak Ada Data Konsultasi</h4>
      <p class="muted" style="font-size: 0.9rem;">
        Belum ada konsultasi yang sesuai dengan filter yang dipilih.
      </p>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/dashboard_footer.php'; ?>
