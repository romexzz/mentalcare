<?php
/**
 * MentalCare - Configuration & Core Helper Functions
 * Backend: PHP with PDO (MySQL)
 */

// Error reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'db_mentalcare');
define('DB_USER', 'root');
define('DB_PASS', '');

define('APP_NAME', 'MentalCare');
define('APP_TAGLINE', 'Layanan Kesehatan Mental Terpadu & Terpercaya');

// Timezone setup
date_default_timezone_set('Asia/Jakarta');

// Secure Session Cookie Settings
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

/**
 * Otomatis mendeteksi BASE_URL secara cerdas
 * Mendukung akses dari:
 * - http://localhost/mentalcare/mentalcare/
 * - http://localhost/mentalcare/
 * - http://localhost:8000/
 */
if (!defined('BASE_URL')) {
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $scriptDir = dirname($scriptName);
    // Hapus folder sub (admin, psikolog, pasien, includes, config) jika diakses dari dalam
    $cleanDir = preg_replace('#/(admin|psikolog|pasien|includes|config)$#i', '', $scriptDir);
    $base = rtrim($cleanDir, '/');
    define('BASE_URL', $base);
}

/**
 * Database Connection Singleton PDO
 */
function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    }
    return $pdo;
}

/**
 * Sanitasi Output (Mencegah XSS)
 */
