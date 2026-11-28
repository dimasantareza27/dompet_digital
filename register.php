<?php
require_once 'config.php';

$error = '';
$sukses = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama     = trim($_POST['nama']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    // Cek email sudah terdaftar atau belum
    $cek = mysqli_prepare($koneksi, "SELECT id FROM pengguna WHERE email = ?");
    mysqli_stmt_bind_param($cek, 's', $email);
    mysqli_stmt_execute($cek);
    mysqli_stmt_store_result($cek);

    if (mysqli_stmt_num_rows($cek) > 0) {
        $error = 'Email sudah terdaftar!';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = mysqli_prepare($koneksi, "INSERT INTO pengguna (nama, email, password, role) VALUES (?, ?, ?, 'mahasiswa')");
        mysqli_stmt_bind_param($stmt, 'sss', $nama, $email, $hash);

        if (mysqli_stmt_execute($stmt)) {
            $sukses = 'Registrasi berhasil! Silakan login.';
        } else {
            $error = 'Terjadi kesalahan, coba lagi.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi - Keuangan Mahasiswa</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;700&amp;family=Plus+Jakarta+Sans:wght@400;500;600;700&amp;family=Space+Mono:wght@400;700&amp;display=swap"
        rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=block"
        rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>

<body>
    <main class="auth-shell nb-grid-bg">
        <div class="auth-container">

            <!-- Brand Header -->
            <div class="auth-brand-row">
                <div class="auth-brand-left">
                    <div class="auth-brand-icon nb-shadow-sm">
                        <span class="material-symbols-outlined icon-24" aria-hidden="true">account_balance_wallet</span>
                    </div>
                    <div class="auth-brand-text">
                        <span class="font-headline text-headline-md leading-none">KEUANGAN.MHS</span>
                        <span class="font-label text-label-sm uppercase text-muted">SISTEM KEUANGAN MAHASISWA</span>
                    </div>
                </div>
                <!-- Hand-drawn Squiggle Doodle -->
                <svg width="42" height="16" viewBox="0 0 46 16" fill="none" aria-hidden="true">
                    <path d="M2 10C6 3 10 3 14 10C18 17 22 17 26 10C30 3 34 3 38 10C40 13 42 13 44 10" stroke="#000000"
                        stroke-width="2.5" stroke-linecap="round"></path>
                </svg>
            </div>

            <!-- Registration Card -->
            <section class="auth-card nb-shadow-lg">
                <!-- Card Top Header -->
                <header class="auth-card-header auth-card-header--lime">
                    <div class="auth-header-text">
                        <h1 class="font-headline text-headline-lg text-balance">Daftar Akun Mahasiswa</h1>
                        <p class="font-body text-body-md">Buat akun baru untuk mulai mencatat keuangan.</p>
                    </div>
                    <span class="auth-badge font-label text-label-sm uppercase">
                        <span>FR-01</span>
                    </span>
                </header>

                <!-- Form Body -->
                <div class="auth-body">
                    <?php if ($error): ?>
                        <div class="auth-alert bg-error nb-shadow-sm flex" role="alert">
                            <span class="material-symbols-outlined icon-20 shrink-0" aria-hidden="true">error</span>
                            <p class="font-label text-label-lg"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                    <?php endif; ?>
                    <?php if ($sukses): ?>
                        <div class="auth-alert bg-secondary nb-shadow-sm flex" role="status">
                            <span class="material-symbols-outlined icon-20 shrink-0" aria-hidden="true">check_circle</span>
                            <p class="font-label text-label-lg"><?= htmlspecialchars($sukses, ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" class="auth-form">
                        <div class="form-field">
                            <label for="nama" class="font-label text-label-lg">Nama Lengkap</label>
                            <input id="nama" name="nama" type="text" maxlength="100" autocomplete="name"
                                placeholder="Contoh: Rizky Pratama" required
                                value="<?= isset($nama) && !$sukses ? htmlspecialchars($nama, ENT_QUOTES, 'UTF-8') : '' ?>"
                                class="auth-input nb-shadow-sm font-body text-body-md">
                        </div>

                        <div class="form-field">
                            <label for="email" class="font-label text-label-lg">Email</label>
                            <input id="email" name="email" type="email" maxlength="100" autocomplete="email"
                                placeholder="rizky@kampus.ac.id" required
                                value="<?= isset($email) && !$sukses ? htmlspecialchars($email, ENT_QUOTES, 'UTF-8') : '' ?>"
                                class="auth-input nb-shadow-sm font-body text-body-md">
                        </div>

                        <div class="form-field">
                            <label for="password" class="font-label text-label-lg">Password</label>
                            <div class="password-wrap">
                                <input id="password" name="password" type="password" autocomplete="new-password"
                                    placeholder="Minimal 6 karakter" required
                                    class="auth-input auth-input--pwd nb-shadow-sm font-body text-body-md">
                                <button type="button" id="toggle-password" aria-label="Tampilkan password"
                                    class="toggle-pwd-btn">
                                    <span class="material-symbols-outlined icon-20" aria-hidden="true">visibility</span>
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="btn-submit nb-shadow nb-press font-label text-label-lg">
                            <span class="font-label text-label-lg">Daftar</span>
                            <span class="material-symbols-outlined icon-18" aria-hidden="true">person_add</span>
                        </button>
                    </form>
                </div>

                <!-- Card Footer -->
                <footer class="auth-footer">
                    <span class="font-body text-body-md text-muted">Sudah punya akun?</span>
                    <a href="login.php" class="auth-link font-label text-label-lg">
                        Login di sini
                    </a>
                </footer>
            </section>

            <!-- Default Role Note -->
            <div class="auth-note">
                <span class="material-symbols-outlined icon-18 shrink-0" aria-hidden="true">school</span>
                <p class="font-body text-body-md text-muted">
                    Pendaftaran otomatis membuat akun Mahasiswa aktif dengan enkripsi password.
                </p>
            </div>

        </div>
    </main>

    <script src="assets/app.js"></script>
</body>

</html>
