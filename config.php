<?php
session_start();

// Koneksi Database (mendukung password 'root' atau tanpa password)
$koneksi = @mysqli_connect('localhost', 'root', '', 'sistem_keuangan_mahasiswa');
if (!$koneksi) {
    $koneksi = @mysqli_connect('localhost', 'root', '', 'sistem_keuangan_mahasiswa');
}
if (!$koneksi) {
    die('Koneksi gagal: ' . mysqli_connect_error());
}

// Base path otomatis (kompatibel localhost/Sistem_Keuangan_Mahasiswa maupun Virtual Host *.test)
$base_path = (stripos($_SERVER['SCRIPT_NAME'], '/Sistem_Keuangan_Mahasiswa') !== false) 
    ? '/Sistem_Keuangan_Mahasiswa' 
    : '';

// Format angka ke Rupiah: 50000 -> "Rp 50.000"
function format_rupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

// Cek login & role, redirect jika tidak sesuai
function cek_login($role) {
    global $base_path;
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== $role) {
        header("Location: {$base_path}/login.php");
        exit;
    }
}
