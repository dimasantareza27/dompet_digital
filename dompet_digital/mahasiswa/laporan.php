<?php
/**
 * mahasiswa/laporan.php
 * =============================================
 * Laporan keuangan bulanan + cetak PDF via window.print().
 * 
 * TUGAS KAMU:
 * 1. Include ../config.php & cek_login('mahasiswa')
 * 2. Ambil parameter bulan & tahun dari GET (default: bulan & tahun ini)
 * 3. Query data laporan (semua WHERE user_id = ?):
 *    a. Total pemasukan periode terpilih
 *    b. Total pengeluaran periode terpilih
 *    c. Saldo periode = pemasukan - pengeluaran
 *    d. Daftar transaksi periode terpilih (JOIN dengan categories)
 * 4. Tampilkan HTML:
 *    a. Form filter: dropdown bulan + tahun, tombol "Tampilkan"
 *    b. Kop laporan (judul, nama mahasiswa, periode)
 *    c. Ringkasan: total pemasukan, pengeluaran, saldo
 *    d. Tabel transaksi (tanggal, kategori, jenis, nominal, catatan)
 *    e. Tombol "Cetak / Simpan PDF" -> onclick="window.print()"
 * 
 * CATATAN:
 * - Pastikan aturan @media print di style.css menyembunyikan 
 *   navbar, form filter, dan tombol cetak
 * =============================================
 */

// TODO: Mulai coding di sini!
