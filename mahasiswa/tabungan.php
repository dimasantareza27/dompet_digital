<?php
require_once '../config.php';
cek_login('mahasiswa');

$userId = (int) $_SESSION['user_id'];
$pesan = '';
$error = '';
$form = [
    'id' => '',
    'nama_target' => '',
    'target_nominal' => '',
    'tenggat_tanggal' => '',
];

$pesanValid = [
    'ditambahkan' => 'Target tabungan berhasil ditambahkan.',
    'diperbarui' => 'Target tabungan berhasil diperbarui.',
    'dihapus' => 'Target tabungan berhasil dihapus.',
    'setoran' => 'Setoran berhasil ditambahkan.',
];
$pesanQuery = $_GET['pesan'] ?? '';
if (is_string($pesanQuery) && isset($pesanValid[$pesanQuery])) {
    $pesan = $pesanValid[$pesanQuery];
}

if (isset($_GET['hapus'])) {
    $idHapus = is_string($_GET['hapus'])
        ? filter_var($_GET['hapus'], FILTER_VALIDATE_INT)
        : false;
    if ($idHapus === false || $idHapus < 1) {
        $error = 'Target yang akan dihapus tidak valid.';
    } else {
        $stmt = mysqli_prepare(
            $koneksi,
            'DELETE FROM target_tabungan WHERE id = ? AND user_id = ?'
        );
        mysqli_stmt_bind_param($stmt, 'ii', $idHapus, $userId);
        mysqli_stmt_execute($stmt);
        $berhasilDihapus = mysqli_stmt_affected_rows($stmt) > 0;
        mysqli_stmt_close($stmt);

        if ($berhasilDihapus) {
            header('Location: tabungan.php?pesan=dihapus');
            exit;
        }

        $error = 'Target tidak ditemukan atau bukan milik akun Anda.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksiInput = $_POST['aksi'] ?? '';
    $idInput = $_POST['id'] ?? '';

    if (!is_string($aksiInput) || !is_string($idInput)) {
        $error = 'Data permintaan tidak valid.';
    } elseif ($aksiInput === 'setoran') {
        $idTarget = filter_var($idInput, FILTER_VALIDATE_INT);
        $nominalInput = $_POST['nominal_setoran'] ?? '';

        if ($idTarget === false || $idTarget < 1 || !is_string($nominalInput)) {
            $error = 'Data setoran tidak valid.';
        } elseif (!preg_match('/\A\d{1,13}(?:\.\d{1,2})?\z/', $nominalInput) || (float) $nominalInput < 1) {
            $error = 'Nominal setoran harus minimal 1 dan maksimal memiliki dua angka desimal.';
        } else {
            $stmt = mysqli_prepare(
                $koneksi,
                "UPDATE target_tabungan
                 SET status = IF(terkumpul_nominal + ? >= target_nominal, 'tercapai', 'berjalan'),
                     terkumpul_nominal = terkumpul_nominal + ?
                 WHERE id = ? AND user_id = ? AND status = 'berjalan'"
            );
            mysqli_stmt_bind_param(
                $stmt,
                'ssii',
                $nominalInput,
                $nominalInput,
                $idTarget,
                $userId
            );
            mysqli_stmt_execute($stmt);
            $berhasilSetor = mysqli_stmt_affected_rows($stmt) > 0;
            mysqli_stmt_close($stmt);

            if ($berhasilSetor) {
                header('Location: tabungan.php?pesan=setoran');
                exit;
            }

            $error = 'Target tidak ditemukan, bukan milik akun Anda, atau sudah tercapai.';
        }
    } elseif ($aksiInput === 'tambah' || $aksiInput === 'edit') {
        $namaInput = $_POST['nama_target'] ?? '';
        $targetInput = $_POST['target_nominal'] ?? '';
        $tenggatInput = $_POST['tenggat_tanggal'] ?? '';
        $form = [
            'id' => $idInput,
            'nama_target' => is_string($namaInput) ? trim($namaInput) : '',
            'target_nominal' => is_string($targetInput) ? trim($targetInput) : '',
            'tenggat_tanggal' => is_string($tenggatInput) ? $tenggatInput : '',
        ];

        if (!is_string($namaInput) || !is_string($targetInput) || !is_string($tenggatInput)) {
            $error = 'Data target tidak valid.';
        } elseif ($form['nama_target'] === '' || strlen($form['nama_target']) > 100) {
            $error = 'Nama target wajib diisi dan maksimal 100 karakter.';
        } elseif (
            !preg_match('/\A\d{1,13}(?:\.\d{1,2})?\z/', $form['target_nominal'])
            || (float) $form['target_nominal'] < 1
        ) {
            $error = 'Target nominal harus minimal 1 dan maksimal memiliki dua angka desimal.';
        } else {
            $tenggat = null;
            if ($form['tenggat_tanggal'] !== '') {
                $tanggalObj = DateTime::createFromFormat('!Y-m-d', $form['tenggat_tanggal']);
                if (!$tanggalObj || $tanggalObj->format('Y-m-d') !== $form['tenggat_tanggal']) {
                    $error = 'Tanggal tenggat tidak valid.';
                } else {
                    $tenggat = $form['tenggat_tanggal'];
                }
            }
        }

        if ($error === '' && $aksiInput === 'tambah') {
            $stmt = mysqli_prepare(
                $koneksi,
                "INSERT INTO target_tabungan (user_id, nama_target, target_nominal, tenggat_tanggal)
                 VALUES (?, ?, ?, ?)"
            );
            mysqli_stmt_bind_param(
                $stmt,
                'isss',
                $userId,
                $form['nama_target'],
                $form['target_nominal'],
                $tenggat
            );
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            header('Location: tabungan.php?pesan=ditambahkan');
            exit;
        }

        if ($error === '' && $aksiInput === 'edit') {
            $idTarget = filter_var($idInput, FILTER_VALIDATE_INT);
            if ($idTarget === false || $idTarget < 1) {
                $error = 'Target yang akan diperbarui tidak valid.';
            } else {
                $stmt = mysqli_prepare(
                    $koneksi,
                    'SELECT id FROM target_tabungan WHERE id = ? AND user_id = ?'
                );
                mysqli_stmt_bind_param($stmt, 'ii', $idTarget, $userId);
                mysqli_stmt_execute($stmt);
                $targetDitemukan = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
                mysqli_stmt_close($stmt);

                if ($targetDitemukan) {
                    $stmt = mysqli_prepare(
                        $koneksi,
                        'UPDATE target_tabungan
                         SET status = IF(terkumpul_nominal >= ?, \'tercapai\', \'berjalan\'),
                             nama_target = ?, target_nominal = ?, tenggat_tanggal = ?
                         WHERE id = ? AND user_id = ?'
                    );
                    mysqli_stmt_bind_param(
                        $stmt,
                        'ssssii',
                        $form['target_nominal'],
                        $form['nama_target'],
                        $form['target_nominal'],
                        $tenggat,
                        $idTarget,
                        $userId
                    );
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                    header('Location: tabungan.php?pesan=diperbarui');
                    exit;
                }

                $error = 'Target tidak ditemukan atau bukan milik akun Anda.';
            }
        }
    } else {
        $error = 'Aksi tidak dikenali.';
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && isset($_GET['edit'])) {
    $idEdit = is_string($_GET['edit'])
        ? filter_var($_GET['edit'], FILTER_VALIDATE_INT)
        : false;
    if ($idEdit === false || $idEdit < 1) {
        $error = 'Target yang akan diedit tidak valid.';
    } else {
        $stmt = mysqli_prepare(
            $koneksi,
            'SELECT id, nama_target, target_nominal, tenggat_tanggal
             FROM target_tabungan
             WHERE id = ? AND user_id = ?'
        );
        mysqli_stmt_bind_param($stmt, 'ii', $idEdit, $userId);
        mysqli_stmt_execute($stmt);
        $targetEdit = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        if (!$targetEdit) {
            $error = 'Target tidak ditemukan atau bukan milik akun Anda.';
        } else {
            $form = [
                'id' => (string) $targetEdit['id'],
                'nama_target' => $targetEdit['nama_target'],
                'target_nominal' => (string) $targetEdit['target_nominal'],
                'tenggat_tanggal' => $targetEdit['tenggat_tanggal'] ?? '',
            ];
        }
    }
}

$stmt = mysqli_prepare(
    $koneksi,
    'SELECT id, nama_target, target_nominal, terkumpul_nominal, tenggat_tanggal, status
     FROM target_tabungan
     WHERE user_id = ?
     ORDER BY id DESC'
);
mysqli_stmt_bind_param($stmt, 'i', $userId);
mysqli_stmt_execute($stmt);
$targetList = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
mysqli_stmt_close($stmt);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Target Tabungan - Keuangan Mahasiswa</title>
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
                    <a href="budget.php" class="nav-link font-label text-label-lg">
                        <span class="font-label text-label-lg">Budget</span>
                    </a>
                    <a href="tabungan.php" aria-current="page" class="nav-link nav-link--active font-label text-label-lg">
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

            <h1 class="font-display text-display-responsive">Target Tabungan</h1>

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

            <!-- Form Tambah / Edit Target -->
            <section class="panel-card nb-shadow-lg">
                <header class="panel-header-lime">
                    <h2 class="font-headline text-headline-md">
                        <?= $form['id'] === '' ? 'Tambah Target Baru' : 'Edit Target Tabungan' ?>
                    </h2>
                </header>

                <form class="target-form" method="post" action="tabungan.php">
                    <input type="hidden" name="aksi" value="<?= $form['id'] === '' ? 'tambah' : 'edit' ?>">
                    <input type="hidden" name="id" value="<?= htmlspecialchars($form['id'], ENT_QUOTES, 'UTF-8') ?>">

                    <div class="col-md-5 form-field">
                        <label for="nama_target" class="font-label text-label-lg">Nama Target</label>
                        <input
                            id="nama_target"
                            name="nama_target"
                            type="text"
                            maxlength="100"
                            placeholder="Contoh: Laptop Kuliah Baru"
                            value="<?= htmlspecialchars($form['nama_target'], ENT_QUOTES, 'UTF-8') ?>"
                            required
                            class="form-control nb-shadow-sm font-body text-body-md"
                        >
                    </div>

                    <div class="col-md-3 form-field">
                        <label for="target_nominal" class="font-label text-label-lg">Target Nominal (Rp)</label>
                        <input
                            id="target_nominal"
                            name="target_nominal"
                            type="number"
                            min="1"
                            step="0.01"
                            placeholder="12000000"
                            value="<?= htmlspecialchars($form['target_nominal'], ENT_QUOTES, 'UTF-8') ?>"
                            required
                            class="form-control nb-shadow-sm font-body text-body-md tabular-nums"
                        >
                    </div>

                    <div class="col-md-2 form-field">
                        <label for="tenggat_tanggal" class="font-label text-label-lg">Tenggat (Opsional)</label>
                        <input
                            id="tenggat_tanggal"
                            name="tenggat_tanggal"
                            type="date"
                            value="<?= htmlspecialchars($form['tenggat_tanggal'], ENT_QUOTES, 'UTF-8') ?>"
                            class="form-control nb-shadow-sm font-body text-body-md"
                        >
                    </div>

                    <div class="col-md-2" style="display:flex; flex-direction:column; gap:6px;">
                        <?php if ($form['id'] !== ''): ?>
                            <a href="tabungan.php" class="btn btn--card nb-shadow-sm nb-press font-label text-label-sm" style="text-align:center;">Batal</a>
                        <?php endif; ?>
                        <button type="submit" class="btn btn--full btn--primary nb-shadow nb-press font-label text-label-lg">
                            <span class="font-label text-label-lg"><?= $form['id'] === '' ? 'Tambah Target' : 'Simpan' ?></span>
                        </button>
                    </div>
                </form>
            </section>

            <!-- Daftar Target Tabungan -->
            <section aria-label="Daftar target tabungan">
                <?php if (!$targetList): ?>
                    <div class="empty-state-card nb-shadow-lg" style="background-color: var(--light-surface-card); border: 2px solid var(--outline); border-radius: var(--radius-md); padding: 48px 24px; text-align: center; display: flex; flex-direction: column; align-items: center; gap: 12px;">
                        <span class="material-symbols-outlined icon-32 text-muted" aria-hidden="true" style="font-size: 48px;">savings</span>
                        <h2 class="font-headline text-headline-md">Belum ada target tabungan.</h2>
                        <p class="font-body text-body-md text-muted">Gunakan formulir di atas untuk menambahkan target tabungan pertama Anda.</p>
                    </div>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 20px;">
                        <?php foreach ($targetList as $target): ?>
                            <?php
                            $targetNominal = (float) $target['target_nominal'];
                            $terkumpul = (float) $target['terkumpul_nominal'];
                            $sisa = max(0, $targetNominal - $terkumpul);
                            $persentase = $targetNominal > 0
                                ? min(100, max(0, ($terkumpul / $targetNominal) * 100))
                                : 0;
                            $statusTampil = $target['status'] === 'tercapai' ? 'Tercapai' : 'Berjalan';
                            ?>
                            <article class="panel-card nb-shadow-lg">
                                <header class="panel-header-beige" style="display: flex; align-items: center; justify-content: space-between;">
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        <h2 class="font-headline text-headline-md"><?= htmlspecialchars($target['nama_target'], ENT_QUOTES, 'UTF-8') ?></h2>
                                        <span class="badge-status font-label text-label-sm uppercase <?= $target['status'] === 'tercapai' ? 'bg-secondary' : 'bg-primary' ?>">
                                            <?= htmlspecialchars($statusTampil, ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </div>
                                    <div style="display: inline-flex; gap: 8px;">
                                        <a href="tabungan.php?edit=<?= (int) $target['id'] ?>" class="btn btn--card nb-shadow-sm nb-press font-label text-label-sm" style="min-height: 36px; padding: 4px 12px;">Edit</a>
                                        <a
                                            href="tabungan.php?hapus=<?= (int) $target['id'] ?>"
                                            onclick="return confirm('Yakin ingin menghapus data ini?')"
                                            class="btn btn--error nb-shadow-sm nb-press font-label text-label-sm"
                                            style="min-height: 36px; padding: 4px 12px;"
                                        >Hapus</a>
                                    </div>
                                </header>

                                <div style="padding: 24px; display: flex; flex-direction: column; gap: 20px;">
                                    <div class="stat-grid">
                                        <div class="stat-box">
                                            <span class="font-label text-label-sm uppercase text-muted">TARGET NOMINAL</span>
                                            <span class="font-headline text-headline-md tabular-nums"><?= format_rupiah($target['target_nominal']) ?></span>
                                        </div>
                                        <div class="stat-box">
                                            <span class="font-label text-label-sm uppercase text-muted">TERKUMPUL</span>
                                            <span class="font-headline text-headline-md tabular-nums"><?= format_rupiah($target['terkumpul_nominal']) ?></span>
                                        </div>
                                        <div class="stat-box">
                                            <span class="font-label text-label-sm uppercase text-muted">SISA</span>
                                            <span class="font-headline text-headline-md tabular-nums"><?= format_rupiah($sisa) ?></span>
                                        </div>
                                    </div>

                                    <div class="progress-section">
                                        <div class="progress-header">
                                            <span class="font-headline text-title-md">Progress: <?= round($persentase, 2) ?>%</span>
                                            <?php if ($target['tenggat_tanggal'] !== null): ?>
                                                <span class="font-label text-label-sm uppercase text-muted">Tenggat: <?= htmlspecialchars($target['tenggat_tanggal'], ENT_QUOTES, 'UTF-8') ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div role="progressbar" aria-label="Progress <?= htmlspecialchars($target['nama_target'], ENT_QUOTES, 'UTF-8') ?>" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= round($persentase, 2) ?>" class="progress-track">
                                            <div class="progress-fill <?= $target['status'] === 'tercapai' ? 'bg-secondary' : 'bg-primary' ?>" style="width: <?= round($persentase, 2) ?>%;"></div>
                                        </div>
                                    </div>

                                    <?php if ($target['status'] === 'berjalan'): ?>
                                        <div style="padding-top: 16px; border-top: 2px solid var(--outline);">
                                            <form method="post" action="tabungan.php" style="display: flex; flex-wrap: wrap; gap: 12px; align-items: center;">
                                                <input type="hidden" name="aksi" value="setoran">
                                                <input type="hidden" name="id" value="<?= (int) $target['id'] ?>">
                                                <label for="setoran_<?= (int) $target['id'] ?>" class="font-label text-label-lg">Tambah Setoran (Rp):</label>
                                                <input
                                                    id="setoran_<?= (int) $target['id'] ?>"
                                                    type="number"
                                                    name="nominal_setoran"
                                                    min="1"
                                                    step="0.01"
                                                    placeholder="Contoh: 50000"
                                                    required
                                                    class="form-control nb-shadow-sm font-body text-body-md tabular-nums"
                                                    style="max-width: 260px; min-height: 40px;"
                                                >
                                                <button type="submit" class="btn btn--lime nb-shadow nb-press font-label text-label-lg" style="min-height: 40px;">
                                                    <span class="material-symbols-outlined icon-18" aria-hidden="true">add_circle</span>
                                                    <span>Setor</span>
                                                </button>
                                            </form>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </div>

    <!-- Global Toast Notification Stack -->
    <div id="nb-toast-stack" class="hidden toast-stack"></div>

    <script src="../assets/app.js"></script>
</body>

</html>
