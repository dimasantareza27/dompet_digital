<?php
/**
 * admin/pengguna.php
 * =============================================
 * Manajemen akun mahasiswa (Aktif/Nonaktif & Reset Password).
 * 
 * TUGAS KAMU (Bagian PHP - proses aksi):
 * 1. Include ../config.php & cek_login('admin')
 * 2. Proses UBAH STATUS (GET ?toggle_status=id):
 *    - Ambil status saat ini
 *    - Toggle: aktif -> nonaktif, nonaktif -> aktif
 *    - UPDATE users SET status = ? WHERE id = ? AND role = 'mahasiswa'
 * 3. Proses RESET PASSWORD (GET ?reset=id):
 *    - Hash password default: password_hash('mahasiswa123', PASSWORD_DEFAULT)
 *    - UPDATE users SET password = ? WHERE id = ? AND role = 'mahasiswa'
 *    - Gunakan onclick="return confirm('Yakin ingin reset password?')"
 * 
 * TUGAS KAMU (Bagian HTML):
 * 1. Tabel daftar mahasiswa:
 *    - Kolom: No, Nama, Email, Status, Tanggal Daftar, Aksi
 *    - Tombol toggle status (Aktifkan / Nonaktifkan)
 *    - Tombol Reset Password
 * =============================================
 */

// TODO: Mulai coding di sini!
