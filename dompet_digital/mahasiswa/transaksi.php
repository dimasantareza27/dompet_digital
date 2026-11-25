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
        <h1>Transaksi</h1>

        <?php if ($pesan !== ''): ?>
            <p role="status"><?= htmlspecialchars($pesan, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
            <p role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <section>
            <h2><?= $form['id'] === '' ? 'Tambah Transaksi' : 'Edit Transaksi' ?></h2>
            <form method="post" action="transaksi.php">
                <input type="hidden" name="id" value="<?= htmlspecialchars($form['id'], ENT_QUOTES, 'UTF-8') ?>">

                <label for="jenis">Jenis</label>
                <select id="jenis" name="jenis" required>
                    <option value="pemasukan" <?= $form['jenis'] === 'pemasukan' ? 'selected' : '' ?>>Pemasukan</option>
                    <option value="pengeluaran" <?= $form['jenis'] === 'pengeluaran' ? 'selected' : '' ?>>Pengeluaran</option>
                </select>

                <label for="kategori_id">Kategori</label>
                <select id="kategori_id" name="kategori_id" required>
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

                <label for="nominal">Nominal</label>
                <input
                    id="nominal"
                    type="number"
                    name="nominal"
                    min="1"
                    step="0.01"
                    value="<?= htmlspecialchars($form['nominal'], ENT_QUOTES, 'UTF-8') ?>"
                    required
                >

                <label for="tanggal">Tanggal</label>
                <input
                    id="tanggal"
                    type="date"
                    name="tanggal"
                    value="<?= htmlspecialchars($form['tanggal'], ENT_QUOTES, 'UTF-8') ?>"
                    required
                >

                <label for="catatan">Catatan</label>
                <textarea id="catatan" name="catatan" maxlength="10000"><?= htmlspecialchars($form['catatan'], ENT_QUOTES, 'UTF-8') ?></textarea>

                <button type="submit"><?= $form['id'] === '' ? 'Simpan' : 'Perbarui' ?></button>
                <?php if ($form['id'] !== ''): ?>
                    <a href="transaksi.php">Batal edit</a>
                <?php endif; ?>
            </form>
        </section>

        <section>
            <h2>Riwayat Transaksi</h2>
            <form method="get" action="transaksi.php">
                <label for="bulan">Bulan</label>
                <select id="bulan" name="bulan">
                    <option value="0" <?= $bulanForm === 0 ? 'selected' : '' ?>>Semua bulan</option>
                    <?php foreach ($namaBulan as $nomorBulan => $labelBulan): ?>
                        <option value="<?= $nomorBulan ?>" <?= $nomorBulan === $bulanForm ? 'selected' : '' ?>>
                            <?= htmlspecialchars($labelBulan, ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="tahun">Tahun</label>
                <select id="tahun" name="tahun">
                    <?php for ($tahun = $tahunMinimum; $tahun <= $tahunSekarang + 1; $tahun++): ?>
                        <option value="<?= $tahun ?>" <?= $tahun === $tahunForm ? 'selected' : '' ?>>
                            <?= $tahun ?>
                        </option>
                    <?php endfor; ?>
                </select>

                <label for="filter_jenis">Jenis</label>
                <select id="filter_jenis" name="filter_jenis">
                    <option value="" <?= $jenisFilter === '' ? 'selected' : '' ?>>Semua jenis</option>
                    <option value="pemasukan" <?= $jenisFilter === 'pemasukan' ? 'selected' : '' ?>>Pemasukan</option>
                    <option value="pengeluaran" <?= $jenisFilter === 'pengeluaran' ? 'selected' : '' ?>>Pengeluaran</option>
                </select>
                <button type="submit">Filter</button>
            </form>

            <table>
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Kategori</th>
                        <th>Jenis</th>
                        <th>Nominal</th>
                        <th>Catatan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$transaksiList): ?>
                        <tr><td colspan="6">Belum ada transaksi pada filter ini.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($transaksiList as $transaksi): ?>
                        <tr>
                            <td><?= htmlspecialchars($transaksi['tanggal'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($transaksi['nama_kategori'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars(ucfirst($transaksi['jenis']), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= format_rupiah($transaksi['nominal']) ?></td>
                            <td><?= nl2br(htmlspecialchars($transaksi['catatan'] ?? '', ENT_QUOTES, 'UTF-8')) ?></td>
                            <td>
                                <a href="transaksi.php?edit=<?= (int) $transaksi['id'] ?>">Edit</a>
                                <a
                                    href="transaksi.php?hapus=<?= (int) $transaksi['id'] ?>"
                                    onclick="return confirm('Yakin ingin menghapus data ini?')"
                                >Hapus</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </main>
</body>
</html>
