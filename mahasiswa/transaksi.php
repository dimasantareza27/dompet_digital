<?php
/**
 * mahasiswa/transaksi.php
 * =============================================
 * CRUD Transaksi keuangan (semua dalam 1 file).
 * 
 * TUGAS KAMU (Bagian PHP - proses CRUD):
 * 1. Include ../config.php & cek_login('mahasiswa')
 * 2. Proses TAMBAH (POST) -> INSERT ke tabel transactions
 * 3. Proses EDIT (POST)   -> UPDATE tabel transactions WHERE id = ? AND user_id = ?
 * 4. Proses HAPUS (GET ?hapus=id) -> DELETE WHERE id = ? AND user_id = ?
 *    - Gunakan onclick="return confirm('Yakin ingin menghapus data ini?')"
 * 5. Proses FILTER -> filter berdasarkan bulan/tahun atau jenis
 * 6. Query daftar kategori dari tabel categories (untuk dropdown)
 * 
 * TUGAS KAMU (Bagian HTML):
 * 1. Form tambah/edit transaksi:
 *    - Dropdown jenis (pemasukan / pengeluaran)
 *    - Dropdown kategori (dari tabel categories)
 *    - Input nominal (type="number", min="1", required)
 *    - Input tanggal (type="date", required)
 *    - Textarea catatan (opsional)
 * 2. Filter: dropdown bulan, tahun, jenis + tombol "Filter"
 * 3. Tabel riwayat transaksi dengan kolom aksi (edit, hapus)
 * 
 * CATATAN:
 * - Semua query WAJIB pakai WHERE user_id = ? (isolasi data)
 * - Gunakan prepared statement untuk semua input user
 * - Gunakan htmlspecialchars() saat menampilkan catatan
 * =============================================
 */

// TODO: Mulai coding di sini!
