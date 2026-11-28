<?php
require_once '../config.php';
cek_login('mahasiswa');

$user_id = $_SESSION['user_id'];

$nama_bulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
               'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

$bulan = isset($_GET['bulan']) ? (int) $_GET['bulan'] : (int) date('n');
$tahun = isset($_GET['tahun']) ? (int) $_GET['tahun'] : (int) date('Y');
if ($bulan < 1 || $bulan > 12) {
    $bulan = (int) date('n');
}
if ($tahun < 2000 || $tahun > 2100) {
    $tahun = (int) date('Y');
}

$stmt = mysqli_prepare(
    $koneksi,
    "SELECT
        COALESCE(SUM(CASE WHEN jenis = 'pemasukan'   THEN nominal END), 0) AS masuk,
        COALESCE(SUM(CASE WHEN jenis = 'pengeluaran' THEN nominal END), 0) AS keluar
     FROM transaksi
     WHERE user_id = ? AND MONTH(tanggal) = ? AND YEAR(tanggal) = ?"
);
mysqli_stmt_bind_param($stmt, 'iii', $user_id, $bulan, $tahun);
mysqli_stmt_execute($stmt);
$ringkasan = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

$total_masuk  = (float) $ringkasan['masuk'];
$total_keluar = (float) $ringkasan['keluar'];
$saldo_periode = $total_masuk - $total_keluar;

