<?php
/**
 * mahasiswa/tabungan.php
 * =============================================
 * CRUD Target Tabungan + Tambah Setoran (semua dalam 1 file).
 * 
 * TUGAS KAMU (Bagian PHP - proses CRUD):
 * 1. Include ../config.php & cek_login('mahasiswa')
 * 2. Proses TAMBAH TARGET (POST) -> INSERT ke savings_goals
 * 3. Proses EDIT TARGET (POST) -> UPDATE savings_goals WHERE id = ? AND user_id = ?
 * 4. Proses HAPUS TARGET (GET ?hapus=id) -> DELETE WHERE id = ? AND user_id = ?
 * 5. Proses TAMBAH SETORAN (POST):
 *    - UPDATE terkumpul_nominal = terkumpul_nominal + ? WHERE id = ? AND user_id = ?
 *    - Cek apakah terkumpul_nominal >= target_nominal
 *    - Jika ya -> UPDATE status = 'tercapai'
 * 
 * TUGAS KAMU (Bagian HTML):
 * 1. Form tambah/edit target tabungan:
 *    - Input nama target (required)
 *    - Input target nominal (type="number", min="1", required)
 *    - Input tenggat tanggal (type="date", opsional)
 * 2. Daftar target tabungan, masing-masing menampilkan:
 *    - Nama target, nominal target, terkumpul, sisa
 *    - Progress bar CSS (width: persentase%)
 *    - Status: "Berjalan" atau "Tercapai"
 *    - Form kecil "Tambah Setoran" (input nominal + tombol)
 *    - Tombol edit & hapus
 * =============================================
 */

// TODO: Mulai coding di sini!
