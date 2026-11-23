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
    <title>Dashboard Mahasiswa</title>
</head>
<body>
    <nav aria-label="Navigasi utama">
        <a href="dashboard.php">Dashboard</a>
        <a href="transaksi.php">Transaksi</a>
        <a href="budget.php">Budget</a>
        <a href="tabungan.php">Target Tabungan</a>
        <a href="laporan.php">Laporan</a>
        <a href="../logout.php">Keluar</a>
    </nav>

    <main>
        <h1>Dashboard Keuangan</h1>
        <p>Halo, <?= htmlspecialchars($_SESSION['nama'], ENT_QUOTES, 'UTF-8') ?>.</p>

        <?php if ($peringatanAnggaran): ?>
            <section role="status">
                <h2>Peringatan Anggaran</h2>
                <p>Pengeluaran bulan ini telah mencapai <?= format_rupiah($pengeluaranBulanIni) ?> dari batas <?= format_rupiah($batasAnggaran) ?>.</p>
            </section>
        <?php endif; ?>

        <section aria-label="Ringkasan keuangan">
            <h2>Total Saldo</h2>
            <p><?= format_rupiah($totalSaldo) ?></p>
            <h2>Pemasukan Bulan Ini</h2>
            <p><?= format_rupiah($pemasukanBulanIni) ?></p>
            <h2>Pengeluaran Bulan Ini</h2>
            <p><?= format_rupiah($pengeluaranBulanIni) ?></p>
        </section>

        <section aria-label="Grafik pemasukan dan pengeluaran bulan ini">
            <h2>Perbandingan Bulan Ini</h2>
            <p>Pemasukan: <?= format_rupiah($pemasukanBulanIni) ?></p>
            <div style="width: <?= $lebarPemasukan ?>%; height: 20px; background-color: #2e8b57;"></div>
            <p>Pengeluaran: <?= format_rupiah($pengeluaranBulanIni) ?></p>
            <div style="width: <?= $lebarPengeluaran ?>%; height: 20px; background-color: #d9534f;"></div>
        </section>

        <section>
            <h2>Tips Keuangan</h2>
            <?php if ($tipTerbaru): ?>
                <h3><?= htmlspecialchars($tipTerbaru['judul'], ENT_QUOTES, 'UTF-8') ?></h3>
                <p><?= nl2br(htmlspecialchars($tipTerbaru['konten'], ENT_QUOTES, 'UTF-8')) ?></p>
            <?php else: ?>
                <p>Belum ada tips keuangan.</p>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
