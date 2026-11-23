<?php
require_once '../config.php';
cek_login('admin');

$pesan = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) $_POST['id'];

    if (isset($_POST['ubah_status'])) {
        $stmt = mysqli_prepare($koneksi,
            "UPDATE pengguna
             SET status = IF(status = 'aktif', 'nonaktif', 'aktif')
             WHERE id = ? AND role = 'mahasiswa'");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $pesan = 'Status akun berhasil diubah.';
    }

    if (isset($_POST['reset_password'])) {
        $hash = password_hash('mahasiswa123', PASSWORD_DEFAULT);
        $stmt = mysqli_prepare($koneksi,
            "UPDATE pengguna SET password = ? WHERE id = ? AND role = 'mahasiswa'");
        mysqli_stmt_bind_param($stmt, "si", $hash, $id);
        mysqli_stmt_execute($stmt);
        $pesan = 'Password berhasil direset ke mahasiswa123.';
    }
}

$stmt = mysqli_prepare($koneksi,
    "SELECT id, nama, email, status, created_at
     FROM pengguna
     WHERE role = 'mahasiswa'
     ORDER BY created_at DESC");
mysqli_stmt_execute($stmt);
$hasil = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Manajemen Pengguna</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <h1>Manajemen Pengguna</h1>
    <p><a href="dashboard.php">&larr; Kembali ke Dashboard</a></p>

    <?php if ($pesan !== '') { ?>
        <p><b><?= htmlspecialchars($pesan) ?></b></p>
    <?php } ?>

    <table border="1" cellpadding="6">
        <tr>
            <th>No</th>
            <th>Nama</th>
            <th>Email</th>
            <th>Status</th>
            <th>Terdaftar</th>
            <th>Aksi</th>
        </tr>
        <?php $no = 1; while ($row = mysqli_fetch_assoc($hasil)) { ?>
        <tr>
            <td><?= $no++ ?></td>
            <td><?= htmlspecialchars($row['nama']) ?></td>
            <td><?= htmlspecialchars($row['email']) ?></td>
            <td><?= htmlspecialchars($row['status']) ?></td>
            <td><?= htmlspecialchars($row['created_at']) ?></td>
            <td>
                <form method="post" style="display:inline">
                    <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                    <button type="submit" name="ubah_status"
                            onclick="return confirm('Yakin?')">
                        <?= $row['status'] === 'aktif' ? 'Nonaktifkan' : 'Aktifkan' ?>
                    </button>
                </form>
                <form method="post" style="display:inline">
                    <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                    <button type="submit" name="reset_password"
                            onclick="return confirm('Yakin?')">
                        Reset Password
                    </button>
                </form>
            </td>
        </tr>
        <?php } ?>
    </table>
</body>
</html>