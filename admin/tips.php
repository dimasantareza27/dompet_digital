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
            mysqli_stmt_close($stmt);
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
            mysqli_stmt_close($stmt);
            $pesan = 'Tips berhasil diubah.';
        }
    }

    if (isset($_POST['hapus'])) {
        $id = (int) $_POST['id'];
        $stmt = mysqli_prepare($koneksi, "DELETE FROM tips_keuangan WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        $pesan = 'Tips berhasil dihapus.';
    }
}

if (isset($_GET['edit'])) {
    $id = (int) $_GET['edit'];
    $stmt = mysqli_prepare($koneksi, "SELECT id, judul, konten FROM tips_keuangan WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $edit = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
}

$stmt = mysqli_prepare($koneksi, "SELECT id, judul, konten, created_at FROM tips_keuangan ORDER BY created_at DESC, id DESC");
mysqli_stmt_execute($stmt);
$hasil = mysqli_stmt_get_result($stmt);
$daftar_tips = mysqli_fetch_all($hasil, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Tips Keuangan - Sistem Keuangan Mahasiswa</title>
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
                    <a href="kategori.php" class="nav-link font-label text-label-lg">
                        <span class="font-label text-label-lg">Kategori</span>
                    </a>
                    <a href="tips.php" aria-current="page"
                        class="nav-link nav-link--active font-label text-label-lg">
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
                                            aria-hidden="true">lightbulb</span>
                                    </div>
                                    <div class="notif-content">
                                        <div class="notif-title-row">
                                            <span class="font-headline text-title-md">Tips Finansial</span>
                                            <span class="js-nb-unread unread-dot"></span>
                                        </div>
                                        <span class="font-label text-label-sm uppercase text-muted">Total <?= count($daftar_tips) ?> Artikel Tips Tersedia</span>
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
                    <h1 class="font-display text-display-responsive">Kelola Tips Keuangan</h1>
                    <p class="font-body text-body-md text-muted">Publikasikan artikel tips finansial singkat yang akan langsung tampil di dashboard mahasiswa.</p>
                </div>

                <?php if ($pesan !== ''): ?>
                    <div role="status" class="feedback-banner nb-shadow-sm flex">
                        <span class="material-symbols-outlined icon-18" aria-hidden="true">info</span>
                        <span class="font-label text-label-lg"><?= htmlspecialchars($pesan, ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Split Bento Grid: Form (4 cols) + Table (8 cols) -->
            <main class="split-grid">

                <!-- Form Card -->
                <section class="col-lg-4 panel-card nb-shadow-lg">
                    <header class="panel-header-tertiary">
                        <h2 class="font-headline text-headline-md"><?= $edit ? 'Ubah Tips' : 'Tambah Tips' ?></h2>
                    </header>

                    <form method="post" action="tips.php" class="panel-form">
                        <?php if ($edit): ?>
                            <input type="hidden" name="id" value="<?= (int) $edit['id'] ?>">
                        <?php endif; ?>

                        <div class="form-field">
                            <label for="judul" class="font-label text-label-lg">Judul</label>
                            <input id="judul" name="judul" type="text" maxlength="150"
                                placeholder="Contoh: Catat Pengeluaran Kecil" required
                                value="<?= $edit ? htmlspecialchars($edit['judul'], ENT_QUOTES, 'UTF-8') : '' ?>"
                                class="form-input nb-shadow-sm font-body text-body-md">
                        </div>

                        <div class="form-field">
                            <label for="konten" class="font-label text-label-lg">Konten</label>
                            <textarea id="konten" name="konten" rows="5"
                                placeholder="Tulis tips finansial singkat untuk mahasiswa..." required
                                class="form-textarea nb-shadow-sm font-body text-body-md"><?= $edit ? htmlspecialchars($edit['konten'], ENT_QUOTES, 'UTF-8') : '' ?></textarea>
                        </div>

                        <button type="submit" name="<?= $edit ? 'ubah' : 'tambah' ?>"
                            class="mt-2 btn btn--full btn--primary nb-shadow nb-press font-label text-label-lg">
                            <span class="material-symbols-outlined icon-18" aria-hidden="true"><?= $edit ? 'save' : 'publish' ?></span>
                            <span class="font-label text-label-lg"><?= $edit ? 'Simpan Perubahan' : 'Publikasikan Tips' ?></span>
                        </button>

                        <?php if ($edit): ?>
                            <a href="tips.php" class="btn btn--full btn--card nb-shadow-sm nb-press font-label text-label-lg" style="margin-top: 8px;">
                                Batal Edit
                            </a>
                        <?php endif; ?>
                    </form>
                </section>

                <!-- Table Card -->
                <section class="col-lg-8 panel-card nb-shadow-lg">
                    <header class="panel-header-beige">
                        <h2 class="font-headline text-headline-md">Daftar Tips</h2>
                        <span class="font-label text-label-sm uppercase text-muted">TAMPIL DI DASHBOARD MAHASISWA</span>
                    </header>

                    <div class="table-wrap">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th scope="col"><span class="font-label text-label-sm uppercase">No</span></th>
                                    <th scope="col"><span class="font-label text-label-sm uppercase">Judul &amp; Konten</span></th>
                                    <th scope="col"><span class="font-label text-label-sm uppercase">Dibuat</span></th>
                                    <th scope="col" class="text-right"><span class="font-label text-label-sm uppercase">Aksi</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($daftar_tips) === 0): ?>
                                    <tr>
                                        <td colspan="4" class="empty-cell">
                                            <span class="font-body text-body-md text-muted">Belum ada tips keuangan.</span>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($daftar_tips as $i => $row): ?>
                                        <tr>
                                            <td class="tabular-nums"><span class="font-body text-body-md"><?= $i + 1 ?></span></td>
                                            <td>
                                                <div style="display: flex; flex-direction: column; gap: 4px;">
                                                    <span class="font-headline text-title-md"><?= htmlspecialchars($row['judul'], ENT_QUOTES, 'UTF-8') ?></span>
                                                    <p class="font-body text-body-md text-muted" style="white-space: pre-line; margin: 0;"><?= htmlspecialchars($row['konten'], ENT_QUOTES, 'UTF-8') ?></p>
                                                </div>
                                            </td>
                                            <td class="tabular-nums"><span class="font-body text-body-md text-muted"><?= htmlspecialchars(date('d-m-Y H:i', strtotime($row['created_at'])), ENT_QUOTES, 'UTF-8') ?></span></td>
                                            <td class="text-right">
                                                <div style="display: inline-flex; gap: 8px;">
                                                    <a href="tips.php?edit=<?= (int) $row['id'] ?>" class="btn btn--card nb-shadow-sm nb-press font-label text-label-sm" style="min-height: 36px; padding: 4px 10px;">
                                                        <span class="material-symbols-outlined icon-16">edit</span>
                                                        <span>Ubah</span>
                                                    </a>
                                                    <form method="post" action="tips.php" style="display:inline">
                                                        <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                                                        <button type="submit" name="hapus" onclick="return confirm('Yakin ingin menghapus tips ini?')" class="btn btn--error nb-shadow-sm nb-press font-label text-label-sm" style="min-height: 36px; padding: 4px 10px;">
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
