<?php
/**
 * admin/tips.php
 * =============================================
 * CRUD Tips Keuangan (semua dalam 1 file).
 * 
 * TUGAS KAMU (Bagian PHP - proses CRUD):
 * 1. Include ../config.php & cek_login('admin')
 * 2. Proses TAMBAH (POST) -> INSERT ke tabel financial_tips
 * 3. Proses EDIT (POST)   -> UPDATE tabel financial_tips WHERE id = ?
 * 4. Proses HAPUS (GET ?hapus=id) -> DELETE WHERE id = ?
 *    - Gunakan onclick="return confirm('Yakin ingin menghapus?')"
 * 
 * TUGAS KAMU (Bagian HTML):
 * 1. Form tambah/edit tips:
 *    - Input judul (required)
 *    - Textarea konten (required)
 * 2. Tabel daftar tips keuangan:
 *    - Kolom: No, Judul, Konten (dipotong singkat), Tanggal, Aksi (Edit, Hapus)
 * 
 * CATATAN:
 * - Gunakan htmlspecialchars() saat menampilkan judul & konten
 * =============================================
 */

// TODO: Mulai coding di sini!
