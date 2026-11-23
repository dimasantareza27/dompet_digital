<?php
require_once '../config.php';
cek_login('admin');


$pesan = '';
$edit  = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['tambah'])) {
        $judul  = trim($_POST['judul']);
        $konten = trim($_POST['konten']);
        if ($judul === '' || $konten === '') {
            $pesan = 'Judul dan konten wajib diisi.';
        } else {
            $stmt = mysqli_prepare($koneksi, "INSERT INTO tips_keuangan (judul, konten) VALUES (?, ?)");
            mysqli_stmt_bind_param($stmt, "ss", $judul, $konten);
            mysqli_stmt_execute($stmt);
            $pesan = 'Tips berhasil ditambahkan.';
        }
    }

    if (isset($_POST['ubah'])) {
        $id     = (int) $_POST['id'];
        $judul  = trim($_POST['judul']);
        $konten = trim($_POST['konten']);
        if ($judul === '' || $konten === '') {
            $pesan = 'Judul dan konten wajib diisi.';
        } else {
            $stmt = mysqli_prepare($koneksi, "UPDATE tips_keuangan SET judul = ?, konten = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "ssi", $judul, $konten, $id);
            mysqli_stmt_execute($stmt);
            $pesan = 'Tips berhasil diubah.';
        }
    }

    if (isset($_POST['hapus'])) {
        $id = (int) $_POST['id'];
        $stmt = mysqli_prepare($koneksi, "DELETE FROM tips_keuangan WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $pesan = 'Tips berhasil dihapus.';
    }
}

if (isset($_GET['edit'])) {
    $id = (int) $_GET['edit'];
    $stmt = mysqli_prepare($koneksi, "SELECT id, judul, konten FROM tips_keuangan WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $edit = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
}

$stmt = mysqli_prepare($koneksi, "SELECT id, judul, konten, created_at FROM tips_keuangan ORDER BY created_at DESC, id DESC");
mysqli_stmt_execute($stmt);
$hasil = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Tips Keuangan</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <h1>Kelola Tips Keuangan</h1>
    <p><a href="dashboard.php">&larr; Kembali ke Dashboard</a></p>

    <?php if ($pesan !== '') { ?>
        <p><b><?= htmlspecialchars($pesan) ?></b></p>
    <?php } ?>

    <h2><?= $edit ? 'Ubah Tips' : 'Tambah Tips' ?></h2>
    <form method="post">
        <?php if ($edit) { ?>
            <input type="hidden" name="id" value="<?= (int) $edit['id'] ?>">
        <?php } ?>

        <label>Judul</label><br>
        <input type="text" name="judul" maxlength="150" required
            value="<?= $edit ? htmlspecialchars($edit['judul']) : '' ?>"><br><br>

        <label>Konten</label><br>
        <textarea name="konten" rows="4" cols="50" required><?= $edit ? htmlspecialchars($edit['konten']) : '' ?></textarea><br><br>

        <button type="submit" name="<?= $edit ? 'ubah' : 'tambah' ?>">
            <?= $edit ? 'Simpan Perubahan' : 'Tambah' ?>
        </button>
        <?php if ($edit) { ?>
            <a href="tips.php">Batal</a>
        <?php } ?>
    </form>

    <h2>Daftar Tips</h2>
    <table border="1" cellpadding="6">
        <tr>
            <th>No</th>
            <th>Judul</th>
            <th>Konten</th>
            <th>Dibuat</th>
            <th>Aksi</th>
        </tr>
        <?php $no = 1; while ($row = mysqli_fetch_assoc($hasil)) { ?>
        <tr>
            <td><?= $no++ ?></td>
            <td><?= htmlspecialchars($row['judul']) ?></td>
            <td><?= nl2br(htmlspecialchars($row['konten'])) ?></td>
            <td><?= htmlspecialchars($row['created_at']) ?></td>
            <td>
                <a href="tips.php?edit=<?= (int) $row['id'] ?>">Ubah</a>
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