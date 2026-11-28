<?php
require_once '../config.php';
cek_login('admin');

$pesan = '';
$edit  = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['tambah'])) {
        $nama  = trim($_POST['nama_kategori']);
        $jenis = $_POST['jenis'] ?? '';
        if ($nama === '' || !in_array($jenis, ['pemasukan', 'pengeluaran'])) {
            $pesan = 'Data tidak valid.';
        } else {
            $stmt = mysqli_prepare($koneksi, "INSERT INTO kategori (nama_kategori, jenis) VALUES (?, ?)");
            mysqli_stmt_bind_param($stmt, "ss", $nama, $jenis);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            $pesan = 'Kategori berhasil ditambahkan.';
        }
    }

    if (isset($_POST['ubah'])) {
        $id    = (int) $_POST['id'];
        $nama  = trim($_POST['nama_kategori']);
        $jenis = $_POST['jenis'] ?? '';
        if ($nama === '' || !in_array($jenis, ['pemasukan', 'pengeluaran'])) {
            $pesan = 'Data tidak valid.';
        } else {
            $stmt = mysqli_prepare($koneksi, "UPDATE kategori SET nama_kategori = ?, jenis = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "ssi", $nama, $jenis, $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
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
            mysqli_stmt_close($stmt);
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
    mysqli_stmt_close($stmt);
}

$stmt = mysqli_prepare($koneksi, "SELECT id, nama_kategori, jenis FROM kategori ORDER BY jenis, nama_kategori");
mysqli_stmt_execute($stmt);
$hasil = mysqli_stmt_get_result($stmt);
$daftar_kategori = mysqli_fetch_all($hasil, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kategori - Sistem Keuangan Mahasiswa</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;700&amp;family=Plus+Jakarta+Sans:wght@400;500;600;700&amp;family=Space+Mono:wght@400;700&amp;display=swap"
        rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=block"
        rel="stylesheet">
    <link rel="stylesheet" href="../assets/style.css">
</head>

<body>
    <div class="page-shell nb-grid-bg">
        <div class="page-container">

            <!-- Top Navigation Bar (Admin Shared Shell) -->
            <header class="top-header nb-shadow-lg">
                <a href="dashboard.php" class="brand-link">
                    <div class="brand-icon brand-icon--admin nb-shadow-sm">
                        <span class="material-symbols-outlined icon-22" aria-hidden="true">admin_panel_settings</span>
                    </div>
                    <div class="brand-text">
                        <span class="font-headline text-headline-md leading-none">KEUANGAN.MHS</span>
                        <span class="font-label text-label-sm uppercase text-muted">PORTAL ADMIN</span>
                    </div>
                </a>

                <nav aria-label="Navigasi admin" class="main-nav">
                    <a href="dashboard.php" class="nav-link font-label text-label-lg">
                        <span class="font-label text-label-lg">Dashboard</span>
                    </a>
                    <a href="pengguna.php" class="nav-link font-label text-label-lg">
                        <span class="font-label text-label-lg">Pengguna</span>
                    </a>
                    <a href="kategori.php" aria-current="page"
                        class="nav-link nav-link--active font-label text-label-lg">
                        <span class="font-label text-label-lg">Kategori</span>
                    </a>
                    <a href="tips.php" class="nav-link font-label text-label-lg">
                        <span class="font-label text-label-lg">Tips Keuangan</span>
                    </a>
                </nav>

                <div class="header-actions">
                    <!-- Notification Bell + Popover -->
                    <div class="relative-wrap">
                        <button type="button"
                            onclick="window.__nbToggle && window.__nbToggle('nb-notif-popover')"
                            aria-label="Notifications" class="icon-btn nb-shadow-sm nb-press">
                            <span class="material-symbols-outlined icon-20" aria-hidden="true">notifications</span>
                            <span id="nb-bell-badge"
                                class="bell-badge font-label text-label-sm tabular-nums">1</span>
                        </button>
                        <div id="nb-notif-popover" class="hidden notif-popover nb-shadow">
                            <header class="notif-header">
                                <span class="font-headline text-title-md">Notifikasi</span>
                                <button type="button"
                                    onclick="window.__nbMarkAllRead && window.__nbMarkAllRead()"
                                    class="mark-read-btn font-label text-label-sm uppercase nb-press">Tandai dibaca</button>
                            </header>
                            <ul class="notif-list">
                                <li class="notif-item">
                                    <div class="notif-avatar bg-secondary">
                                        <span class="material-symbols-outlined icon-16"
                                            aria-hidden="true">category</span>
                                    </div>
                                    <div class="notif-content">
                                        <div class="notif-title-row">
                                            <span class="font-headline text-title-md">Master Kategori</span>
                                            <span class="js-nb-unread unread-dot"></span>
                                        </div>
                                        <span class="font-label text-label-sm uppercase text-muted">Total <?= count($daftar_kategori) ?> Kategori Tersedia</span>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <a href="../logout.php" class="btn btn--card nb-shadow nb-press font-label text-label-lg">
                        <span class="material-symbols-outlined icon-18" aria-hidden="true">logout</span>
                        <span class="font-label text-label-lg">Logout</span>
                    </a>
                </div>
            </header>

            <div class="page-header-row">
                <div class="page-title-col">
                    <h1 class="font-display text-display-responsive">Kelola Master Kategori</h1>
                    <p class="font-body text-body-md text-muted">Daftar kategori transaksi yang digunakan mahasiswa untuk mencatat pemasukan dan pengeluaran.</p>
                </div>

                <?php if ($pesan !== ''): ?>
                    <div role="status" class="feedback-banner nb-shadow-sm flex">
                        <span class="material-symbols-outlined icon-18" aria-hidden="true">info</span>
                        <span class="font-label text-label-lg"><?= htmlspecialchars($pesan, ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Split Bento Grid: Left Form (4 cols) + Right Table (8 cols) -->
            <main class="split-grid">

                <!-- Form Card -->
                <section class="col-lg-4 panel-card nb-shadow-lg">
                    <header class="panel-header-lime">
                        <h2 class="font-headline text-headline-md"><?= $edit ? 'Ubah Kategori' : 'Tambah Kategori' ?></h2>
                    </header>

                    <form method="post" action="kategori.php" class="panel-form">
                        <?php if ($edit): ?>
                            <input type="hidden" name="id" value="<?= (int) $edit['id'] ?>">
                        <?php endif; ?>

                        <div class="form-field">
                            <label for="nama_kategori" class="font-label text-label-lg">Nama Kategori</label>
                            <input id="nama_kategori" name="nama_kategori" type="text" maxlength="100"
                                placeholder="Contoh: Freelance Desain" required
                                value="<?= $edit ? htmlspecialchars($edit['nama_kategori'], ENT_QUOTES, 'UTF-8') : '' ?>"
                                class="form-control nb-shadow-sm font-body text-body-md">
                        </div>

                        <div class="form-field">
                            <label for="jenis" class="font-label text-label-lg">Jenis</label>
                            <select id="jenis" name="jenis" class="form-control nb-shadow-sm font-body text-body-md">
                                <option value="pemasukan" <?= ($edit && $edit['jenis'] === 'pemasukan') ? 'selected' : '' ?>>Pemasukan</option>
                                <option value="pengeluaran" <?= ($edit && $edit['jenis'] === 'pengeluaran') ? 'selected' : '' ?>>Pengeluaran</option>
                            </select>
                        </div>

                        <button type="submit" name="<?= $edit ? 'ubah' : 'tambah' ?>"
                            class="mt-2 btn btn--full btn--primary nb-shadow nb-press font-label text-label-lg">
                            <span class="material-symbols-outlined icon-18" aria-hidden="true"><?= $edit ? 'save' : 'add' ?></span>
                            <span class="font-label text-label-lg"><?= $edit ? 'Simpan Perubahan' : 'Tambah Kategori' ?></span>
                        </button>

                        <?php if ($edit): ?>
                            <a href="kategori.php" class="btn btn--full btn--card nb-shadow-sm nb-press font-label text-label-lg" style="margin-top: 8px;">
                                Batal Edit
                            </a>
                        <?php endif; ?>

                        <div class="lock-note">
                            <span class="material-symbols-outlined icon-18 shrink-0" aria-hidden="true">lock</span>
                            <p class="font-body text-body-md text-muted">Kategori yang masih dipakai transaksi mahasiswa tidak dapat dihapus.</p>
                        </div>
                    </form>
                </section>

                <!-- Table Card -->
                <section class="col-lg-8 panel-card nb-shadow-lg">
                    <header class="panel-header-beige">
                        <h2 class="font-headline text-headline-md">Daftar Kategori</h2>
                        <span class="font-label text-label-sm uppercase text-muted"><?= count($daftar_kategori) ?> KATEGORI</span>
                    </header>

                    <div class="table-wrap">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th scope="col"><span class="font-label text-label-sm uppercase">No</span></th>
                                    <th scope="col"><span class="font-label text-label-sm uppercase">Nama Kategori</span></th>
                                    <th scope="col"><span class="font-label text-label-sm uppercase">Jenis</span></th>
                                    <th scope="col" class="text-right"><span class="font-label text-label-sm uppercase">Aksi</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($daftar_kategori) === 0): ?>
                                    <tr>
                                        <td colspan="4" class="empty-cell">
                                            <span class="font-body text-body-md text-muted">Belum ada kategori.</span>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($daftar_kategori as $i => $row): ?>
                                        <tr>
                                            <td class="tabular-nums"><span class="font-body text-body-md"><?= $i + 1 ?></span></td>
                                            <td><span class="font-body text-body-md" style="font-weight: 600;"><?= htmlspecialchars($row['nama_kategori'], ENT_QUOTES, 'UTF-8') ?></span></td>
                                            <td>
                                                <span role="status" class="badge-status <?= $row['jenis'] === 'pemasukan' ? 'bg-secondary' : 'bg-warning' ?> font-label text-label-sm uppercase">
                                                    <?= htmlspecialchars(ucfirst($row['jenis']), ENT_QUOTES, 'UTF-8') ?>
                                                </span>
                                            </td>
                                            <td class="text-right">
                                                <div style="display: inline-flex; gap: 8px;">
                                                    <a href="kategori.php?edit=<?= (int) $row['id'] ?>" class="btn btn--card nb-shadow-sm nb-press font-label text-label-sm" style="min-height: 36px; padding: 4px 10px;">
                                                        <span class="material-symbols-outlined icon-16">edit</span>
                                                        <span>Ubah</span>
                                                    </a>
                                                    <form method="post" action="kategori.php" style="display:inline">
                                                        <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                                                        <button type="submit" name="hapus" onclick="return confirm('Yakin ingin menghapus kategori ini?')" class="btn btn--error nb-shadow-sm nb-press font-label text-label-sm" style="min-height: 36px; padding: 4px 10px;">
                                                            <span class="material-symbols-outlined icon-16">delete</span>
                                                            <span>Hapus</span>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

            </main>
        </div>
    </div>

    <!-- Global Toast Notification Stack -->
    <div id="nb-toast-stack" class="hidden toast-stack"></div>

    <script src="../assets/app.js"></script>
</body>

</html>
