<?php
require_once '../config.php';
cek_login('mahasiswa');

$userId = (int) $_SESSION['user_id'];
$bulanIni = (int) date('n');
$tahunIni = (int) date('Y');
$awalBulan = date('Y-m-01');
$awalBulanBerikutnya = date('Y-m-01', strtotime('+1 month'));

$stmt = mysqli_prepare(
    $koneksi,
    "SELECT
        COALESCE(SUM(CASE WHEN jenis = 'pemasukan' THEN nominal ELSE 0 END), 0) AS total_pemasukan,
        COALESCE(SUM(CASE WHEN jenis = 'pengeluaran' THEN nominal ELSE 0 END), 0) AS total_pengeluaran,
        COALESCE(SUM(CASE WHEN jenis = 'pemasukan' AND tanggal >= ? AND tanggal < ? THEN nominal ELSE 0 END), 0) AS pemasukan_bulan_ini,
        COALESCE(SUM(CASE WHEN jenis = 'pengeluaran' AND tanggal >= ? AND tanggal < ? THEN nominal ELSE 0 END), 0) AS pengeluaran_bulan_ini
     FROM transaksi
     WHERE user_id = ?"
);
mysqli_stmt_bind_param(
    $stmt,
    'ssssi',
    $awalBulan,
    $awalBulanBerikutnya,
    $awalBulan,
    $awalBulanBerikutnya,
    $userId
);
mysqli_stmt_execute($stmt);
$ringkasan = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

$totalSaldo = (float) $ringkasan['total_pemasukan'] - (float) $ringkasan['total_pengeluaran'];
$pemasukanBulanIni = (float) $ringkasan['pemasukan_bulan_ini'];
$pengeluaranBulanIni = (float) $ringkasan['pengeluaran_bulan_ini'];

$stmt = mysqli_prepare(
    $koneksi,
    'SELECT batas_nominal
     FROM anggaran
     WHERE user_id = ? AND bulan = ? AND tahun = ?
     ORDER BY id DESC
     LIMIT 1'
);
mysqli_stmt_bind_param($stmt, 'iii', $userId, $bulanIni, $tahunIni);
mysqli_stmt_execute($stmt);
$anggaran = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

$batasAnggaran = $anggaran ? (float) $anggaran['batas_nominal'] : 0;
$peringatanAnggaran = $batasAnggaran > 0
    && $pengeluaranBulanIni >= ($batasAnggaran * 0.8);

$nilaiMaksimumGrafik = max($pemasukanBulanIni, $pengeluaranBulanIni);
$lebarPemasukan = $nilaiMaksimumGrafik > 0
    ? round(($pemasukanBulanIni / $nilaiMaksimumGrafik) * 100, 2)
    : 0;
$lebarPengeluaran = $nilaiMaksimumGrafik > 0
    ? round(($pengeluaranBulanIni / $nilaiMaksimumGrafik) * 100, 2)
    : 0;

