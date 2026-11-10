<?php
require_once 'config/config.php';
$id = (int)($_GET['id'] ?? 0);
redirect('pasien/kuisioner.php?id=' . $id);
