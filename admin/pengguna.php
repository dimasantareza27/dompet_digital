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
        mysqli_stmt_close($stmt);
        $pesan = 'Status akun berhasil diubah.';
    }

    if (isset($_POST['reset_password'])) {
        $hash = password_hash('mahasiswa123', PASSWORD_DEFAULT);
        $stmt = mysqli_prepare($koneksi,
            "UPDATE pengguna SET password = ? WHERE id = ? AND role = 'mahasiswa'");
        mysqli_stmt_bind_param($stmt, "si", $hash, $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
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
$daftar_pengguna = mysqli_fetch_all($hasil, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Pengguna - Sistem Keuangan Mahasiswa</title>
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
                    <a href="pengguna.php" aria-current="page"
                        class="nav-link nav-link--active font-label text-label-lg">
                        <span class="font-label text-label-lg">Pengguna</span>
                    </a>
                    <a href="kategori.php" class="nav-link font-label text-label-lg">
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
                                            aria-hidden="true">groups</span>
                                    </div>
                                    <div class="notif-content">
                                        <div class="notif-title-row">
                                            <span class="font-headline text-title-md">Total <?= count($daftar_pengguna) ?> Mahasiswa</span>
                                            <span class="js-nb-unread unread-dot"></span>
                                        </div>
                                        <span class="font-label text-label-sm uppercase text-muted">Data Pengguna Aktif</span>
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

            <!-- Page Header + Feedback Banner -->
            <div class="page-header-row">
                <div class="page-title-col">
                    <h1 class="font-display text-display-responsive">Manajemen Pengguna</h1>
                    <p class="font-body text-body-md text-muted">
                        Daftar akun dengan role Mahasiswa. Reset password mengembalikan sandi ke default (<code>mahasiswa123</code>).
                    </p>
                </div>

                <?php if ($pesan !== ''): ?>
                    <div role="status" class="feedback-banner toast-fixed nb-shadow-sm flex">
                        <span class="material-symbols-outlined icon-18" aria-hidden="true">check_circle</span>
                        <span class="font-label text-label-lg"><?= htmlspecialchars($pesan, ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Table Card -->
            <section class="panel-card nb-shadow-lg">
                <div class="panel-header-beige">
                    <div class="title-with-badge">
                        <h2 class="font-headline text-headline-md">Daftar Akun Mahasiswa</h2>
                        <span role="status" class="badge-numeric font-label text-label-sm tabular-nums">
                            <span><?= count($daftar_pengguna) ?></span>
                        </span>
                    </div>

                    <div class="search-wrap">
                        <span class="material-symbols-outlined icon-18 search-icon" aria-hidden="true">search</span>
                        <input id="search-user" type="search" placeholder="Cari nama atau email..."
                            aria-label="Cari nama atau email" class="search-input nb-shadow-sm font-body text-body-md">
                    </div>
                </div>

                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th scope="col"><span class="font-label text-label-sm uppercase">No</span></th>
                                <th scope="col"><span class="font-label text-label-sm uppercase">Nama</span></th>
                                <th scope="col"><span class="font-label text-label-sm uppercase">Email</span></th>
                                <th scope="col"><span class="font-label text-label-sm uppercase">Status</span></th>
                                <th scope="col"><span class="font-label text-label-sm uppercase">Terdaftar</span></th>
                                <th scope="col" class="text-right"><span class="font-label text-label-sm uppercase">Aksi</span></th>
                            </tr>
                        </thead>
                        <tbody id="user-tbody">
                            <?php if (count($daftar_pengguna) === 0): ?>
                                <tr>
                                    <td colspan="6" class="empty-cell">
                                        <span class="font-body text-body-md text-muted">Belum ada akun mahasiswa terdaftar.</span>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($daftar_pengguna as $i => $row): ?>
                                    <tr>
                                        <td class="tabular-nums"><span class="font-body text-body-md"><?= $i + 1 ?></span></td>
                                        <td><span class="font-body text-body-md" style="font-weight: 600;"><?= htmlspecialchars($row['nama'], ENT_QUOTES, 'UTF-8') ?></span></td>
                                        <td><span class="font-body text-body-md"><?= htmlspecialchars($row['email'], ENT_QUOTES, 'UTF-8') ?></span></td>
                                        <td>
                                            <span role="status" class="badge-status <?= $row['status'] === 'aktif' ? 'bg-secondary' : 'bg-error' ?> font-label text-label-sm uppercase">
                                                <?= htmlspecialchars(strtoupper($row['status']), ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                        </td>
                                        <td class="tabular-nums"><span class="font-body text-body-md text-muted"><?= htmlspecialchars(date('d-m-Y H:i', strtotime($row['created_at'])), ENT_QUOTES, 'UTF-8') ?></span></td>
                                        <td class="text-right">
                                            <div style="display: inline-flex; gap: 8px;">
                                                <form method="post" style="display:inline">
                                                    <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                                                    <button type="submit" name="ubah_status" onclick="return confirm('Yakin ingin <?= $row['status'] === 'aktif' ? 'menonaktifkan' : 'mengaktifkan' ?> akun ini?')" class="btn btn--card nb-shadow-sm nb-press font-label text-label-sm" style="min-height: 36px; padding: 4px 10px;">
                                                        <span class="material-symbols-outlined icon-16"><?= $row['status'] === 'aktif' ? 'block' : 'check_circle' ?></span>
                                                        <span><?= $row['status'] === 'aktif' ? 'Nonaktifkan' : 'Aktifkan' ?></span>
                                                    </button>
                                                </form>
                                                <form method="post" style="display:inline">
                                                    <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                                                    <button type="submit" name="reset_password" onclick="return confirm('Yakin reset password akun ini ke mahasiswa123?')" class="btn btn--card nb-shadow-sm nb-press font-label text-label-sm" style="min-height: 36px; padding: 4px 10px;">
                                                        <span class="material-symbols-outlined icon-16">lock_reset</span>
                                                        <span>Reset Password</span>
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
        </div>
    </div>

    <!-- Global Toast Notification Stack -->
    <div id="nb-toast-stack" class="hidden toast-stack"></div>

    <script src="../assets/app.js"></script>
</body>

</html>
