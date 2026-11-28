<?php
require_once 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = mysqli_prepare($koneksi, "SELECT id, nama, password, role, status FROM pengguna WHERE email = ?");
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user   = mysqli_fetch_assoc($result);

    if (!$user) {
        $error = 'Email tidak ditemukan!';
    } elseif (!password_verify($password, $user['password'])) {
        $error = 'Password salah!';
    } elseif ($user['status'] === 'nonaktif') {
        $error = 'Akun Anda dinonaktifkan. Hubungi admin.';
    } else {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['nama']    = $user['nama'];
        $_SESSION['role']    = $user['role'];

        if ($user['role'] === 'admin') {
            header("Location: {$base_path}/admin/dashboard.php");
        } else {
            header("Location: {$base_path}/mahasiswa/dashboard.php");
        }
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Keuangan Mahasiswa</title>
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
                <!-- Hand-drawn Sparkle Doodle -->
                <svg width="28" height="28" viewBox="0 0 32 32" fill="none" aria-hidden="true">
                    <path d="M16 2L19.5 12.5L30 16L19.5 19.5L16 30L12.5 19.5L2 16L12.5 12.5L16 2Z" fill="#FFD000"
                        stroke="#000000" stroke-width="2.2" stroke-linejoin="round"></path>
                </svg>
            </div>

            <!-- Auth Card -->
            <section class="auth-card nb-shadow-lg">
                <!-- Card Top Header -->
                <header class="auth-card-header">
                    <div class="auth-header-text">
                        <h1 class="font-headline text-headline-lg text-balance">Masuk ke Akun</h1>
                        <p class="font-body text-body-md">Kelola pemasukan, budget, dan tabungan kuliah.</p>
                    </div>
                    <span class="auth-badge font-label text-label-sm uppercase">
                        <span>FR-02</span>
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

                    <form method="POST" action="" class="auth-form">
                        <div class="form-field">
                            <label for="email" class="font-label text-label-lg">Email</label>
                            <input id="email" name="email" type="email" autocomplete="email"
                                placeholder="mahasiswa@kampus.ac.id" required
                                value="<?= isset($email) ? htmlspecialchars($email, ENT_QUOTES, 'UTF-8') : '' ?>"
                                class="auth-input nb-shadow-sm font-body text-body-md">
                        </div>

                        <div class="form-field">
                            <label for="password" class="font-label text-label-lg">Password</label>
                            <div class="password-wrap">
                                <input id="password" name="password" type="password" autocomplete="current-password"
                                    placeholder="Masukkan password Anda" required
                                    class="auth-input auth-input--pwd nb-shadow-sm font-body text-body-md">
                                <button type="button" id="toggle-password" aria-label="Tampilkan password"
                                    class="toggle-pwd-btn">
                                    <span class="material-symbols-outlined icon-20" aria-hidden="true">visibility</span>
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="btn-submit nb-shadow nb-press font-label text-label-lg">
                            <span class="font-label text-label-lg">Login</span>
                            <span class="material-symbols-outlined icon-18" aria-hidden="true">arrow_forward</span>
                        </button>
                    </form>
                </div>

                <!-- Card Footer -->
                <footer class="auth-footer">
                    <span class="font-body text-body-md text-muted">Belum punya akun?</span>
                    <a href="register.php" class="auth-link font-label text-label-lg">
                        Daftar di sini
                    </a>
                </footer>
            </section>

            <!-- Session & Role Routing Note -->
            <div class="auth-note">
                <span class="material-symbols-outlined icon-18 shrink-0" aria-hidden="true">shield_lock</span>
                <p class="font-body text-body-md text-muted">
                    Otomatis mengarahkan ke Dashboard Admin atau Mahasiswa sesuai hak akses aktif.
                </p>
            </div>

        </div>
    </main>

    <script src="assets/app.js"></script>
</body>

</html>
