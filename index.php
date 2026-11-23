<?php
require_once 'config.php';

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: {$base_path}/admin/dashboard.php");
    } else {
        header("Location: {$base_path}/mahasiswa/dashboard.php");
    }
} else {
    header("Location: {$base_path}/login.php");
}
exit;
