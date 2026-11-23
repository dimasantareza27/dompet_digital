<?php
/**
 * admin/kategori.php
 * =============================================
 * CRUD Master Kategori transaksi (semua dalam 1 file).
 * 
 * TUGAS KAMU (Bagian PHP - proses CRUD):
 * 1. Include ../config.php & cek_login('admin')
 * 2. Proses TAMBAH (POST) -> INSERT ke tabel categories
 * 3. Proses EDIT (POST)   -> UPDATE tabel categories WHERE id = ?
 * 4. Proses HAPUS (GET ?hapus=id) -> DELETE WHERE id = ?
 *    - Gunakan onclick="return confirm('Yakin ingin menghapus?')"
 *    - CATATAN: FK ON DELETE RESTRICT -> tidak bisa hapus kategori 
 *      yang sudah dipakai di transaksi (handle error-nya!)
 * 
 * TUGAS KAMU (Bagian HTML):
 * 1. Form tambah/edit kategori:
 *    - Input nama kategori (required)
 *    - Dropdown jenis: pemasukan / pengeluaran
 * 2. Tabel daftar kategori:
 *    - Kolom: No, Nama Kategori, Jenis, Aksi (Edit, Hapus)
 * =============================================
 */

// TODO: Mulai coding di sini!
