<?php
session_start();

// Koneksi Database
$koneksi = mysqli_connect('localhost', 'root', '', 'sistem_keuangan_mahasiswa');
if (!$koneksi) {
    die('Koneksi gagal: ' . mysqli_connect_error());
}

// Format angka ke Rupiah: 50000 -> "Rp 50.000"
function format_rupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

// Cek login & role, redirect jika tidak sesuai
function cek_login($role) {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== $role) {
        header('Location: /Sistem_Keuangan_Mahasiswa/login.php');
        exit;
    }
}