$stmt = mysqli_prepare(
    $koneksi,
    'SELECT judul, konten
     FROM tips_keuangan
     ORDER BY created_at DESC, id DESC
     LIMIT 1'
);
mysqli_stmt_execute($stmt);
$tipTerbaru = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Mahasiswa - Keuangan Mahasiswa</title>
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

            <!-- Top Navigation Bar (Shared Shell) -->
            <header class="top-header nb-shadow-lg">
                <a href="dashboard.php" class="brand-link">
                    <div class="brand-icon nb-shadow-sm">
                        <span class="material-symbols-outlined icon-22" aria-hidden="true">account_balance_wallet</span>
                    </div>
                    <div class="brand-text">
                        <span class="font-headline text-headline-md leading-none">KEUANGAN.MHS</span>
                        <span class="font-label text-label-sm uppercase text-muted">PORTAL MAHASISWA</span>
                    </div>
                </a>

                <nav aria-label="Navigasi utama" class="main-nav">
                    <a href="dashboard.php" aria-current="page" class="nav-link nav-link--active font-label text-label-lg">
                        <span class="font-label text-label-lg">Dashboard</span>
                    </a>
                    <a href="transaksi.php" class="nav-link font-label text-label-lg">
                        <span class="font-label text-label-lg">Transaksi</span>
                    </a>
                    <a href="budget.php" class="nav-link font-label text-label-lg">
                        <span class="font-label text-label-lg">Budget</span>
                    </a>
                    <a href="tabungan.php" class="nav-link font-label text-label-lg">
                        <span class="font-label text-label-lg">Target Tabungan</span>
                    </a>
                    <a href="laporan.php" class="nav-link font-label text-label-lg">
                        <span class="font-label text-label-lg">Laporan</span>
                    </a>
                </nav>

                <div class="header-actions">
                    <!-- Notification Bell + Popover -->
                    <div class="relative-wrap">
                        <button type="button"
                            onclick="window.__nbToggle && window.__nbToggle('nb-notif-popover')"
                            aria-label="Notifications" class="icon-btn nb-shadow-sm nb-press">
                            <span class="material-symbols-outlined icon-20" aria-hidden="true">notifications</span>
                            <?php if ($peringatanAnggaran): ?>
                                <span id="nb-bell-badge" class="bell-badge font-label text-label-sm tabular-nums">!</span>
                            <?php endif; ?>
                        </button>
                        <div id="nb-notif-popover" class="hidden notif-popover nb-shadow">
                            <header class="notif-header">
                                <span class="font-headline text-title-md">Notifikasi</span>
                                <button type="button"
                                    onclick="window.__nbMarkAllRead && window.__nbMarkAllRead()"
                                    class="mark-read-btn font-label text-label-sm uppercase nb-press">Tandai dibaca</button>
                            </header>
                            <ul class="notif-list">
                                <?php if ($peringatanAnggaran): ?>
                                    <li class="notif-item">
                                        <div class="notif-avatar bg-warning">
                                            <span class="material-symbols-outlined icon-16" aria-hidden="true">warning</span>
                                        </div>
                                        <div class="notif-content">
                                            <div class="notif-title-row">
                                                <span class="font-headline text-title-md">Batas Budget Tercapai</span>
                                                <span class="js-nb-unread unread-dot"></span>
                                            </div>
                                            <span class="font-label text-label-sm uppercase text-muted">Pengeluaran &ge; 80%</span>
                                        </div>
                                    </li>
                                <?php endif; ?>
                                <li class="notif-item">
                                    <div class="notif-avatar bg-secondary">
                                        <span class="material-symbols-outlined icon-16" aria-hidden="true">check_circle</span>
                                    </div>
                                    <div class="notif-content">
                                        <div class="notif-title-row">
                                            <span class="font-headline text-title-md">Sistem Siap</span>
                                        </div>
                                        <span class="font-label text-label-sm uppercase text-muted">Portal keuangan aktif</span>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <a href="../logout.php" class="btn btn--card nb-shadow nb-press font-label text-label-lg">
                        <span class="material-symbols-outlined icon-18" aria-hidden="true">logout</span>
                        <span class="font-label text-label-lg">Keluar</span>
                    </a>
                </div>
            </header>

            <!-- Greeting -->
            <section class="greeting-section">
                <div class="greeting-row">
                    <div class="greeting-title">
                        <h1 class="font-display text-display-responsive text-balance">Dashboard Keuangan</h1>
                        <svg width="28" height="28" viewBox="0 0 32 32" fill="none" aria-hidden="true">
                            <path d="M16 2L19.5 12.5L30 16L19.5 19.5L16 30L12.5 19.5L2 16L12.5 12.5L16 2Z"
                                fill="#D9FF00" stroke="#000000" stroke-width="2.2" stroke-linejoin="round"></path>
                        </svg>
                    </div>
                    <p class="font-headline text-headline-md text-muted">Halo, <?= htmlspecialchars($_SESSION['nama'], ENT_QUOTES, 'UTF-8') ?>.</p>
                </div>
            </section>

            <!-- Budget Warning Banner if triggered -->
            <?php if ($peringatanAnggaran): ?>
                <div role="status" class="toast-item nb-shadow bg-warning" style="display:flex; align-items:center; justify-content:space-between; gap:16px;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <span class="material-symbols-outlined icon-24" aria-hidden="true">warning</span>
                        <div>
                            <h2 class="font-headline text-title-md" style="margin:0;">Peringatan Anggaran</h2>
                            <p class="font-body text-body-md" style="margin:0;">Pengeluaran bulan ini telah mencapai <strong><?= format_rupiah($pengeluaranBulanIni) ?></strong> dari batas <strong><?= format_rupiah($batasAnggaran) ?></strong> (<?= round(($pengeluaranBulanIni / $batasAnggaran) * 100, 1) ?>%).</p>
                        </div>
                    </div>
                    <a href="budget.php" class="btn btn--card nb-shadow-sm nb-press font-label text-label-sm" style="min-height:36px; padding:4px 12px; white-space:nowrap;">Cek Budget</a>
                </div>
            <?php endif; ?>

            <!-- Summary Metrics Row (3 KPI Cards) -->
            <section aria-label="Ringkasan keuangan" class="kpi-grid">
                <!-- Total Saldo -->
                <article class="card card-elevated nb-shadow-lg">
                    <header class="card-header">
                        <h2 class="font-headline text-title-md">Total Saldo</h2>
                        <span class="badge-status font-label text-label-sm uppercase">
                            <span class="font-label text-label-sm">NETO</span>
                        </span>
                    </header>
                    <div class="card-metric">
                        <p class="font-display text-display-responsive tabular-nums"><?= format_rupiah($totalSaldo) ?></p>
                        <p class="font-label text-label-sm uppercase text-muted">KESELURUHAN PEMASUKAN - PENGELUARAN</p>
                    </div>
                </article>

                <!-- Pemasukan Bulan Ini -->
                <article class="card card-elevated nb-shadow-lg">
                    <header class="card-header">
                        <h2 class="font-headline text-title-md">Pemasukan Bulan Ini</h2>
                        <span class="badge-status font-label text-label-sm uppercase">
                            <span class="font-label text-label-sm">MASUK</span>
                        </span>
                    </header>
                    <div class="card-metric">
                        <p class="font-display text-display-responsive tabular-nums"><?= format_rupiah($pemasukanBulanIni) ?></p>
                        <p class="font-label text-label-sm uppercase text-muted">TOTAL PEMASUKAN BULAN INI</p>
                    </div>
                </article>

                <!-- Pengeluaran Bulan Ini -->
                <article class="card card-elevated nb-shadow-lg">
                    <header class="card-header">
                        <h2 class="font-headline text-title-md">Pengeluaran Bulan Ini</h2>
                        <span class="badge-status font-label text-label-sm uppercase">
                            <span class="font-label text-label-sm"><?= $batasAnggaran > 0 ? round(($pengeluaranBulanIni / $batasAnggaran) * 100) . '% BUDGET' : '0% BUDGET' ?></span>
                        </span>
                    </header>
                    <div class="card-metric">
                        <p class="font-display text-display-responsive tabular-nums"><?= format_rupiah($pengeluaranBulanIni) ?></p>
                        <p class="font-label text-label-sm uppercase text-muted">TOTAL PENGELUARAN BULAN INI</p>
                    </div>
                </article>
            </section>

            <!-- Main Bento Grid: CSS Bar Chart + Latest Financial Tip -->
            <main class="bento-grid">

                <!-- Left 7 Cols: Pure CSS Bar Chart Comparison -->
                <section aria-label="Grafik pemasukan dan pengeluaran bulan ini"
                    class="bento-chart card card-chart nb-shadow-lg">
                    <header class="chart-header">
                        <div class="chart-title-group">
                            <h2 class="font-headline text-headline-md">Perbandingan Bulan Ini</h2>
                            <p class="font-body text-body-md text-muted">
                                <?= ($pemasukanBulanIni == 0 && $pengeluaranBulanIni == 0) ? 'Belum ada transaksi pada bulan ini.' : 'Grafik perbandingan pemasukan vs pengeluaran bulan ini.' ?>
                            </p>
                        </div>
                        <a href="transaksi.php" class="btn btn--primary nb-shadow nb-press font-label text-label-lg">
                            <span class="material-symbols-outlined icon-18" aria-hidden="true">add</span>
                            <span class="font-label text-label-lg">Catat Transaksi</span>
                        </a>
                    </header>

                    <div class="chart-bars">
                        <div class="bar-group">
                            <div class="bar-label-row">
                                <span class="font-headline text-title-md">Pemasukan</span>
                                <span class="font-label text-label-lg tabular-nums"><?= format_rupiah($pemasukanBulanIni) ?> (<?= $lebarPemasukan ?>%)</span>
                            </div>
                            <div class="bar-track">
                                <div class="bar-fill bar-fill--secondary" style="width: <?= $lebarPemasukan ?>%;"></div>
                            </div>
                        </div>

                        <div class="bar-group">
                            <div class="bar-label-row">
                                <span class="font-headline text-title-md">Pengeluaran</span>
                                <span class="font-label text-label-lg tabular-nums"><?= format_rupiah($pengeluaranBulanIni) ?> (<?= $lebarPengeluaran ?>%)</span>
                            </div>
                            <div class="bar-track">
                                <div class="bar-fill bar-fill--tertiary" style="width: <?= $lebarPengeluaran ?>%;"></div>
                            </div>
                        </div>
                    </div>

                    <footer class="card-footer">
                        <span class="font-label text-label-sm uppercase text-muted">SELISIH BULAN INI</span>
                        <span class="font-headline text-headline-md tabular-nums"><?= format_rupiah($pemasukanBulanIni - $pengeluaranBulanIni) ?></span>
                    </footer>
                </section>

                <!-- Right 5 Cols: Latest Financial Tip -->
                <article class="bento-tip card card-tip nb-shadow-lg">
                    <header class="card-header">
                        <span class="badge-primary font-label text-label-sm uppercase">TIPS KEUANGAN TERBARU</span>
                        <svg width="42" height="16" viewBox="0 0 46 16" fill="none" aria-hidden="true">
                            <path d="M2 10C6 3 10 3 14 10C18 17 22 17 26 10C30 3 34 3 38 10C40 13 42 13 44 10"
                                stroke="#000000" stroke-width="2.5" stroke-linecap="round"></path>
                        </svg>
                    </header>

                    <div class="tip-body">
                        <?php if ($tipTerbaru): ?>
                            <h2 class="font-headline text-headline-md"><?= htmlspecialchars($tipTerbaru['judul'], ENT_QUOTES, 'UTF-8') ?></h2>
                            <p class="font-body text-body-md text-pretty">
                                <?= nl2br(htmlspecialchars($tipTerbaru['konten'], ENT_QUOTES, 'UTF-8')) ?>
                            </p>
                        <?php else: ?>
                            <h2 class="font-headline text-headline-md">Belum ada tips keuangan</h2>
                            <p class="font-body text-body-md text-pretty">
                                Tips keuangan terbaru dari Administrator akan muncul di sini setelah dipublikasikan.
                            </p>
                        <?php endif; ?>
                    </div>

                    <footer class="card-footer">
                        <span class="font-label text-label-sm uppercase"><?= $tipTerbaru ? 'EDUKASI FINANSIAL' : 'MENUNGGU KONTEN' ?></span>
                        <a href="tabungan.php" class="btn btn--card nb-shadow nb-press font-label text-label-lg">
                            <span class="font-label text-label-lg">Cek Tabungan</span>
                        </a>
                    </footer>
                </article>

            </main>
        </div>
    </div>

    <!-- Global Toast Notification Stack -->
    <div id="nb-toast-stack" class="hidden toast-stack"></div>

    <script src="../assets/app.js"></script>
</body>

</html>
