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
</head>
<body>
    <main>
        <h1>Target Tabungan</h1>

        <?php if ($pesan !== ''): ?>
            <p role="status"><?= htmlspecialchars($pesan, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
            <p role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <section>
            <h2><?= $form['id'] === '' ? 'Tambah Target' : 'Edit Target' ?></h2>
            <form method="post" action="tabungan.php">
                <input type="hidden" name="aksi" value="<?= $form['id'] === '' ? 'tambah' : 'edit' ?>">
                <input type="hidden" name="id" value="<?= htmlspecialchars($form['id'], ENT_QUOTES, 'UTF-8') ?>">

                <label for="nama_target">Nama target</label>
                <input
                    id="nama_target"
                    type="text"
                    name="nama_target"
                    maxlength="100"
                    value="<?= htmlspecialchars($form['nama_target'], ENT_QUOTES, 'UTF-8') ?>"
                    required
                >

                <label for="target_nominal">Target nominal</label>
                <input
                    id="target_nominal"
                    type="number"
                    name="target_nominal"
                    min="1"
                    step="0.01"
                    value="<?= htmlspecialchars($form['target_nominal'], ENT_QUOTES, 'UTF-8') ?>"
                    required
                >

                <label for="tenggat_tanggal">Tenggat tanggal (opsional)</label>
                <input
                    id="tenggat_tanggal"
                    type="date"
                    name="tenggat_tanggal"
                    value="<?= htmlspecialchars($form['tenggat_tanggal'], ENT_QUOTES, 'UTF-8') ?>"
                >
                <button type="submit"><?= $form['id'] === '' ? 'Tambah Target' : 'Simpan Perubahan' ?></button>
                <?php if ($form['id'] !== ''): ?>
                    <a href="tabungan.php">Batal edit</a>
                <?php endif; ?>
            </form>
        </section>

        <section>
            <h2>Daftar Target</h2>
            <?php if (!$targetList): ?>
                <p>Belum ada target tabungan.</p>
            <?php endif; ?>

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
                <article>
                    <h3><?= htmlspecialchars($target['nama_target'], ENT_QUOTES, 'UTF-8') ?></h3>
                    <p>Target: <?= format_rupiah($target['target_nominal']) ?></p>
                    <p>Terkumpul: <?= format_rupiah($target['terkumpul_nominal']) ?></p>
                    <p>Sisa: <?= format_rupiah($sisa) ?></p>
                    <p>Status: <?= htmlspecialchars($statusTampil, ENT_QUOTES, 'UTF-8') ?></p>
                    <?php if ($target['tenggat_tanggal'] !== null): ?>
                        <p>Tenggat: <?= htmlspecialchars($target['tenggat_tanggal'], ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endif; ?>
                    <div
                        role="progressbar"
                        aria-label="Progress <?= htmlspecialchars($target['nama_target'], ENT_QUOTES, 'UTF-8') ?>"
                        aria-valuemin="0"
                        aria-valuemax="100"
                        aria-valuenow="<?= round($persentase, 2) ?>"
                        style="width: 100%; height: 20px; background-color: #e9ecef;"
                    >
                        <div style="width: <?= round($persentase, 2) ?>%; height: 20px; background-color: #198754;"></div>
                    </div>

                    <?php if ($target['status'] === 'berjalan'): ?>
                        <form method="post" action="tabungan.php">
                            <input type="hidden" name="aksi" value="setoran">
                            <input type="hidden" name="id" value="<?= (int) $target['id'] ?>">
                            <label for="setoran_<?= (int) $target['id'] ?>">Tambah Setoran</label>
                            <input
                                id="setoran_<?= (int) $target['id'] ?>"
                                type="number"
                                name="nominal_setoran"
                                min="1"
                                step="0.01"
                                required
                            >
                            <button type="submit">Setor</button>
                        </form>
                    <?php endif; ?>
                    <a href="tabungan.php?edit=<?= (int) $target['id'] ?>">Edit</a>
                    <a
                        href="tabungan.php?hapus=<?= (int) $target['id'] ?>"
                        onclick="return confirm('Yakin ingin menghapus data ini?')"
                    >Hapus</a>
                </article>
            <?php endforeach; ?>
        </section>
    </main>
</body>
</html>
