<?php
require_once 'config.php';

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: /Sistem_Keuangan_Mahasiswa/admin/dashboard.php');
    } else {
        header('Location: /Sistem_Keuangan_Mahasiswa/mahasiswa/dashboard.php');
    }
} else {
    header('Location: /Sistem_Keuangan_Mahasiswa/login.php');
}
exit;
