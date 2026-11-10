<?php
require_once 'config/config.php';
$id = (int)($_GET['id'] ?? 0);
redirect('artikel_detail.php?id=' . $id);
