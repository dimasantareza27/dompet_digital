<?php
require_once '../config.php';
cek_login('mahasiswa');

$userId = (int) $_SESSION['user_id'];
$bulanIni = (int) date('n');
$tahunIni = (int) date('Y');
$namaBulan = [
    1 => 'Januari',
    2 => 'Februari',
    3 => 'Maret',
    4 => 'April',
    5 => 'Mei',
    6 => 'Juni',
    7 => 'Juli',
    8 => 'Agustus',
    9 => 'September',
    10 => 'Oktober',
    11 => 'November',
    12 => 'Desember',
];
$awalBulan = date('Y-m-01');
$awalBulanBerikutnya = date('Y-m-01', strtotime('+1 month', strtotime($awalBulan)));
$batasForm = '';
$pesan = '';
$error = '';

$pesanValid = [
    'tersimpan' => 'Budget bulan ini berhasil disimpan.',
];
$statusQuery = $_GET['status'] ?? '';
if (is_string($statusQuery) && isset($pesanValid[$statusQuery])) {
    $pesan = $pesanValid[$statusQuery];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $batasInput = $_POST['batas_nominal'] ?? '';
    if (!is_string($batasInput)) {
        $error = 'Nilai budget tidak valid.';
    } else {
        $batasForm = trim($batasInput);
        if (!preg_match('/\A\d{1,13}(?:\.\d{1,2})?\z/', $batasForm)) {
            $error = 'Budget harus minimal 1 dan maksimal memiliki dua angka desimal.';
        } elseif ((float) $batasForm < 1) {
            $error = 'Budget minimal adalah 1.';
        }
    }

    if ($error === '') {
        $stmt = mysqli_prepare(
            $koneksi,
            'SELECT id
             FROM anggaran
             WHERE user_id = ? AND bulan = ? AND tahun = ?
             ORDER BY id DESC
             LIMIT 1'
        );
        mysqli_stmt_bind_param($stmt, 'iii', $userId, $bulanIni, $tahunIni);
        mysqli_stmt_execute($stmt);
        $anggaranAda = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        if ($anggaranAda) {
            $stmt = mysqli_prepare(
                $koneksi,
                'UPDATE anggaran
                 SET batas_nominal = ?
                 WHERE user_id = ? AND bulan = ? AND tahun = ?'
            );
            mysqli_stmt_bind_param($stmt, 'siii', $batasForm, $userId, $bulanIni, $tahunIni);
        } else {
            $stmt = mysqli_prepare(
                $koneksi,
                'INSERT INTO anggaran (user_id, bulan, tahun, batas_nominal)
                 VALUES (?, ?, ?, ?)'
            );
            mysqli_stmt_bind_param($stmt, 'iiis', $userId, $bulanIni, $tahunIni, $batasForm);
        }

        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        header('Location: budget.php?status=tersimpan');
        exit;
    }
}

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
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $anggaran) {
    $batasForm = (string) $anggaran['batas_nominal'];
}

$stmt = mysqli_prepare(
    $koneksi,
    "SELECT COALESCE(SUM(nominal), 0) AS total_pengeluaran
     FROM transaksi
     WHERE user_id = ?
       AND jenis = 'pengeluaran'
       AND tanggal >= ?
       AND tanggal < ?"
);
mysqli_stmt_bind_param($stmt, 'iss', $userId, $awalBulan, $awalBulanBerikutnya);
mysqli_stmt_execute($stmt);
$realisasi = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

$totalPengeluaran = (float) $realisasi['total_pengeluaran'];
$persentase = $batasAnggaran > 0
    ? ($totalPengeluaran / $batasAnggaran) * 100
    : 0;
$persentaseTampil = round($persentase, 2);
$lebarProgress = min(100, max(0, $persentaseTampil));
$sisaBudget = $batasAnggaran - $totalPengeluaran;

