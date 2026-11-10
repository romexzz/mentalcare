<?php
require_once __DIR__ . '/../config/config.php';
$u = require_login(['admin']);

$pageTitle = 'Kelola Pasien';
$activeMenu = 'pasien';

$action = $_GET['action'] ?? '';
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

// 1. Tambah / Edit Pasien
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'simpan') {
        $nama = trim($_POST['nama'] ?? '');
        $identitas_nik = trim($_POST['identitas_nik'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $no_hp = trim($_POST['no_hp'] ?? '');
        $jenis_kelamin = trim($_POST['jenis_kelamin'] ?? '');
        $tanggal_lahir = trim($_POST['tanggal_lahir'] ?? '');
        $status = trim($_POST['status'] ?? 'aktif');
        $password = $_POST['password'] ?? '';

        if (!$nama || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('Nama dan email yang valid wajib diisi.', 'error');
        } else {
            if ($id > 0) {
                // Update
                $stmtCheck = db()->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
                $stmtCheck->execute([$email, $id]);
                if ($stmtCheck->fetch()) {
                    flash('Email sudah dipakai akun lain.', 'error');
                } else {
                    $sql = 'UPDATE users SET nama = ?, identitas_nik = ?, email = ?, no_hp = ?, jenis_kelamin = ?, tanggal_lahir = ?, status = ?';
                    $params = [$nama, $identitas_nik, $email, $no_hp, $jenis_kelamin, $tanggal_lahir ?: null, $status];

                    if (!empty($password)) {
                        $sql .= ', password = ?';
                        $params[] = password_hash($password, PASSWORD_DEFAULT);
                    }
                    $sql .= ' WHERE id = ? AND role = "pasien"';
                    $params[] = $id;

                    db()->prepare($sql)->execute($params);
                    flash('Data pasien berhasil diperbarui.', 'success');
                    redirect('admin/pasien.php');
                }
            } else {
                // Tambah baru
                $stmtCheck = db()->prepare('SELECT id FROM users WHERE email = ?');
                $stmtCheck->execute([$email]);
                if ($stmtCheck->fetch()) {
                    flash('Email sudah terdaftar.', 'error');
                } else {
                    $pwHash = password_hash(!empty($password) ? $password : 'Pasien123!', PASSWORD_DEFAULT);
                    db()->prepare('
                        INSERT INTO users (nama, identitas_nik, email, password, role, no_hp, jenis_kelamin, tanggal_lahir, status, consent_at)
                        VALUES (?, ?, ?, ?, "pasien", ?, ?, ?, ?, NOW())
                    ')->execute([$nama, $identitas_nik, $email, $pwHash, $no_hp, $jenis_kelamin, $tanggal_lahir ?: null, $status]);

                    flash('Pasien baru berhasil ditambahkan.', 'success');
                    redirect('admin/pasien.php');
                }
            }
        }
    } elseif ($aksi === 'toggle_status') {
        $stmtCurr = db()->prepare('SELECT status FROM users WHERE id = ? AND role = "pasien"');
        $stmtCurr->execute([$id]);
        $currStatus = $stmtCurr->fetchColumn();

        if ($currStatus) {
            $newStatus = ($currStatus === 'aktif') ? 'nonaktif' : 'aktif';
            db()->prepare('UPDATE users SET status = ? WHERE id = ?')->execute([$newStatus, $id]);
            flash("Status pasien berhasil diubah menjadi $newStatus.", 'info');
        }
        redirect('admin/pasien.php');
    } elseif ($aksi === 'hapus') {
        db()->prepare('DELETE FROM users WHERE id = ? AND role = "pasien"')->execute([$id]);
        flash('Data pasien berhasil dihapus beserta data pemeriksaannya.', 'info');
        redirect('admin/pasien.php');
    }
}

// Ambil data untuk mode edit
$editPasien = null;
if ($action === 'edit' && $id > 0) {
    $stmt = db()->prepare('SELECT * FROM users WHERE id = ? AND role = "pasien"');
    $stmt->execute([$id]);
    $editPasien = $stmt->fetch();
}

// Ambil semua pasien
$cari = trim($_GET['cari'] ?? '');
$query = 'SELECT * FROM users WHERE role = "pasien"';
$params = [];
if ($cari) {
    $query .= ' AND (nama LIKE ? OR email LIKE ? OR identitas_nik LIKE ? OR no_hp LIKE ?)';
    $params = ["%$cari%", "%$cari%", "%$cari%", "%$cari%"];
}
$query .= ' ORDER BY id DESC';
$stmtList = db()->prepare($query);
$stmtList->execute($params);
$pasienList = $stmtList->fetchAll();

require __DIR__ . '/../includes/sidebar_admin.php';
?>

<div class="card">
  <div class="card-header" style="flex-wrap: wrap; gap: 12px;">
    <div>
      <h2 style="font-size: 1.35rem; margin: 0;">Manajemen Data Pasien</h2>
      <p class="muted" style="margin-top: 4px; font-size: 0.9rem;">
        Kelola akun pengguna pasien, tambah baru, edit profil, dan atur status aktif.
      </p>
    </div>
    <div>
      <button class="btn btn-primary btn-sm" onclick="document.getElementById('modalFormPasien').style.display = 'block'">
        <i class="fa-solid fa-user-plus"></i> Tambah Pasien Baru
      </button>
    </div>
  </div>

  <!-- Pencarian -->
  <form method="get" action="" style="display: flex; gap: 10px; margin-bottom: 20px;">
    <input type="text" name="cari" class="form-control" style="max-width: 300px;" placeholder="Cari nama, email, NIK..." value="<?= e($cari) ?>">
    <button type="submit" class="btn btn-secondary btn-sm"><i class="fa-solid fa-search"></i> Cari</button>
    <?php if ($cari): ?>
      <a href="<?= url('admin/pasien.php') ?>" class="btn btn-light btn-sm"><i class="fa-solid fa-rotate-left"></i> Reset</a>
    <?php endif; ?>
  </form>

  <!-- Tabel Pasien -->
  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>No</th>
          <th>Nama Lengkap</th>
          <th>NIK / NIM</th>
          <th>Email & WhatsApp</th>
          <th>Gender</th>
          <th>Status</th>
          <th>Daftar</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($pasienList)): ?>
          <?php foreach ($pasienList as $idx => $p): ?>
            <tr>
              <td><?= $idx + 1 ?></td>
              <td><strong><?= e($p['nama']) ?></strong></td>
              <td><?= e($p['identitas_nik'] ?: '-') ?></td>
              <td>
                <div><i class="fa-solid fa-envelope text-primary"></i> <?= e($p['email']) ?></div>
                <div class="muted" style="font-size: 0.8rem;"><i class="fa-brands fa-whatsapp text-success"></i> <?= e($p['no_hp'] ?: '-') ?></div>
              </td>
              <td><?= $p['jenis_kelamin'] === 'L' ? 'L' : ($p['jenis_kelamin'] === 'P' ? 'P' : '-') ?></td>
              <td><?= badge_status($p['status']) ?></td>
              <td><small class="muted"><?= format_tanggal($p['created_at']) ?></small></td>
              <td>
                <div style="display: flex; gap: 6px;">
                  <a href="<?= url('admin/pasien.php?action=edit&id=' . $p['id']) ?>" class="btn btn-light btn-sm" title="Edit">
                    <i class="fa-solid fa-pen-to-square"></i>
                  </a>

                  <!-- Toggle Aktif/Nonaktif -->
                  <form method="post" action="" style="display: inline;">
                    <?= csrf_field() ?>
                    <input type="hidden" name="aksi" value="toggle_status">
                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                    <button type="submit" class="btn btn-outline btn-sm" title="<?= $p['status'] === 'aktif' ? 'Nonaktifkan' : 'Aktifkan' ?>">
                      <i class="fa-solid <?= $p['status'] === 'aktif' ? 'fa-ban text-danger' : 'fa-check text-success' ?>"></i>
                    </button>
                  </form>

                  <!-- Hapus -->
                  <form method="post" action="" onsubmit="return confirm('Hapus pasien ini beserta semua riwayat pemeriksaannya?');" style="display: inline;">
                    <?= csrf_field() ?>
                    <input type="hidden" name="aksi" value="hapus">
                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                    <button type="submit" class="btn btn-outline btn-sm" style="color:var(--danger)!important; border-color:var(--danger);" title="Hapus">
                      <i class="fa-solid fa-trash"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="8" class="text-center muted" style="padding: 24px;">Tidak ada data pasien yang ditemukan.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal Form Tambah / Edit Pasien -->
<div id="modalFormPasien" style="display: <?= $editPasien ? 'block' : 'none' ?>; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; overflow-y: auto;">
  <div style="max-width: 580px; margin: 40px auto; background: #fff; border-radius: var(--radius-md); padding: 30px; box-shadow: var(--shadow-lg);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
      <h3 style="margin: 0;"><?= $editPasien ? 'Edit Data Pasien' : 'Tambah Pasien Baru' ?></h3>
      <button type="button" onclick="document.getElementById('modalFormPasien').style.display='none'" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
    </div>

    <form method="post" action="">
      <?= csrf_field() ?>
      <input type="hidden" name="aksi" value="simpan">
      <input type="hidden" name="id" value="<?= (int)($editPasien['id'] ?? 0) ?>">

      <div class="form-group">
        <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
        <input type="text" name="nama" class="form-control" required value="<?= e($editPasien['nama'] ?? '') ?>">
      </div>

      <div class="form-row">
        <div class="form-group" style="flex: 1;">
          <label class="form-label">NIK / NIM</label>
          <input type="text" name="identitas_nik" class="form-control" value="<?= e($editPasien['identitas_nik'] ?? '') ?>">
        </div>
        <div class="form-group" style="flex: 1;">
          <label class="form-label">Jenis Kelamin</label>
          <select name="jenis_kelamin" class="form-control">
            <option value="">-- Pilih --</option>
            <option value="L" <?= ($editPasien['jenis_kelamin'] ?? '') === 'L' ? 'selected' : '' ?>>Laki-laki</option>
            <option value="P" <?= ($editPasien['jenis_kelamin'] ?? '') === 'P' ? 'selected' : '' ?>>Perempuan</option>
          </select>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group" style="flex: 1;">
          <label class="form-label">Email <span class="text-danger">*</span></label>
          <input type="email" name="email" class="form-control" required value="<?= e($editPasien['email'] ?? '') ?>">
        </div>
        <div class="form-group" style="flex: 1;">
          <label class="form-label">Nomor WhatsApp / HP</label>
          <input type="tel" name="no_hp" class="form-control" value="<?= e($editPasien['no_hp'] ?? '') ?>">
        </div>
      </div>

      <div class="form-row">
        <div class="form-group" style="flex: 1;">
          <label class="form-label">Tanggal Lahir</label>
          <input type="date" name="tanggal_lahir" class="form-control" value="<?= e($editPasien['tanggal_lahir'] ?? '') ?>">
        </div>
        <div class="form-group" style="flex: 1;">
          <label class="form-label">Status Akun</label>
          <select name="status" class="form-control">
            <option value="aktif" <?= ($editPasien['status'] ?? 'aktif') === 'aktif' ? 'selected' : '' ?>>Aktif</option>
            <option value="nonaktif" <?= ($editPasien['status'] ?? '') === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
          </select>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Password <?= $editPasien ? '(Biarkan kosong bila tidak ingin mengganti)' : '<span class="text-danger">* (Default: Pasien123!)</span>' ?></label>
        <input type="password" name="password" class="form-control" placeholder="<?= $editPasien ? 'Masukkan password baru jika ingin mengubah' : 'Pasien123!' ?>">
      </div>

      <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 24px;">
        <button type="button" class="btn btn-light" onclick="document.getElementById('modalFormPasien').style.display='none'">Batal</button>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Simpan Data Pasien</button>
      </div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/../includes/dashboard_footer.php'; ?>
