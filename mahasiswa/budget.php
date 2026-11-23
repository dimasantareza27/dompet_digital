<?php
/**
 * mahasiswa/budget.php
 * =============================================
 * Pengaturan budget (batas pengeluaran) bulanan.
 * 
 * TUGAS KAMU:
 * 1. Include ../config.php & cek_login('mahasiswa')
 * 2. Ambil bulan & tahun berjalan (date('n') dan date('Y'))
 * 3. Proses form SET/UPDATE budget:
 *    - Cek apakah sudah ada budget untuk bulan+tahun ini (SELECT)
 *    - Jika belum ada -> INSERT
 *    - Jika sudah ada -> UPDATE
 * 4. Hitung realisasi pengeluaran:
 *    - Query SUM pengeluaran bulan ini WHERE user_id = ?
 *    - Hitung persentase = (pengeluaran / batas_nominal) * 100
 * 5. Tampilkan HTML:
 *    a. Form input batas nominal (type="number", min="1")
 *    b. Info: batas nominal, total pengeluaran, sisa budget
 *    c. Progress bar / indikator warna:
 *       - Hijau  (< 80%)  = Aman
 *       - Kuning (80-99%) = Peringatan
 *       - Merah  (>= 100%) = Melebihi Batas
 * =============================================
 */

// TODO: Mulai coding di sini!
