<?php
require_once '../config.php';
cek_login('mahasiswa');

$userId = (int) $_SESSION['user_id'];
$pesan = '';
$error = '';
$bulanSekarang = (int) date('n');
$tahunSekarang = (int) date('Y');
$tahunMinimum = max(2000, $tahunSekarang - 5);
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
$form = [
    'id' => '',
    'jenis' => 'pemasukan',
    'kategori_id' => '',
    'nominal' => '',
    'tanggal' => date('Y-m-d'),
    'catatan' => '',
];

$pesanValid = [
    'ditambahkan' => 'Transaksi berhasil ditambahkan.',
    'diperbarui' => 'Transaksi berhasil diperbarui.',
    'dihapus' => 'Transaksi berhasil dihapus.',
];
$pesanQuery = $_GET['pesan'] ?? '';
if (is_string($pesanQuery) && isset($pesanValid[$pesanQuery])) {
    $pesan = $pesanValid[$pesanQuery];
}

$bulanInput = filter_input(INPUT_GET, 'bulan', FILTER_VALIDATE_INT);
$tahunInput = filter_input(INPUT_GET, 'tahun', FILTER_VALIDATE_INT);
$jenisFilter = $_GET['filter_jenis'] ?? '';

if ($bulanInput === null || $bulanInput === false) {
    $bulanFilter = $bulanSekarang;
} elseif ($bulanInput >= 0 && $bulanInput <= 12) {
    $bulanFilter = $bulanInput;
} else {
    $bulanFilter = $bulanSekarang;
    $error = 'Filter bulan tidak valid.';
}

if ($tahunInput === null || $tahunInput === false) {
    $tahunFilter = $tahunSekarang;
} elseif ($tahunInput >= $tahunMinimum && $tahunInput <= $tahunSekarang + 1) {
    $tahunFilter = $tahunInput;
} else {
    $tahunFilter = $tahunSekarang;
    $error = 'Filter tahun tidak valid.';
}

if (!is_string($jenisFilter) || !in_array($jenisFilter, ['', 'pemasukan', 'pengeluaran'], true)) {
    $jenisFilter = '';
    $error = 'Filter jenis transaksi tidak valid.';
}

