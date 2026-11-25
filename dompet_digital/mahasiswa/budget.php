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
} elseif ($persentase >= 80) {
    $statusBudget = 'Peringatan';
    $warnaProgress = '#ffc107';
} else {
    $statusBudget = 'Aman';
    $warnaProgress = '#198754';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Budget Bulanan - Keuangan Mahasiswa</title>
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
        <h1>Budget Bulan <?= htmlspecialchars($namaBulan[$bulanIni] . ' ' . $tahunIni, ENT_QUOTES, 'UTF-8') ?></h1>

        <?php if ($pesan !== ''): ?>
            <p role="status"><?= htmlspecialchars($pesan, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
            <p role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <form method="post" action="budget.php">
            <label for="batas_nominal">Batas pengeluaran bulan ini</label>
            <input
                id="batas_nominal"
                type="number"
                name="batas_nominal"
                min="1"
                step="0.01"
                value="<?= htmlspecialchars($batasForm, ENT_QUOTES, 'UTF-8') ?>"
                required
            >
            <button type="submit">Simpan Budget</button>
        </form>

        <section aria-label="Realisasi budget">
            <?php if ($batasAnggaran > 0): ?>
                <h2>Status: <?= htmlspecialchars($statusBudget, ENT_QUOTES, 'UTF-8') ?></h2>
                <p>Batas budget: <?= format_rupiah($batasAnggaran) ?></p>
                <p>Total pengeluaran: <?= format_rupiah($totalPengeluaran) ?></p>
                <p>Sisa budget: <?= format_rupiah($sisaBudget) ?></p>
                <p>Penggunaan: <?= $persentaseTampil ?>%</p>
                <div
                    role="progressbar"
                    aria-label="Persentase penggunaan budget"
                    aria-valuemin="0"
                    aria-valuemax="100"
                    aria-valuenow="<?= $lebarProgress ?>"
                    style="width: 100%; height: 20px; background-color: #e9ecef;"
                >
                    <div style="width: <?= $lebarProgress ?>%; height: 20px; background-color: <?= $warnaProgress ?>;"></div>
                </div>
            <?php else: ?>
                <h2>Belum ada budget untuk bulan ini</h2>
                <p>Total pengeluaran: <?= format_rupiah($totalPengeluaran) ?></p>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