$stmt = mysqli_prepare(
    $koneksi,
    "SELECT t.tanggal, t.jenis, t.nominal, t.catatan, k.nama_kategori
     FROM transaksi t
     JOIN kategori k ON k.id = t.kategori_id
     WHERE t.user_id = ? AND MONTH(t.tanggal) = ? AND YEAR(t.tanggal) = ?
     ORDER BY t.tanggal ASC, t.id ASC"
);
mysqli_stmt_bind_param($stmt, 'iii', $user_id, $bulan, $tahun);
mysqli_stmt_execute($stmt);
$hasil = mysqli_stmt_get_result($stmt);
$daftar_transaksi = mysqli_fetch_all($hasil, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Keuangan - Sistem Keuangan Mahasiswa</title>
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

            <!-- Top Navigation Bar (Shared Shell, hidden on print) -->
            <header class="no-print top-header nb-shadow-lg">
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
                    <a href="dashboard.php" class="nav-link font-label text-label-lg">
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
                    <a href="laporan.php" aria-current="page"
                        class="nav-link nav-link--active font-label text-label-lg">
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
                                            aria-hidden="true">receipt_long</span>
                                    </div>
                                    <div class="notif-content">
                                        <div class="notif-title-row">
                                            <span class="font-headline text-title-md">Laporan Siap</span>
                                            <span class="js-nb-unread unread-dot"></span>
                                        </div>
                                        <span class="font-label text-label-sm uppercase text-muted">Periode <?= htmlspecialchars($nama_bulan[$bulan] . ' ' . $tahun) ?></span>
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

            <!-- Filter & Print Toolbar (hidden on print) -->
            <section class="no-print print-toolbar nb-shadow-lg">
                <form method="get" action="laporan.php" class="period-form">
                    <label for="bulan" class="font-label text-label-lg">Periode:</label>
                    <select id="bulan" name="bulan" required class="toolbar-control nb-shadow-sm font-body text-body-md">
                        <?php foreach ($nama_bulan as $no => $nama): ?>
                            <option value="<?= $no ?>" <?= $no === $bulan ? 'selected' : '' ?>><?= $nama ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input id="tahun" name="tahun" type="number" value="<?= $tahun ?>" min="2000" max="2100" required
                        class="toolbar-control toolbar-control--year nb-shadow-sm font-body text-body-md tabular-nums">
                    <button type="submit" class="btn btn--card nb-shadow-sm nb-press font-label text-label-lg">
                        <span class="font-label text-label-lg">Tampilkan</span>
                    </button>
                </form>

                <button type="button" onclick="window.print()"
                    class="btn btn--primary nb-shadow nb-press font-label text-label-lg">
                    <span class="material-symbols-outlined icon-18" aria-hidden="true">print</span>
                    <span class="font-label text-label-lg">Cetak / Simpan PDF</span>
                </button>
            </section>

            <!-- Printable Report Sheet -->
            <main class="report-sheet nb-shadow-lg">
                <!-- Kop Laporan -->
                <header class="report-header">
                    <div class="report-title-col">
                        <span class="font-label text-label-sm uppercase text-muted">SISTEM KEUANGAN MAHASISWA</span>
                        <h1 class="font-display text-display-responsive">Laporan Keuangan Mahasiswa</h1>
                    </div>
                    <div class="report-meta-col">
                        <span class="font-headline text-title-md">Nama: <?= htmlspecialchars($_SESSION['nama'] ?? 'Mahasiswa', ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="font-body text-body-md text-muted">Periode: <?= htmlspecialchars($nama_bulan[$bulan] . ' ' . $tahun, ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="font-label text-label-sm uppercase tabular-nums text-muted">DICETAK: <?= date('d-m-Y') ?></span>
                    </div>
                </header>

                <!-- Ringkasan Periode -->
                <section aria-label="Ringkasan periode" class="summary-grid">
                    <div class="summary-box">
                        <span class="font-label text-label-sm uppercase text-muted">TOTAL PEMASUKAN</span>
                        <span class="font-headline text-headline-md tabular-nums"><?= format_rupiah($total_masuk) ?></span>
                    </div>
                    <div class="summary-box">
                        <span class="font-label text-label-sm uppercase text-muted">TOTAL PENGELUARAN</span>
                        <span class="font-headline text-headline-md tabular-nums"><?= format_rupiah($total_keluar) ?></span>
                    </div>
                    <div class="summary-box summary-box--lime">
                        <span class="font-label text-label-sm uppercase">SALDO PERIODE</span>
                        <span class="font-headline text-headline-md tabular-nums"><?= format_rupiah($saldo_periode) ?></span>
                    </div>
                </section>

                <!-- Rincian Transaksi -->
                <section aria-label="Rincian transaksi" class="report-details">
                    <h2 class="font-headline text-headline-md">Rincian Transaksi</h2>

                    <div class="table-wrap">
                        <table class="report-table">
                            <thead>
                                <tr>
                                    <th scope="col"><span class="font-label text-label-sm uppercase">No</span></th>
                                    <th scope="col"><span class="font-label text-label-sm uppercase">Tanggal</span></th>
                                    <th scope="col"><span class="font-label text-label-sm uppercase">Kategori</span></th>
                                    <th scope="col"><span class="font-label text-label-sm uppercase">Jenis</span></th>
                                    <th scope="col" class="text-right"><span class="font-label text-label-sm uppercase">Nominal</span></th>
                                    <th scope="col"><span class="font-label text-label-sm uppercase">Catatan</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($daftar_transaksi) === 0): ?>
                                    <tr>
                                        <td colspan="6" class="empty-cell">
                                            <span class="font-body text-body-md text-muted">Tidak ada transaksi pada periode ini.</span>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($daftar_transaksi as $i => $t): ?>
                                        <tr>
                                            <td class="tabular-nums"><span class="font-body text-body-md"><?= $i + 1 ?></span></td>
                                            <td class="tabular-nums"><span class="font-body text-body-md"><?= date('d-m-Y', strtotime($t['tanggal'])) ?></span></td>
                                            <td><span class="font-body text-body-md"><?= htmlspecialchars($t['nama_kategori'], ENT_QUOTES, 'UTF-8') ?></span></td>
                                            <td>
                                                <span class="badge-status font-label text-label-sm uppercase <?= $t['jenis'] === 'pemasukan' ? 'bg-secondary' : 'bg-warning' ?>">
                                                    <?= htmlspecialchars(ucfirst($t['jenis']), ENT_QUOTES, 'UTF-8') ?>
                                                </span>
                                            </td>
                                            <td class="text-right tabular-nums">
                                                <span class="font-body text-body-md" style="font-weight: 700;">
                                                    <?= ($t['jenis'] === 'pemasukan' ? '+ ' : '- ') . format_rupiah($t['nominal']) ?>
                                                </span>
                                            </td>
                                            <td><span class="font-body text-body-md text-muted"><?= htmlspecialchars($t['catatan'] ?? '-', ENT_QUOTES, 'UTF-8') ?></span></td>
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