if ($persentase >= 100) {
    $statusBudget = 'Melebihi Batas';
    $warnaProgress = '#dc3545';
    $badgeClass = 'bg-error text-on-error';
} elseif ($persentase >= 80) {
    $statusBudget = 'Peringatan';
    $warnaProgress = '#ffc107';
    $badgeClass = 'bg-warning text-on-warning';
} else {
    $statusBudget = 'Aman';
    $warnaProgress = '#00C2CB';
    $badgeClass = 'bg-secondary text-on-secondary';
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Budget Bulanan - Keuangan Mahasiswa</title>
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
                    <a href="dashboard.php" class="nav-link font-label text-label-lg">
                        <span class="font-label text-label-lg">Dashboard</span>
                    </a>
                    <a href="transaksi.php" class="nav-link font-label text-label-lg">
                        <span class="font-label text-label-lg">Transaksi</span>
                    </a>
                    <a href="budget.php" aria-current="page" class="nav-link nav-link--active font-label text-label-lg">
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
                    <a href="../logout.php" class="btn btn--card nb-shadow nb-press font-label text-label-lg">
                        <span class="material-symbols-outlined icon-18" aria-hidden="true">logout</span>
                        <span class="font-label text-label-lg">Keluar</span>
                    </a>
                </div>
            </header>

            <!-- Page Title -->
            <div class="page-title-row">
                <h1 class="font-display text-display-responsive">
                    Budget Bulan <?= htmlspecialchars($namaBulan[$bulanIni] . ' ' . $tahunIni, ENT_QUOTES, 'UTF-8') ?>
                </h1>
                <span class="pill-badge-beige font-label text-label-sm uppercase">1 BATAS TOTAL PER BULAN</span>
            </div>

            <!-- Toast / Alerts -->
            <?php if ($pesan !== ''): ?>
                <div role="status" class="toast-item nb-shadow bg-secondary" style="display:flex; align-items:center; gap:8px;">
                    <span class="material-symbols-outlined icon-18" aria-hidden="true">check_circle</span>
                    <span class="font-label text-label-lg"><?= htmlspecialchars($pesan, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            <?php endif; ?>
            <?php if ($error !== ''): ?>
                <div role="alert" class="toast-item nb-shadow bg-error" style="display:flex; align-items:center; gap:8px;">
                    <span class="material-symbols-outlined icon-18" aria-hidden="true">error</span>
                    <span class="font-label text-label-lg"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            <?php endif; ?>

            <!-- 2-Column Bento Grid -->
            <main class="budget-grid">

                <!-- Left 5 Cols: Set Monthly Limit Form -->
                <section class="col-lg-5 panel-card nb-shadow-lg">
                    <header class="panel-header-beige">
                        <h2 class="font-headline text-headline-md">Atur Batas Pengeluaran</h2>
                    </header>

                    <form id="budget-form" class="budget-form" method="post" action="budget.php">
                        <div class="form-field">
                            <label for="batas_nominal" class="font-label text-label-lg">Batas pengeluaran bulan ini (Rp)</label>
                            <input
                                id="batas_nominal"
                                name="batas_nominal"
                                type="number"
                                min="1"
                                step="0.01"
                                placeholder="Masukkan batas pengeluaran"
                                value="<?= htmlspecialchars($batasForm, ENT_QUOTES, 'UTF-8') ?>"
                                required
                                class="budget-input nb-shadow-sm font-headline text-headline-md tabular-nums"
                            >
                        </div>

                        <button type="submit" class="btn btn--full btn--primary nb-shadow nb-press font-label text-label-lg">
                            <span class="material-symbols-outlined icon-18" aria-hidden="true">save</span>
                            <span class="font-label text-label-lg">Simpan Budget</span>
                        </button>

                        <div class="rules-box">
                            <span class="font-label text-label-sm uppercase">ATURAN INDIKATOR WARNA</span>
                            <div class="rules-chips">
                                <span class="rule-chip bg-secondary font-label text-label-sm">&lt; 80% AMAN</span>
                                <span class="rule-chip bg-warning font-label text-label-sm">80–99% PERINGATAN</span>
                                <span class="rule-chip bg-error font-label text-label-sm">&gt;= 100% MELEBIHI</span>
                            </div>
                        </div>
                    </form>
                </section>

                <!-- Right 7 Cols: Budget Realization & Progress Bar -->
                <section aria-label="Realisasi budget" class="col-lg-7 realization-card nb-shadow-lg">
                    <header class="realization-header">
                        <h2 class="font-headline text-headline-md">
                            <?= $batasAnggaran > 0 ? 'Status: ' . htmlspecialchars($statusBudget, ENT_QUOTES, 'UTF-8') : 'Belum ada budget untuk bulan ini' ?>
                        </h2>
                        <span id="budget-badge" role="status" class="budget-badge <?= $badgeClass ?> font-label text-label-sm uppercase">
                            <span id="budget-status-text" class="font-label text-label-sm">
                                <?= $batasAnggaran > 0 ? strtoupper(htmlspecialchars($statusBudget, ENT_QUOTES, 'UTF-8')) : 'BELUM DIATUR' ?>
                            </span>
                        </span>
                    </header>

                    <div class="stat-grid">
                        <div class="stat-box">
                            <span class="font-label text-label-sm uppercase text-muted">BATAS BUDGET</span>
                            <span id="disp-batas" class="font-headline text-headline-md tabular-nums">
                                <?= format_rupiah($batasAnggaran) ?>
                            </span>
                        </div>
                        <div class="stat-box">
                            <span class="font-label text-label-sm uppercase text-muted">TOTAL PENGELUARAN</span>
                            <span class="font-headline text-headline-md tabular-nums">
                                <?= format_rupiah($totalPengeluaran) ?>
                            </span>
                        </div>
                        <div class="stat-box">
                            <span class="font-label text-label-sm uppercase text-muted">SISA BUDGET</span>
                            <span id="disp-sisa" class="font-headline text-headline-md tabular-nums">
                                <?= format_rupiah($sisaBudget) ?>
                            </span>
                        </div>
                    </div>

                    <div class="progress-section">
                        <div class="progress-header">
                            <span class="font-headline text-title-md">Persentase Penggunaan</span>
                            <span id="disp-pct" class="font-display text-display-lg-mobile tabular-nums">
                                <?= $persentaseTampil ?>%
                            </span>
                        </div>
                        <div role="progressbar" aria-label="Persentase penggunaan budget" aria-valuemin="0"
                            aria-valuemax="100" aria-valuenow="<?= $lebarProgress ?>" class="progress-track">
                            <div id="budget-bar" class="progress-fill" style="width: <?= $lebarProgress ?>%; background-color: <?= $warnaProgress ?>;"></div>
                        </div>
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
