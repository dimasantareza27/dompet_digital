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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Sistem Keuangan Mahasiswa</title>
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
                    <a href="dashboard.php" aria-current="page"
                        class="nav-link nav-link--active font-label text-label-lg">
                        <span class="font-label text-label-lg">Dashboard</span>
                    </a>
                    <a href="pengguna.php" class="nav-link font-label text-label-lg">
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
                                            aria-hidden="true">admin_panel_settings</span>
                                    </div>
                                    <div class="notif-content">
                                        <div class="notif-title-row">
                                            <span class="font-headline text-title-md">Sesi Admin Aktif</span>
                                            <span class="js-nb-unread unread-dot"></span>
                                        </div>
                                        <span class="font-label text-label-sm uppercase text-muted">Akses Administrator</span>
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

            <!-- Greeting & Privacy Banner -->
            <section class="greeting-section">
                <div class="greeting-row">
                    <div class="greeting-title">
                        <h1 class="font-display text-display-responsive">Dashboard Admin</h1>
                        <svg width="28" height="28" viewBox="0 0 32 32" fill="none" aria-hidden="true">
                            <path d="M16 2L19.5 12.5L30 16L19.5 19.5L16 30L12.5 19.5L2 16L12.5 12.5L16 2Z"
                                fill="#00C2CB" stroke="#000000" stroke-width="2.2" stroke-linejoin="round"></path>
                        </svg>
                    </div>
                    <p class="font-headline text-headline-md text-muted">Halo, <?= htmlspecialchars($_SESSION['nama'] ?? 'Administrator', ENT_QUOTES, 'UTF-8') ?></p>
                </div>

                <div role="status" class="privacy-banner nb-shadow">
                    <span class="material-symbols-outlined icon-22 shrink-0" aria-hidden="true">visibility_off</span>
                    <p class="font-label text-label-lg">
                        Mode Privasi Aktif: Statistik sistem ditampilkan secara agregat dan anonim. Saldo serta catatan
                        pribadi mahasiswa tidak ditampilkan.
                    </p>
                </div>
            </section>

            <!-- 4 Global Anonymous KPI Cards -->
            <section aria-label="Statistik sistem" class="kpi-grid-4">
                <!-- Total Mahasiswa -->
                <article class="card card-elevated nb-shadow-lg">
                    <header class="card-header">
                        <h2 class="font-headline text-title-md">Total Mahasiswa</h2>
                        <span class="material-symbols-outlined icon-20" aria-hidden="true">groups</span>
                    </header>
                    <div class="card-metric">
                        <p class="font-display text-display-responsive tabular-nums"><?= (int) ($mhs['total'] ?? 0) ?></p>
                        <p class="font-label text-label-sm uppercase text-muted">AKUN TERDAFTAR</p>
                    </div>
                </article>

                <!-- Akun Aktif -->
                <article class="card card-elevated nb-shadow-lg">
                    <header class="card-header">
                        <h2 class="font-headline text-title-md">Akun Aktif</h2>
                        <span role="status" class="badge-status bg-secondary font-label text-label-sm uppercase">
                            <span>AKTIF</span>
                        </span>
                    </header>
                    <div class="card-metric">
                        <p class="font-display text-display-responsive tabular-nums"><?= (int) ($mhs['aktif'] ?? 0) ?></p>
                        <p class="font-label text-label-sm uppercase text-muted">DAPAT MENGAKSES SISTEM</p>
                    </div>
                </article>

                <!-- Akun Nonaktif -->
                <article class="card card-elevated nb-shadow-lg">
                    <header class="card-header">
                        <h2 class="font-headline text-title-md">Akun Nonaktif</h2>
                        <span role="status" class="badge-status bg-error font-label text-label-sm uppercase">
                            <span>NONAKTIF</span>
                        </span>
                    </header>
                    <div class="card-metric">
                        <p class="font-display text-display-responsive tabular-nums"><?= (int) ($mhs['nonaktif'] ?? 0) ?></p>
                        <p class="font-label text-label-sm uppercase text-muted">AKSES DITANGGUHKAN</p>
                    </div>
                </article>

                <!-- Total Transaksi Sistem -->
                <article class="card card-elevated nb-shadow-lg">
                    <header class="card-header">
                        <h2 class="font-headline text-title-md">Total Transaksi</h2>
                        <span class="material-symbols-outlined icon-20" aria-hidden="true">receipt_long</span>
                    </header>
                    <div class="card-metric">
                        <p class="font-display text-display-responsive tabular-nums"><?= (int) ($total_transaksi ?? 0) ?></p>
                        <p class="font-label text-label-sm uppercase text-muted">CATATAN ANONIM DI SISTEM</p>
                    </div>
                </article>
            </section>

            <!-- Quick Admin Navigation Bento Row -->
            <main class="nav-bento-grid">
                <a href="pengguna.php" class="card card-elevated nb-shadow-lg nb-press">
                    <div class="nav-card-head">
                        <h2 class="font-headline text-headline-md">Manajemen Pengguna</h2>
                        <span class="material-symbols-outlined icon-22" aria-hidden="true">arrow_outward</span>
                    </div>
                    <p class="font-body text-body-md text-muted">
                        Aktifkan atau nonaktifkan akun mahasiswa serta reset password ke default.
                    </p>
                </a>

                <a href="kategori.php" class="card card-beige nb-shadow-lg nb-press">
                    <div class="nav-card-head">
                        <h2 class="font-headline text-headline-md">Master Kategori</h2>
                        <span class="material-symbols-outlined icon-22" aria-hidden="true">arrow_outward</span>
                    </div>
                    <p class="font-body text-body-md text-muted">
                        Kelola daftar kategori pemasukan dan pengeluaran bawaan sistem.
                    </p>
                </a>

                <a href="tips.php" class="card card-tertiary nb-shadow-lg nb-press">
                    <div class="nav-card-head">
                        <h2 class="font-headline text-headline-md">Tips Keuangan</h2>
                        <span class="material-symbols-outlined icon-22" aria-hidden="true">arrow_outward</span>
                    </div>
                    <p class="font-body text-body-md">
                        Publikasikan artikel tips finansial singkat untuk dashboard mahasiswa.
                    </p>
                </a>
            </main>

        </div>
    </div>

    <!-- Global Toast Notification Stack -->
    <div id="nb-toast-stack" class="hidden toast-stack"></div>

    <script src="../assets/app.js"></script>
</body>

</html>
