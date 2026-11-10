<?php
require_once 'config/config.php';
$u = require_login();

if ($u['role'] === 'admin') {
    redirect('admin/index.php');
} elseif ($u['role'] === 'psikolog') {
    redirect('psikolog/index.php');
} else {
    redirect('pasien/index.php');
}