if (isset($_GET['hapus'])) {
    $idHapus = filter_input(INPUT_GET, 'hapus', FILTER_VALIDATE_INT);
    if ($idHapus === false || $idHapus === null || $idHapus < 1) {
        $error = 'Transaksi yang akan dihapus tidak valid.';
    } else {
        $stmt = mysqli_prepare(
            $koneksi,
            'DELETE FROM transaksi WHERE id = ? AND user_id = ?'
        );
        mysqli_stmt_bind_param($stmt, 'ii', $idHapus, $userId);
        mysqli_stmt_execute($stmt);
        $berhasilDihapus = mysqli_stmt_affected_rows($stmt) > 0;
        mysqli_stmt_close($stmt);

        if ($berhasilDihapus) {
            header('Location: transaksi.php?pesan=dihapus');
            exit;
        }

        $error = 'Transaksi tidak ditemukan atau bukan milik akun Anda.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idInput = $_POST['id'] ?? '';
    $jenisInput = $_POST['jenis'] ?? '';
    $kategoriInput = $_POST['kategori_id'] ?? '';
    $nominalInput = $_POST['nominal'] ?? '';
    $tanggalInput = $_POST['tanggal'] ?? '';
    $catatanInput = $_POST['catatan'] ?? '';

    $inputTransaksiValid = is_string($idInput)
        && is_string($jenisInput)
        && is_string($kategoriInput)
        && is_string($nominalInput)
        && is_string($tanggalInput)
        && is_string($catatanInput);

    $idInput = is_string($idInput) ? $idInput : '';
    $jenisInput = is_string($jenisInput) ? $jenisInput : '';
    $kategoriInput = is_string($kategoriInput) ? $kategoriInput : '';
    $nominalInput = is_string($nominalInput) ? trim($nominalInput) : '';
    $tanggalInput = is_string($tanggalInput) ? $tanggalInput : '';
    $catatanInput = is_string($catatanInput) ? trim($catatanInput) : '';

    $form = [
        'id' => $idInput,
        'jenis' => $jenisInput,
        'kategori_id' => $kategoriInput,
        'nominal' => $nominalInput,
        'tanggal' => $tanggalInput,
        'catatan' => $catatanInput,
    ];

    if (!$inputTransaksiValid) {
        $error = 'Data transaksi tidak valid.';
    }

    $idTransaksi = null;
    if ($idInput !== '') {
        $idValid = filter_var($idInput, FILTER_VALIDATE_INT);
        if ($idValid === false || $idValid < 1) {
            $error = 'Transaksi yang akan diperbarui tidak valid.';
        } else {
            $idTransaksi = $idValid;
            $stmt = mysqli_prepare(
                $koneksi,
                'SELECT id FROM transaksi WHERE id = ? AND user_id = ?'
            );
            mysqli_stmt_bind_param($stmt, 'ii', $idTransaksi, $userId);
            mysqli_stmt_execute($stmt);
            $transaksiMilikUser = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            mysqli_stmt_close($stmt);

            if (!$transaksiMilikUser) {
                $error = 'Transaksi tidak ditemukan atau bukan milik akun Anda.';
            }
        }
    }

    $kategoriId = filter_var($kategoriInput, FILTER_VALIDATE_INT);
    if ($error === '' && !in_array($jenisInput, ['pemasukan', 'pengeluaran'], true)) {
        $error = 'Jenis transaksi tidak valid.';
    } elseif ($error === '' && ($kategoriId === false || $kategoriId < 1)) {
        $error = 'Silakan pilih kategori yang valid.';
    }

    if ($error === '' && !preg_match('/\A\d{1,13}(?:\.\d{1,2})?\z/', $nominalInput)) {
        $error = 'Nominal harus minimal 1 dan maksimal memiliki dua angka desimal.';
    } elseif ($error === '' && (float) $nominalInput < 1) {
        $error = 'Nominal minimal adalah 1.';
    }

    $tanggalObj = DateTime::createFromFormat('!Y-m-d', $tanggalInput);
    if (
        $error === ''
        && (!$tanggalObj || $tanggalObj->format('Y-m-d') !== $tanggalInput)
    ) {
        $error = 'Tanggal transaksi tidak valid.';
    }

    if ($error === '' && strlen($catatanInput) > 10000) {
        $error = 'Catatan terlalu panjang.';
    }

    if ($error === '') {
        $stmt = mysqli_prepare(
            $koneksi,
            'SELECT id FROM kategori WHERE id = ? AND jenis = ?'
        );
        mysqli_stmt_bind_param($stmt, 'is', $kategoriId, $jenisInput);
        mysqli_stmt_execute($stmt);
        $kategoriValid = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        if (!$kategoriValid) {
            $error = 'Kategori tidak tersedia untuk jenis transaksi tersebut.';
        }
    }

    if ($error === '') {
        $catatan = $catatanInput === '' ? null : $catatanInput;

        if ($idTransaksi === null) {
            $stmt = mysqli_prepare(
                $koneksi,
                'INSERT INTO transaksi (user_id, kategori_id, jenis, nominal, tanggal, catatan)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            mysqli_stmt_bind_param(
                $stmt,
                'iissss',
                $userId,
                $kategoriId,
                $jenisInput,
                $nominalInput,
                $tanggalInput,
                $catatan
            );
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            header('Location: transaksi.php?pesan=ditambahkan');
            exit;
        }

        $stmt = mysqli_prepare(
            $koneksi,
            'UPDATE transaksi
             SET kategori_id = ?, jenis = ?, nominal = ?, tanggal = ?, catatan = ?
             WHERE id = ? AND user_id = ?'
        );
        mysqli_stmt_bind_param(
            $stmt,
            'issssii',
            $kategoriId,
            $jenisInput,
            $nominalInput,
            $tanggalInput,
            $catatan,
            $idTransaksi,
            $userId
        );
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        header('Location: transaksi.php?pesan=diperbarui');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && isset($_GET['edit'])) {
    $idEdit = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
    if ($idEdit === false || $idEdit === null || $idEdit < 1) {
        $error = 'Transaksi yang akan diedit tidak valid.';
    } else {
        $stmt = mysqli_prepare(
            $koneksi,
            'SELECT id, kategori_id, jenis, nominal, tanggal, catatan
             FROM transaksi
             WHERE id = ? AND user_id = ?'
        );
        mysqli_stmt_bind_param($stmt, 'ii', $idEdit, $userId);
        mysqli_stmt_execute($stmt);
        $transaksiEdit = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        if (!$transaksiEdit) {
            $error = 'Transaksi tidak ditemukan atau bukan milik akun Anda.';
        } else {
            $form = [
                'id' => (string) $transaksiEdit['id'],
                'jenis' => $transaksiEdit['jenis'],
                'kategori_id' => (string) $transaksiEdit['kategori_id'],
                'nominal' => (string) $transaksiEdit['nominal'],
                'tanggal' => $transaksiEdit['tanggal'],
                'catatan' => $transaksiEdit['catatan'] ?? '',
            ];
        }
    }
}

$kategoriResult = mysqli_query(
    $koneksi,
    'SELECT id, nama_kategori, jenis FROM kategori ORDER BY jenis, nama_kategori'
);
$kategoriList = mysqli_fetch_all($kategoriResult, MYSQLI_ASSOC);
mysqli_free_result($kategoriResult);

if ($bulanFilter === 0) {
    $tanggalMulai = sprintf('%04d-01-01', $tahunFilter);
    $tanggalSelesai = sprintf('%04d-01-01', $tahunFilter + 1);
} else {
    $tanggalMulai = sprintf('%04d-%02d-01', $tahunFilter, $bulanFilter);
    $tanggalSelesai = date('Y-m-d', strtotime('+1 month', strtotime($tanggalMulai)));
}

$stmt = mysqli_prepare(
    $koneksi,
    "SELECT t.id, t.jenis, t.nominal, t.tanggal, t.catatan, k.nama_kategori
     FROM transaksi AS t
     LEFT JOIN kategori AS k ON k.id = t.kategori_id
     WHERE t.user_id = ?
       AND t.tanggal >= ?
       AND t.tanggal < ?
       AND (? = '' OR t.jenis = ?)
     ORDER BY t.tanggal DESC, t.id DESC"
);
mysqli_stmt_bind_param(
    $stmt,
    'issss',
    $userId,
    $tanggalMulai,
    $tanggalSelesai,
    $jenisFilter,
    $jenisFilter
);
mysqli_stmt_execute($stmt);
$transaksiList = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

$bulanForm = $bulanFilter;
$tahunForm = $tahunFilter;
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaksi - Keuangan Mahasiswa</title>
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
                    <a href="transaksi.php" aria-current="page" class="nav-link nav-link--active font-label text-label-lg">
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
                    <a href="../logout.php" class="btn btn--card nb-shadow nb-press font-label text-label-lg">
                        <span class="material-symbols-outlined icon-18" aria-hidden="true">logout</span>
                        <span class="font-label text-label-lg">Keluar</span>
                    </a>
                </div>
            </header>

            <!-- Page Title -->
            <div class="page-title-row">
                <h1 class="font-display text-display-responsive">Transaksi Keuangan</h1>
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

            <!-- CRUD Form Card: Tambah / Edit Transaksi -->
            <section class="panel-card nb-shadow-lg">
                <header class="panel-header-lime">
                    <h2 id="form-heading" class="font-headline text-headline-md">
                        <?= $form['id'] === '' ? 'Tambah Transaksi' : 'Edit Transaksi' ?>
                    </h2>
                    <span class="font-label text-label-sm uppercase">INPUT MANUAL</span>
                </header>

                <form id="tx-form" class="tx-form" method="post" action="transaksi.php">
                    <input type="hidden" name="id" value="<?= htmlspecialchars($form['id'], ENT_QUOTES, 'UTF-8') ?>">

                    <div class="col-md-2 form-field">
                        <label for="jenis" class="font-label text-label-lg">Jenis</label>
                        <select id="jenis" name="jenis" required class="form-control nb-shadow-sm font-body text-body-md">
                            <option value="pemasukan" <?= $form['jenis'] === 'pemasukan' ? 'selected' : '' ?>>Pemasukan</option>
                            <option value="pengeluaran" <?= $form['jenis'] === 'pengeluaran' ? 'selected' : '' ?>>Pengeluaran</option>
                        </select>
                    </div>

                    <div class="col-md-3 form-field">
                        <label for="kategori_id" class="font-label text-label-lg">Kategori</label>
                        <select id="kategori_id" name="kategori_id" required class="form-control nb-shadow-sm font-body text-body-md">
                            <option value="">Pilih kategori</option>
                            <?php foreach (['pemasukan' => 'Pemasukan', 'pengeluaran' => 'Pengeluaran'] as $jenisKategori => $labelJenis): ?>
                                <optgroup label="<?= $labelJenis ?>">
                                    <?php foreach ($kategoriList as $kategori): ?>
                                        <?php if ($kategori['jenis'] === $jenisKategori): ?>
                                            <option
                                                value="<?= (int) $kategori['id'] ?>"
                                                <?= (string) $kategori['id'] === $form['kategori_id'] ? 'selected' : '' ?>
                                            >
                                                <?= htmlspecialchars($kategori['nama_kategori'], ENT_QUOTES, 'UTF-8') ?>
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2 form-field">
                        <label for="nominal" class="font-label text-label-lg">Nominal (Rp)</label>
                        <input
                            id="nominal"
                            name="nominal"
                            type="number"
                            min="1"
                            step="0.01"
                            placeholder="35000"
                            value="<?= htmlspecialchars($form['nominal'], ENT_QUOTES, 'UTF-8') ?>"
                            required
                            class="form-control nb-shadow-sm font-body text-body-md tabular-nums"
                        >
                    </div>

                    <div class="col-md-2 form-field">
                        <label for="tanggal" class="font-label text-label-lg">Tanggal</label>
                        <input
                            id="tanggal"
                            name="tanggal"
                            type="date"
                            value="<?= htmlspecialchars($form['tanggal'], ENT_QUOTES, 'UTF-8') ?>"
                            required
                            class="form-control nb-shadow-sm font-body text-body-md"
                        >
                    </div>

                    <div class="col-md-3 form-field">
                        <label for="catatan" class="font-label text-label-lg">Catatan</label>
                        <input
                            id="catatan"
                            name="catatan"
                            type="text"
                            maxlength="10000"
                            placeholder="Opsional: Makan siang kantin"
                            value="<?= htmlspecialchars($form['catatan'], ENT_QUOTES, 'UTF-8') ?>"
                            class="form-control nb-shadow-sm font-body text-body-md"
                        >
                    </div>

                    <div class="col-md-12 form-actions">
                        <?php if ($form['id'] !== ''): ?>
                            <a href="transaksi.php" class="btn btn--card nb-shadow nb-press font-label text-label-lg">Batal Edit</a>
                        <?php endif; ?>
                        <button type="submit" class="btn btn--primary nb-shadow nb-press font-label text-label-lg">
                            <span class="material-symbols-outlined icon-18" aria-hidden="true">save</span>
                            <span class="font-label text-label-lg"><?= $form['id'] === '' ? 'Simpan Transaksi' : 'Perbarui Transaksi' ?></span>
                        </button>
                    </div>
                </form>
            </section>

            <!-- Table & Filter Section -->
            <section class="panel-card nb-shadow-lg">
                <div class="panel-header-beige">
                    <h2 class="font-headline text-headline-md">Riwayat Transaksi</h2>

                    <!-- Filter Bar -->
                    <form method="get" action="transaksi.php" class="filter-bar">
                        <select id="bulan" name="bulan" aria-label="Filter bulan" class="filter-select nb-shadow-sm font-body text-body-md">
                            <option value="0" <?= $bulanForm === 0 ? 'selected' : '' ?>>Semua bulan</option>
                            <?php foreach ($namaBulan as $nomorBulan => $labelBulan): ?>
                                <option value="<?= $nomorBulan ?>" <?= $nomorBulan === $bulanForm ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($labelBulan, ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <select id="tahun" name="tahun" aria-label="Filter tahun" class="filter-select nb-shadow-sm font-body text-body-md">
                            <?php for ($tahun = $tahunMinimum; $tahun <= $tahunSekarang + 1; $tahun++): ?>
                                <option value="<?= $tahun ?>" <?= $tahun === $tahunForm ? 'selected' : '' ?>>
                                    <?= $tahun ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                        <select id="filter_jenis" name="filter_jenis" aria-label="Filter jenis" class="filter-select nb-shadow-sm font-body text-body-md">
                            <option value="" <?= $jenisFilter === '' ? 'selected' : '' ?>>Semua jenis</option>
                            <option value="pemasukan" <?= $jenisFilter === 'pemasukan' ? 'selected' : '' ?>>Pemasukan</option>
                            <option value="pengeluaran" <?= $jenisFilter === 'pengeluaran' ? 'selected' : '' ?>>Pengeluaran</option>
                        </select>
                        <button type="submit" class="btn btn--card nb-shadow-sm nb-press font-label text-label-lg" style="min-height: 44px;">Filter</button>
                    </form>
                </div>

                <div class="table-wrap">
                    <table class="tx-table">
                        <thead>
                            <tr>
                                <th scope="col"><span class="font-label text-label-sm uppercase">Tanggal</span></th>
                                <th scope="col"><span class="font-label text-label-sm uppercase">Kategori</span></th>
                                <th scope="col"><span class="font-label text-label-sm uppercase">Jenis</span></th>
                                <th scope="col" class="text-right"><span class="font-label text-label-sm uppercase">Nominal</span></th>
                                <th scope="col"><span class="font-label text-label-sm uppercase">Catatan</span></th>
                                <th scope="col" class="text-right"><span class="font-label text-label-sm uppercase">Aksi</span></th>
                            </tr>
                        </thead>
                        <tbody id="tx-tbody">
                            <?php if (!$transaksiList): ?>
                                <tr>
                                    <td colspan="6" class="empty-cell">
                                        <span class="font-body text-body-md text-muted">Belum ada transaksi pada filter ini.</span>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($transaksiList as $transaksi): ?>
                                    <tr data-jenis="<?= htmlspecialchars($transaksi['jenis'], ENT_QUOTES, 'UTF-8') ?>">
                                        <td><?= htmlspecialchars($transaksi['tanggal'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($transaksi['nama_kategori'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                        <td>
                                            <span class="badge-status font-label text-label-sm uppercase <?= $transaksi['jenis'] === 'pemasukan' ? 'bg-secondary' : 'bg-warning' ?>">
                                                <?= htmlspecialchars(ucfirst($transaksi['jenis']), ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                        </td>
                                        <td class="text-right tabular-nums" style="font-weight: 700;">
                                            <?= ($transaksi['jenis'] === 'pemasukan' ? '+ ' : '- ') . format_rupiah($transaksi['nominal']) ?>
                                        </td>
                                        <td><?= nl2br(htmlspecialchars($transaksi['catatan'] ?? '', ENT_QUOTES, 'UTF-8')) ?></td>
                                        <td class="text-right">
                                            <div style="display:inline-flex; gap:8px;">
                                                <a href="transaksi.php?edit=<?= (int) $transaksi['id'] ?>" class="btn btn--card nb-shadow-sm nb-press font-label text-label-sm" style="min-height:32px; padding:4px 10px;">Edit</a>
                                                <a
                                                    href="transaksi.php?hapus=<?= (int) $transaksi['id'] ?>"
                                                    onclick="return confirm('Yakin ingin menghapus data ini?')"
                                                    class="btn btn--error nb-shadow-sm nb-press font-label text-label-sm"
                                                    style="min-height:32px; padding:4px 10px;"
                                                >Hapus</a>
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
