<?php
/**
 * mahasiswa/dashboard.php
 * =============================================
 * Dashboard utama mahasiswa - ringkasan keuangan.
 * 
 * TUGAS KAMU:
 * 1. Include ../config.php
 * 2. Panggil cek_login('mahasiswa') untuk proteksi halaman
 * 3. Query data keuangan (gunakan prepared statement + WHERE user_id = ?):
 *    a. Total Saldo = SUM(pemasukan) - SUM(pengeluaran) [keseluruhan]
 *    b. Pemasukan Bulan Ini = SUM pemasukan bulan & tahun berjalan
 *    c. Pengeluaran Bulan Ini = SUM pengeluaran bulan & tahun berjalan
 * 4. Cek budget bulan ini:
 *    - Jika pengeluaran >= 80% batas -> tampilkan banner peringatan
 * 5. Buat bar chart CSS murni:
 *    - Hitung persentase pemasukan vs pengeluaran
 *    - Set width: ...% dari PHP ke style inline
 * 6. Query 1 tips keuangan terbaru dari tabel financial_tips
 * 7. Tampilkan HTML dengan navbar + konten dashboard
 * =============================================
 */

// TODO: Mulai coding di sini!
