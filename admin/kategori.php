<?php
require_once '../config.php';
cek_login('admin');

$pesan = '';
$edit  = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['tambah'])) {
        $nama  = trim($_POST['nama_kategori']);
        $jenis = $_POST['jenis'];
        if ($nama === '' || !in_array($jenis, ['pemasukan', 'pengeluaran'])) {
            $pesan = 'Data tidak valid.';
        } else {
            $stmt = mysqli_prepare($koneksi, "INSERT INTO kategori (nama_kategori, jenis) VALUES (?, ?)");
            mysqli_stmt_bind_param($stmt, "ss", $nama, $jenis);
            mysqli_stmt_execute($stmt);
            $pesan = 'Kategori berhasil ditambahkan.';
        }
    }

    if (isset($_POST['ubah'])) {
        $id    = (int) $_POST['id'];
        $nama  = trim($_POST['nama_kategori']);
        $jenis = $_POST['jenis'];
        if ($nama === '' || !in_array($jenis, ['pemasukan', 'pengeluaran'])) {
            $pesan = 'Data tidak valid.';
        } else {
            $stmt = mysqli_prepare($koneksi, "UPDATE kategori SET nama_kategori = ?, jenis = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "ssi", $nama, $jenis, $id);
            mysqli_stmt_execute($stmt);
            $pesan = 'Kategori berhasil diubah.';
        }
    }

    if (isset($_POST['hapus'])) {
        $id = (int) $_POST['id'];
        try {
            $stmt = mysqli_prepare($koneksi, "DELETE FROM kategori WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "i", $id);
            if (mysqli_stmt_execute($stmt)) {
                $pesan = 'Kategori berhasil dihapus.';
            } else {
                $pesan = 'Kategori tidak bisa dihapus karena masih dipakai.';
            }
        } catch (mysqli_sql_exception $e) {
            $pesan = 'Kategori tidak bisa dihapus karena masih dipakai.';
        }
    }
}

if (isset($_GET['edit'])) {
    $id = (int) $_GET['edit'];
    $stmt = mysqli_prepare($koneksi, "SELECT id, nama_kategori, jenis FROM kategori WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $edit = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
}

$stmt = mysqli_prepare($koneksi, "SELECT id, nama_kategori, jenis FROM kategori ORDER BY jenis, nama_kategori");
mysqli_stmt_execute($stmt);
$hasil = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Kategori</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <h1>Kelola Kategori</h1>
    <p><a href="dashboard.php">&larr; Kembali ke Dashboard</a></p>

    <?php if ($pesan !== '') { ?>
        <p><b><?= htmlspecialchars($pesan) ?></b></p>
    <?php } ?>

    <h2><?= $edit ? 'Ubah Kategori' : 'Tambah Kategori' ?></h2>
    <form method="post">
        <?php if ($edit) { ?>
            <input type="hidden" name="id" value="<?= (int) $edit['id'] ?>">
        <?php } ?>

        <label>Nama Kategori</label><br>
        <input type="text" name="nama_kategori" required
            value="<?= $edit ? htmlspecialchars($edit['nama_kategori']) : '' ?>"><br><br>

        <label>Jenis</label><br>
        <select name="jenis">
            <option value="pemasukan"   <?= ($edit && $edit['jenis'] === 'pemasukan')   ? 'selected' : '' ?>>Pemasukan</option>
            <option value="pengeluaran" <?= ($edit && $edit['jenis'] === 'pengeluaran') ? 'selected' : '' ?>>Pengeluaran</option>
        </select><br><br>

        <button type="submit" name="<?= $edit ? 'ubah' : 'tambah' ?>">
            <?= $edit ? 'Simpan Perubahan' : 'Tambah' ?>
        </button>
        <?php if ($edit) { ?>
            <a href="kategori.php">Batal</a>
        <?php } ?>
    </form>

    <h2>Daftar Kategori</h2>
    <table border="1" cellpadding="6">
        <tr>
            <th>No</th>
            <th>Nama Kategori</th>
            <th>Jenis</th>
            <th>Aksi</th>
        </tr>
        <?php $no = 1; while ($row = mysqli_fetch_assoc($hasil)) { ?>
        <tr>
            <td><?= $no++ ?></td>
            <td><?= htmlspecialchars($row['nama_kategori']) ?></td>
            <td><?= htmlspecialchars($row['jenis']) ?></td>
            <td>
                <a href="kategori.php?edit=<?= (int) $row['id'] ?>">Ubah</a>
                <form method="post" style="display:inline">
                    <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                    <button type="submit" name="hapus" onclick="return confirm('Yakin?')">Hapus</button>
                </form>
            </td>
        </tr>
        <?php } ?>
    </table>
</body>
</html>