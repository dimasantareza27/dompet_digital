<?php
/**
 * admin/dashboard.php
 * =============================================
 * Dashboard Admin - statistik global (anonim).
 * 
 * TUGAS KAMU:
 * 1. Include ../config.php & cek_login('admin')
 * 2. Query statistik (TANPA data pribadi mahasiswa!):
 *    a. Total mahasiswa terdaftar -> COUNT(*) FROM users WHERE role='mahasiswa'
 *    b. Jumlah akun aktif -> COUNT(*) WHERE status='aktif'
 *    c. Jumlah akun nonaktif -> COUNT(*) WHERE status='nonaktif'
 *    d. Total seluruh transaksi di sistem -> COUNT(*) FROM transactions
 * 3. Tampilkan HTML:
 *    a. Navbar admin
 *    b. Kartu-kartu statistik (card) dengan angka-angka di atas
 * 
 * CATATAN:
 * - Admin TIDAK BOLEH melihat saldo, nominal, atau catatan 
 *   keuangan milik mahasiswa tertentu (prinsip privasi)
 * =============================================
 */

// TODO: Mulai coding di sini!