function e($str): string {
    return htmlspecialchars((string)($str ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * URL Generator Helper
 */
function url(string $path = ''): string {
    $cleanPath = ltrim($path, '/');
    return BASE_URL . ($cleanPath ? '/' . $cleanPath : '');
}

/**
 * Redirect Helper
 */
function redirect(string $path): void {
    if (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0) {
        header("Location: $path");
    } else {
        header('Location: ' . url($path));
    }
    exit;
}

/**
 * CSRF Protection
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

function csrf_check(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $sent = $_POST['csrf_token'] ?? $_POST['csrf'] ?? '';
        $stored = $_SESSION['csrf_token'] ?? '';
        if (!$sent || !$stored || !hash_equals($stored, $sent)) {
            http_response_code(403);
            exit('Permintaan tidak sah (Gagal validasi token CSRF). Silakan muat ulang halaman.');
        }
    }
}

/**
 * Flash Notifications
 */
function flash(?string $message = null, string $type = 'success'): ?array {
    if ($message !== null) {
        $_SESSION['flash_notification'] = [
            'message' => $message,
            'type' => $type // 'success', 'danger'/'error', 'warning', 'info'
        ];
        return null;
    }
    if (isset($_SESSION['flash_notification'])) {
        $data = $_SESSION['flash_notification'];
        unset($_SESSION['flash_notification']);
        return $data;
    }
    return null;
}

/**
 * Auth & User Context
 */
function current_user(): ?array {
    return $_SESSION['user'] ?? null;
}

/**
 * Middleware Proteksi Halaman
 */
function require_login(array $roles = []): array {
    $u = current_user();
    if (!$u) {
        flash('Silakan masuk terlebih dahulu untuk mengakses halaman ini.', 'warning');
        redirect('login.php');
    }
    if ($roles && !in_array($u['role'], $roles, true)) {
        http_response_code(403);
        // Tampilkan halaman akses ditolak dengan pesan ramah
        echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Akses Ditolak</title><link rel="stylesheet" href="'.url('assets/css/style.css').'"></head><body style="background:#f4f8f7;padding:40px;font-family:sans-serif;"><div style="max-width:500px;margin:auto;background:#fff;padding:30px;border-radius:16px;box-shadow:0 4px 15px rgba(0,0,0,0.05);text-align:center;"><h2>⛔ Akses Terbatas</h2><p>Anda tidak memiliki hak akses ke halaman ini.</p><a href="'.url('index.php').'" style="display:inline-block;padding:10px 20px;background:#3b8276;color:#fff;border-radius:8px;text-decoration:none;">Kembali ke Beranda</a></div></body></html>';
        exit;
    }
    return $u;
}

/**
 * Format Tanggal Indonesia
 */
function format_tanggal(?string $date, bool $includeTime = false): string {
    if (!$date || $date === '0000-00-00' || $date === '0000-00-00 00:00:00') {
        return '-';
    }
    $timestamp = strtotime($date);
    if (!$timestamp) return $date;

    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $d = date('j', $timestamp);
    $m = $bulan[(int)date('n', $timestamp)];
    $y = date('Y', $timestamp);

    $formatted = "$d $m $y";
    if ($includeTime) {
        $formatted .= ', ' . date('H:i', $timestamp) . ' WIB';
    }
    return $formatted;
}

/**
 * Badge Status Konsultasi
 */
function badge_status(string $status): string {
    $status = strtolower($status);
    $map = [
        'menunggu' => ['label' => 'Menunggu Konfirmasi', 'class' => 'badge-warning', 'icon' => 'fa-clock'],
        'disetujui' => ['label' => 'Disetujui', 'class' => 'badge-success', 'icon' => 'fa-check-circle'],
        'berlangsung' => ['label' => 'Sedang Berlangsung', 'class' => 'badge-info', 'icon' => 'fa-video'],
        'selesai' => ['label' => 'Selesai', 'class' => 'badge-primary', 'icon' => 'fa-circle-check'],
        'dibatalkan' => ['label' => 'Dibatalkan', 'class' => 'badge-danger', 'icon' => 'fa-times-circle'],
        'aktif' => ['label' => 'Aktif', 'class' => 'badge-success', 'icon' => 'fa-check'],
        'nonaktif' => ['label' => 'Nonaktif', 'class' => 'badge-secondary', 'icon' => 'fa-ban'],
        'terverifikasi' => ['label' => 'Terverifikasi', 'class' => 'badge-success', 'icon' => 'fa-certificate'],
    ];
    $conf = $map[$status] ?? ['label' => ucfirst($status), 'class' => 'badge-secondary', 'icon' => 'fa-info-circle'];
    return '<span class="badge ' . $conf['class'] . '"><i class="fa-solid ' . $conf['icon'] . '"></i> ' . e($conf['label']) . '</span>';
}

/**
 * Badge Kategori & Tingkat Risiko Assessment
 */
function badge_risiko(string $kategori, string $tingkat_risiko = ''): string {
    $k = strtolower($kategori);
    $r = strtolower($tingkat_risiko);

    $badgeClass = 'badge-success';
    if (strpos($k, 'tinggi') !== false || strpos($r, 'tinggi') !== false || strpos($k, 'berat') !== false) {
        $badgeClass = 'badge-danger';
    } elseif (strpos($k, 'sedang') !== false || strpos($r, 'perlu') !== false || strpos($r, 'sedang') !== false) {
        $badgeClass = 'badge-warning';
    } elseif (strpos($k, 'ringan') !== false) {
        $badgeClass = 'badge-info';
    }

    $text = e($kategori);
    if ($tingkat_risiko && $tingkat_risiko !== $kategori) {
        $text .= ' (' . e($tingkat_risiko) . ')';
    }
    return '<span class="badge ' . $badgeClass . '">' . $text . '</span>';
}

/**
 * Kalkulasi Hasil Assessment dari Database (Tabel kategori_skor_aturan)
 * Memastikan aturan skor dan rekomendasi dikontrol dari database oleh Admin!
 */
function hitung_hasil_assessment(int $kategori_id, int $total_skor): array {
    $stmt = db()->prepare('
        SELECT * FROM kategori_skor_aturan 
        WHERE kategori_id = ? AND ? >= skor_min AND ? <= skor_max
        LIMIT 1
    ');
    $stmt->execute([$kategori_id, $total_skor, $total_skor]);
    $rule = $stmt->fetch();

    if ($rule) {
        return [
            'kategori_hasil' => $rule['nama_kategori'],
            'tingkat_risiko' => $rule['tingkat_risiko'],
            'warna_badge'    => $rule['warna_badge'],
            'rekomendasi'    => $rule['saran_rekomendasi']
        ];
    }

    // Fallback jika belum diatur admin
    if ($total_skor <= 7) {
        return [
            'kategori_hasil' => 'Normal',
            'tingkat_risiko' => 'Rendah',
            'warna_badge'    => 'success',
            'rekomendasi'    => 'Kondisi Anda berada dalam batas normal. Pertahankan pola hidup sehat dan waktu istirahat yang cukup.'
        ];
    } elseif ($total_skor <= 14) {
        return [
            'kategori_hasil' => 'Ringan',
            'tingkat_risiko' => 'Sedang',
            'warna_badge'    => 'info',
            'rekomendasi'    => 'Ada gejala ringan. Lakukan aktivitas fisik ringan, relaksasi pernapasan, dan istirahat teratur.'
        ];
    } elseif ($total_skor <= 21) {
        return [
            'kategori_hasil' => 'Sedang',
            'tingkat_risiko' => 'Perlu diperhatikan',
            'warna_badge'    => 'warning',
            'rekomendasi'    => 'Gejala berada pada tingkat sedang. Disarankan mengurangi beban aktivitas dan menjadwalkan konsultasi dengan psikolog.'
        ];
    } else {
        return [
            'kategori_hasil' => 'Tinggi',
            'tingkat_risiko' => 'Tinggi',
            'warna_badge'    => 'danger',
            'rekomendasi'    => 'Tingkat risiko tergolong tinggi. Sangat dianjurkan segera berkonsultasi langsung dengan psikolog berlisensi di MentalCare.'
        ];
    }
}


/**
 * Enkripsi Jurnal Pribadi (AES-256-CBC + HMAC) & Helper Mood
 */
define('JOURNAL_KEY', 'mentalcare-secret-encryption-key-2026-secure');

function encrypt_text(string $plain): string {
    $key = hash('sha256', JOURNAL_KEY, true);
    $iv = random_bytes(16);
    $ct = openssl_encrypt($plain, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
    $mac = hash_hmac('sha256', $iv . $ct, $key, true);
    return base64_encode($iv . $mac . $ct);
}

function decrypt_text(string $blob): string {
    $key = hash('sha256', JOURNAL_KEY, true);
    $raw = base64_decode($blob, true);
    if ($raw === false || strlen($raw) < 49) return '[data tidak dapat dibaca]';
    $iv = substr($raw, 0, 16);
    $mac = substr($raw, 16, 32);
    $ct = substr($raw, 48);
    if (!hash_equals(hash_hmac('sha256', $iv . $ct, $key, true), $mac)) return '[data rusak]';
    return openssl_decrypt($ct, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv) ?: '[data tidak dapat dibaca]';
}

function mood_label(int $s): string {
    return [
        1 => 'Sangat buruk',
        2 => 'Buruk',
        3 => 'Biasa / Netral',
        4 => 'Baik',
        5 => 'Sangat baik'
    ][$s] ?? '-';
}
