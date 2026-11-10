<?php
require_once 'config/config.php';
$u = require_login(['pasien']);

$pageTitle = 'Mood Tracker - Pelacak Suasana Hati';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $skor = (int)($_POST['skor'] ?? 0);
    $cat = mb_substr(trim($_POST['catatan'] ?? ''), 0, 255);

    if ($skor < 1 || $skor > 5) {
        flash('Pilih skor mood Anda.', 'error');
    } else {
        db()->prepare('
            INSERT INTO mood_logs (user_id, skor_mood, catatan, tanggal) 
            VALUES (?, ?, ?, CURDATE())
        ')->execute([$u['id'], $skor, $cat]);

        flash('Catatan suasana hati hari ini berhasil disimpan.', 'success');
    }
    redirect('mood.php');
}

// Tren 14 hari
$s = db()->prepare('
    SELECT tanggal, ROUND(AVG(skor_mood), 1) AS rata 
    FROM mood_logs 
    WHERE user_id = ? AND tanggal >= DATE_SUB(CURDATE(), INTERVAL 13 DAY) 
    GROUP BY tanggal 
    ORDER BY tanggal
');
$s->execute([$u['id']]);
$chart = $s->fetchAll();

// 10 riwayat terbaru
$h = db()->prepare('SELECT * FROM mood_logs WHERE user_id = ? ORDER BY id DESC LIMIT 10');
$h->execute([$u['id']]);
$hist = $h->fetchAll();

require 'includes/header.php';
?>

<div class="container container-narrow" style="padding-top: 30px; margin-bottom: 60px;">
  <div style="margin-bottom: 24px;">
    <h2>🌿 Mood Tracker</h2>
    <p class="muted">Catat dan pantau fluktuasi suasana hati harianmu untuk mengenali pola emosi diri.</p>
  </div>

  <div class="card" style="padding: 28px;">
    <h3 style="margin-bottom: 14px; font-size: 1.25rem;">Bagaimana perasaanmu hari ini?</h3>

    <form method="post" action="">
      <?= csrf_field() ?>

      <div class="mood-opts" style="margin-bottom: 18px; display: flex; gap: 10px; flex-wrap: wrap;">
        <?php 
          $emojis = [
            1 => ['emo' => '😞', 'label' => 'Sangat Buruk'],
            2 => ['emo' => '😕', 'label' => 'Buruk'],
            3 => ['emo' => '😐', 'label' => 'Biasa / Netral'],
            4 => ['emo' => '🙂', 'label' => 'Baik'],
            5 => ['emo' => '😄', 'label' => 'Sangat Baik']
          ];
        ?>
        <?php foreach ($emojis as $v => $item): ?>
          <label style="background: var(--bg-body); padding: 10px 16px; border-radius: var(--radius-sm); border: 1.5px solid var(--border-color); cursor: pointer; display: flex; align-items: center; gap: 8px;">
            <input type="radio" name="skor" value="<?= $v ?>" required style="width: auto;">
            <span style="font-size: 1.4rem;"><?= $item['emo'] ?></span>
            <span style="font-weight: 600; font-size: 0.9rem;"><?= $item['label'] ?></span>
          </label>
        <?php endforeach; ?>
      </div>

      <div class="form-group">
        <label class="form-label">Catatan Singkat (Opsional)</label>
        <input type="text" name="catatan" class="form-control" maxlength="255" placeholder="Contoh: Merasa lebih tenang setelah berjalan pagi...">
      </div>

      <button type="submit" class="btn btn-primary">
        <i class="fa-solid fa-floppy-disk"></i> Simpan Catatan Mood
      </button>
    </form>
  </div>

  <!-- Grafik Tren 14 Hari -->
  <div class="card" style="padding: 28px;">
    <h3 style="margin-bottom: 14px; font-size: 1.25rem;">
      <i class="fa-solid fa-chart-line text-primary"></i> Tren Suasana Hati (14 Hari Terakhir)
    </h3>
    <?php if (!empty($chart)): ?>
      <div style="height: 220px; position: relative;">
        <canvas id="moodChart"></canvas>
      </div>
    <?php else: ?>
      <p class="muted text-center" style="padding: 24px 0;">Belum ada catatan mood yang tersimpan.</p>
    <?php endif; ?>
  </div>

  <!-- Riwayat Terbaru -->
  <div class="card">
    <div class="card-header">
      <div class="card-title">
        <i class="fa-solid fa-clock-rotate-left text-secondary"></i> Riwayat Mood Terkini
      </div>
    </div>

    <?php if (!empty($hist)): ?>
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>Tanggal</th>
              <th>Suasana Hati</th>
              <th>Catatan</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($hist as $r): ?>
              <tr>
                <td><strong><?= format_tanggal($r['tanggal']) ?></strong></td>
                <td>
                  <?php 
                    $s = (int)$r['skor_mood'];
                    $emoIcon = [1=>'😞', 2=>'😕', 3=>'😐', 4=>'🙂', 5=>'😄'][$s] ?? '😐';
                  ?>
                  <span style="font-size: 1.1rem; margin-right: 4px;"><?= $emoIcon ?></span>
                  <strong><?= e(mood_label($s)) ?></strong>
                </td>
                <td><span class="muted"><?= e($r['catatan'] ?: '-') ?></span></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <p class="muted text-center" style="padding: 20px 0;">Belum ada riwayat mood.</p>
    <?php endif; ?>
  </div>
</div>

<?php if (!empty($chart)): ?>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
  new Chart(document.getElementById('moodChart'), {
    type: 'line',
    data: {
      labels: <?= json_encode(array_column($chart, 'tanggal')) ?>,
      datasets: [{
        label: 'Skor Mood (1-5)',
        data: <?= json_encode(array_map('floatval', array_column($chart, 'rata'))) ?>,
        borderColor: '#3b8276',
        backgroundColor: 'rgba(59, 130, 118, 0.15)',
        fill: true,
        tension: 0.35,
        pointRadius: 4,
        pointBackgroundColor: '#3b8276'
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        y: {
          min: 1,
          max: 5,
          ticks: {
            stepSize: 1,
            callback: function(val) {
              return {1:'😞 Sangat Buruk', 2:'😕 Buruk', 3:'😐 Netral', 4:'🙂 Baik', 5:'😄 Sangat Baik'}[val] || val;
            }
          }
        }
      }
    }
  });
  </script>
<?php endif; ?>

<?php require 'includes/footer.php'; ?>
