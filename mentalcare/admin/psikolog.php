<?php
require_once __DIR__ . '/../config/config.php';
$u = require_login(['admin']);

$pageTitle = 'Kelola Psikolog';
$activeMenu = 'psikolog';

$action = $_GET['action'] ?? '';
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0); // user_id

// Simpan Tambah / Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'simpan') {
        $nama = trim($_POST['nama'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $no_hp = trim($_POST['no_hp'] ?? '');
        $nomor_izin = trim($_POST['nomor_izin'] ?? '');
        $spesialisasi = trim($_POST['spesialisasi'] ?? '');
        $pendidikan = trim($_POST['pendidikan'] ?? '');
        $pengalaman = trim($_POST['pengalaman'] ?? '');
        $deskripsi = trim($_POST['deskripsi'] ?? '');
        $status_verifikasi = trim($_POST['status_verifikasi'] ?? 'terverifikasi');
        $status_akun = trim($_POST['status'] ?? 'aktif');
        $password = $_POST['password'] ?? '';

        if (!$nama || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('Nama dan email wajib diisi dengan benar.', 'error');
        } else {
            $pdo = db();
            try {
                $pdo->beginTransaction();

                if ($id > 0) {
                    // Cek duplikasi email
                    $stmtCek = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
                    $stmtCek->execute([$email, $id]);
                    if ($stmtCek->fetch()) {
                        throw new Exception('Email sudah digunakan akun lain.');
                    }

                    $sqlUser = 'UPDATE users SET nama = ?, email = ?, no_hp = ?, status = ?';
                    $paramsUser = [$nama, $email, $no_hp, $status_akun];
                    if (!empty($password)) {
                        $sqlUser .= ', password = ?';
                        $paramsUser[] = password_hash($password, PASSWORD_DEFAULT);
                    }
                    $sqlUser .= ' WHERE id = ? AND role = "psikolog"';
                    $paramsUser[] = $id;

                    $pdo->prepare($sqlUser)->execute($paramsUser);

                    // Update atau insert di tabel psikolog
                    $stmtCekPsi = $pdo->prepare('SELECT id FROM psikolog WHERE user_id = ?');
                    $stmtCekPsi->execute([$id]);
                    $psiRow = $stmtCekPsi->fetch();

                    if ($psiRow) {
                        $pdo->prepare('
                            UPDATE psikolog 
                            SET nomor_izin = ?, spesialisasi = ?, pendidikan = ?, pengalaman = ?, deskripsi = ?, status_verifikasi = ?
                            WHERE user_id = ?
                        ')->execute([$nomor_izin, $spesialisasi, $pendidikan, $pengalaman, $deskripsi, $status_verifikasi, $id]);
                    } else {
                        $pdo->prepare('
                            INSERT INTO psikolog (user_id, nomor_izin, spesialisasi, pendidikan, pengalaman, deskripsi, status_verifikasi)
                            VALUES (?, ?, ?, ?, ?, ?, ?)
                        ')->execute([$id, $nomor_izin, $spesialisasi, $pendidikan, $pengalaman, $deskripsi, $status_verifikasi]);
                    }

                    $pdo->commit();
                    flash('Data psikolog berhasil diperbarui.', 'success');
                    redirect('admin/psikolog.php');

                } else {
                    // Tambah baru
                    $stmtCek = $pdo->prepare('SELECT id FROM users WHERE email = ?');
                    $stmtCek->execute([$email]);
                    if ($stmtCek->fetch()) {
                        throw new Exception('Email sudah terdaftar.');
                    }

                    $pwHash = password_hash(!empty($password) ? $password : 'Psikolog123!', PASSWORD_DEFAULT);
                    $pdo->prepare('
                        INSERT INTO users (nama, email, password, role, no_hp, status, consent_at)
                        VALUES (?, ?, ?, "psikolog", ?, ?, NOW())
                    ')->execute([$nama, $email, $pwHash, $no_hp, $status_akun]);

                    $newUserId = $pdo->lastInsertId();

                    $pdo->prepare('
                        INSERT INTO psikolog (user_id, nomor_izin, spesialisasi, pendidikan, pengalaman, deskripsi, status_verifikasi)
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ')->execute([$newUserId, $nomor_izin, $spesialisasi, $pendidikan, $pengalaman, $deskripsi, $status_verifikasi]);

                    $pdo->commit();
                    flash('Psikolog baru berhasil ditambahkan.', 'success');
                    redirect('admin/psikolog.php');
                }

            } catch (Exception $e) {
                $pdo->rollBack();
                flash('Gagal menyimpan psikolog: ' . $e->getMessage(), 'error');
            }
        }
    } elseif ($aksi === 'toggle_status') {
        $stmtCurr = db()->prepare('SELECT status FROM users WHERE id = ? AND role = "psikolog"');
        $stmtCurr->execute([$id]);
        $currStatus = $stmtCurr->fetchColumn();

        if ($currStatus) {
            $newStatus = ($currStatus === 'aktif') ? 'nonaktif' : 'aktif';
            db()->prepare('UPDATE users SET status = ? WHERE id = ?')->execute([$newStatus, $id]);
            flash("Status psikolog diubah menjadi $newStatus.", 'info');
        }
        redirect('admin/psikolog.php');
    } elseif ($aksi === 'hapus') {
        db()->prepare('DELETE FROM users WHERE id = ? AND role = "psikolog"')->execute([$id]);
        flash('Data psikolog berhasil dihapus.', 'info');
        redirect('admin/psikolog.php');
    }
}

// Data edit
$editPsi = null;
if ($action === 'edit' && $id > 0) {
    $stmt = db()->prepare('
        SELECT u.*, p.nomor_izin, p.spesialisasi, p.pendidikan, p.pengalaman, p.deskripsi, p.status_verifikasi
        FROM users u
        LEFT JOIN psikolog p ON p.user_id = u.id
        WHERE u.id = ? AND u.role = "psikolog"
    ');
    $stmt->execute([$id]);
    $editPsi = $stmt->fetch();
}

// Ambil list psikolog
$stmtList = db()->query('
    SELECT u.*, p.nomor_izin, p.spesialisasi, p.pendidikan, p.pengalaman, p.status_verifikasi
    FROM users u
    LEFT JOIN psikolog p ON p.user_id = u.id
    WHERE u.role = "psikolog"
    ORDER BY u.id DESC
');
$psikologList = $stmtList->fetchAll();

require __DIR__ . '/../includes/sidebar_admin.php';
?>

<div class="card">
  <div class="card-header" style="flex-wrap: wrap; gap: 12px;">
    <div>
      <h2 style="font-size: 1.35rem; margin: 0;">Manajemen Psikolog Profesional</h2>
      <p class="muted" style="margin-top: 4px; font-size: 0.9rem;">
        Kelola data akun mitra psikolog klinis, status verifikasi izin praktik, dan spesialisasi.
      </p>
    </div>
    <div>
      <button class="btn btn-primary btn-sm" onclick="document.getElementById('modalFormPsikolog').style.display='block'">
        <i class="fa-solid fa-user-plus"></i> Tambah Psikolog Baru
      </button>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>No</th>
          <th>Nama & Kredensial</th>
          <th>Email & WhatsApp</th>
          <th>Spesialisasi & Pengalaman</th>
          <th>Status Verifikasi</th>
          <th>Status Akun</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($psikologList)): ?>
          <?php foreach ($psikologList as $idx => $psi): ?>
            <tr>
              <td><?= $idx + 1 ?></td>
              <td>
                <strong style="color: var(--primary-dark); font-size: 1rem;"><?= e($psi['nama']) ?></strong><br>
                <span class="badge badge-light" style="font-size: 0.75rem;">
                  <i class="fa-solid fa-certificate"></i> <?= e($psi['nomor_izin'] ?: 'Belum Ada SIPP') ?>
                </span>
              </td>
              <td>
                <div><i class="fa-solid fa-envelope text-primary"></i> <?= e($psi['email']) ?></div>
                <div class="muted" style="font-size: 0.8rem;"><i class="fa-brands fa-whatsapp text-success"></i> <?= e($psi['no_hp'] ?: '-') ?></div>
              </td>
              <td>
                <div><strong><?= e($psi['spesialisasi'] ?: '-') ?></strong></div>
                <small class="muted">Pengalaman: <?= e($psi['pengalaman'] ?: '-') ?></small>
              </td>
              <td>
                <?php if ($psi['status_verifikasi'] === 'terverifikasi'): ?>
                  <span class="badge badge-success"><i class="fa-solid fa-check-double"></i> Terverifikasi</span>
                <?php elseif ($psi['status_verifikasi'] === 'ditolak'): ?>
                  <span class="badge badge-danger">Ditolak</span>
                <?php else: ?>
                  <span class="badge badge-warning">Menunggu</span>
                <?php endif; ?>
              </td>
              <td><?= badge_status($psi['status']) ?></td>
              <td>
                <div style="display: flex; gap: 6px;">
                  <a href="<?= url('admin/psikolog.php?action=edit&id=' . $psi['id']) ?>" class="btn btn-light btn-sm" title="Edit">
                    <i class="fa-solid fa-pen-to-square"></i>
                  </a>

                  <!-- Toggle Aktif/Nonaktif -->
                  <form method="post" action="" style="display: inline;">
                    <?= csrf_field() ?>
                    <input type="hidden" name="aksi" value="toggle_status">
                    <input type="hidden" name="id" value="<?= $psi['id'] ?>">
                    <button type="submit" class="btn btn-outline btn-sm" title="<?= $psi['status'] === 'aktif' ? 'Nonaktifkan' : 'Aktifkan' ?>">
                      <i class="fa-solid <?= $psi['status'] === 'aktif' ? 'fa-ban text-danger' : 'fa-check text-success' ?>"></i>
                    </button>
                  </form>

                  <!-- Hapus -->
                  <form method="post" action="" onsubmit="return confirm('Hapus akun psikolog ini?');" style="display: inline;">
                    <?= csrf_field() ?>
                    <input type="hidden" name="aksi" value="hapus">
                    <input type="hidden" name="id" value="<?= $psi['id'] ?>">
                    <button type="submit" class="btn btn-outline btn-sm" style="color:var(--danger)!important; border-color:var(--danger);" title="Hapus">
                      <i class="fa-solid fa-trash"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="7" class="text-center muted" style="padding: 24px;">Belum ada data psikolog.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal Form Tambah / Edit Psikolog -->
<div id="modalFormPsikolog" style="display: <?= $editPsi ? 'block' : 'none' ?>; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; overflow-y: auto;">
  <div style="max-width: 650px; margin: 40px auto; background: #fff; border-radius: var(--radius-md); padding: 30px; box-shadow: var(--shadow-lg);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
      <h3 style="margin: 0;"><?= $editPsi ? 'Edit Data Psikolog' : 'Tambah Psikolog Baru' ?></h3>
      <button type="button" onclick="document.getElementById('modalFormPsikolog').style.display='none'" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
    </div>

    <form method="post" action="">
      <?= csrf_field() ?>
      <input type="hidden" name="aksi" value="simpan">
      <input type="hidden" name="id" value="<?= (int)($editPsi['id'] ?? 0) ?>">

      <div class="form-group">
        <label class="form-label">Nama Lengkap & Gelar <span class="text-danger">*</span></label>
        <input type="text" name="nama" class="form-control" required placeholder="Contoh: Sarah Amanda, M.Psi., Psikolog" value="<?= e($editPsi['nama'] ?? '') ?>">
      </div>

      <div class="form-row">
        <div class="form-group" style="flex: 1;">
          <label class="form-label">Email <span class="text-danger">*</span></label>
          <input type="email" name="email" class="form-control" required placeholder="psikolog@mentalcare.test" value="<?= e($editPsi['email'] ?? '') ?>">
        </div>
        <div class="form-group" style="flex: 1;">
          <label class="form-label">Nomor WhatsApp / HP</label>
          <input type="tel" name="no_hp" class="form-control" placeholder="08123456789" value="<?= e($editPsi['no_hp'] ?? '') ?>">
        </div>
      </div>

      <div class="form-row">
        <div class="form-group" style="flex: 1;">
          <label class="form-label">Nomor Izin Praktik (SIPP / STR)</label>
          <input type="text" name="nomor_izin" class="form-control" placeholder="SIPP-1234-PSI-2022" value="<?= e($editPsi['nomor_izin'] ?? '') ?>">
        </div>
        <div class="form-group" style="flex: 1;">
          <label class="form-label">Lama Pengalaman</label>
          <input type="text" name="pengalaman" class="form-control" placeholder="Contoh: 5 Tahun" value="<?= e($editPsi['pengalaman'] ?? '') ?>">
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Spesialisasi Klinis</label>
        <input type="text" name="spesialisasi" class="form-control" placeholder="Contoh: Stres Kerja, Depresi, Kecemasan, Remaja & Dewasa Muda" value="<?= e($editPsi['spesialisasi'] ?? '') ?>">
      </div>

      <div class="form-group">
        <label class="form-label">Latar Belakang Pendidikan</label>
        <input type="text" name="pendidikan" class="form-control" placeholder="Contoh: S2 Magister Profesi Psikologi Klinis - Universitas Indonesia" value="<?= e($editPsi['pendidikan'] ?? '') ?>">
      </div>

      <div class="form-group">
        <label class="form-label">Bio Singkat</label>
        <textarea name="deskripsi" class="form-control" rows="3" placeholder="Deskripsikan pendekatan konseling..."><?= e($editPsi['deskripsi'] ?? '') ?></textarea>
      </div>

      <div class="form-row">
        <div class="form-group" style="flex: 1;">
          <label class="form-label">Status Verifikasi</label>
          <select name="status_verifikasi" class="form-control">
            <option value="terverifikasi" <?= ($editPsi['status_verifikasi'] ?? 'terverifikasi') === 'terverifikasi' ? 'selected' : '' ?>>Terverifikasi</option>
            <option value="menunggu" <?= ($editPsi['status_verifikasi'] ?? '') === 'menunggu' ? 'selected' : '' ?>>Menunggu</option>
            <option value="ditolak" <?= ($editPsi['status_verifikasi'] ?? '') === 'ditolak' ? 'selected' : '' ?>>Ditolak</option>
          </select>
        </div>
        <div class="form-group" style="flex: 1;">
          <label class="form-label">Status Akun</label>
          <select name="status" class="form-control">
            <option value="aktif" <?= ($editPsi['status'] ?? 'aktif') === 'aktif' ? 'selected' : '' ?>>Aktif</option>
            <option value="nonaktif" <?= ($editPsi['status'] ?? '') === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
          </select>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Password <?= $editPsi ? '(Biarkan kosong jika tidak diubah)' : '<span class="text-danger">* (Default: Psikolog123!)</span>' ?></label>
        <input type="password" name="password" class="form-control" placeholder="<?= $editPsi ? 'Password baru' : 'Psikolog123!' ?>">
      </div>

      <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 24px;">
        <button type="button" class="btn btn-light" onclick="document.getElementById('modalFormPsikolog').style.display='none'">Batal</button>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Simpan Data Psikolog</button>
      </div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/../includes/dashboard_footer.php'; ?>
