<?php
require_once '../config.php';
cek_login('admin');

$stmt = mysqli_prepare($koneksi, "
    SELECT
        COUNT(*) AS total,
        COALESCE(SUM(status = 'aktif'), 0)    AS aktif,
        COALESCE(SUM(status = 'nonaktif'), 0) AS nonaktif
    FROM pengguna
    WHERE role = 'mahasiswa'
");
mysqli_stmt_execute($stmt);
$mhs = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

$stmt = mysqli_prepare($koneksi, "SELECT COUNT(*) AS total FROM transaksi");
mysqli_stmt_execute($stmt);
$total_transaksi = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Admin</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <h1>Dashboard Admin</h1>
    <p>Halo, <?= htmlspecialchars($_SESSION['nama']) ?></p>

    <nav>
        <a href="dashboard.php">Dashboard</a> |
        <a href="pengguna.php">Pengguna</a> |
        <a href="kategori.php">Kategori</a> |
        <a href="tips.php">Tips Keuangan</a> |
        <a href="../logout.php">Logout</a>
    </nav>

    <h2>Statistik Sistem</h2>
    <div>
        <p>Total Mahasiswa Terdaftar: <b><?= (int) $mhs['total'] ?></b></p>
        <p>Akun Aktif: <b><?= (int) $mhs['aktif'] ?></b></p>
        <p>Akun Nonaktif: <b><?= (int) $mhs['nonaktif'] ?></b></p>
        <p>Total Transaksi di Sistem: <b><?= (int) $total_transaksi ?></b></p>
    </div>
</body>
</html>