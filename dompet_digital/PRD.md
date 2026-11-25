# Product Requirements Document (PRD)
## Aplikasi Manajemen Keuangan Mahasiswa (100% Full Native — Versi Ringkas)


---

## 1. Strict Constraints (Batasan Mutlak untuk Generate Code)

### 1.1 Tech Stack (100% Full Native & Ringkas)
* **Frontend:** HTML5, CSS3 (Flexbox & Grid dalam 1 file `style.css`).
* **Interaksi Ringan:** Tanpa file JavaScript terpisah (`main.js` tidak diperlukan). Gunakan:
  * Atribut validasi bawaan HTML5 (`required`, `type="number"`, `min="1"`).
  * Konfirmasi hapus inline: `onclick="return confirm('Yakin ingin menghapus data ini?')"`.
  * Cetak PDF inline: `onclick="window.print()"`.
  * Grafik Dashboard: *Bar chart* / *progress bar* **CSS murni** (mengatur `width: ...%` dari PHP).
* **Backend:** Native PHP (PHP 8.x prosedural sederhana). Proses CRUD (tampil + tambah/edit/hapus) digabung dalam satu file yang sama per fitur.
* **Database:** MySQL / MariaDB menggunakan `mysqli`.
* **LARANGAN KERAS (DO NOT USE):**
  * **TIDAK BOLEH** menggunakan *framework* PHP/CSS/JS (Laravel, CodeIgniter, Bootstrap, Tailwind, jQuery, React, dll.).
  * **TIDAK BOLEH** menggunakan *library* eksternal, Composer, NPM, maupun CDN (Chart.js, Dompdf, FPDF, FontAwesome CDN, Google Fonts CDN, dll.).

### 1.2 Scope Boundaries (Batasan Fitur)
* **OUT-OF-SCOPE (JANGAN DIBUAT):**
  * Tidak ada fitur **Split Bill**, patungan, utang-piutang, atau manajemen grup.
  * Tidak ada fitur **Ekspor CSV / Excel** (hanya cetak/simpan PDF via `@media print`).
  * Tidak ada integrasi API Bank atau E-Wallet (semua transaksi input manual).
  * Tidak perlu fitur keamanan kompleks seperti CSRF token, *rate limiting*, atau *email verification*.

### 1.3 Security & Data Integrity Rules (Standar Dasar Wajib)
1. **Password Hashing:** Gunakan `password_hash($password, PASSWORD_DEFAULT)` saat registrasi/reset dan `password_verify()` saat login.
2. **Cek Sesi & Role:** Panggil fungsi `cek_login('mahasiswa')` atau `cek_login('admin')` di baris paling atas setiap halaman terproteksi agar Mahasiswa tidak bisa membuka halaman Admin dan sebaliknya.
3. **Isolasi Data Mahasiswa:** Setiap query `SELECT`, `UPDATE`, dan `DELETE` pada halaman Mahasiswa wajib menyertakan `WHERE user_id = ?` dari `$_SESSION['user_id']` agar data antarpengguna tidak tertukar.
4. **Keamanan Dasar Query & Output:** Gunakan *Prepared Statements* untuk input user dan `htmlspecialchars()` saat menampilkan teks bebas (seperti nama, catatan, atau tips).
5. **Presisi Keuangan:** Semua kolom nominal uang di MySQL wajib bertipe `DECIMAL(15,2)`.

---

## 2. Ringkasan Produk & Aktor

Aplikasi ini adalah platform web *personal finance* bagi mahasiswa untuk mencatat arus kas harian, mengontrol batas pengeluaran bulanan, dan memantau target tabungan, dengan dukungan manajemen sistem oleh Administrator.

### 2.1 Aktor 1: Mahasiswa (`role = 'mahasiswa'`)
* Mengelola keuangan pribadinya secara mandiri (CRUD transaksi, budget bulanan, target tabungan, dan cetak laporan PDF).

### 2.2 Aktor 2: Administrator (`role = 'admin'`)
* **Prinsip Privasi:** Admin **TIDAK BOLEH** melihat saldo pribadi, nominal transaksi, atau catatan keuangan milik mahasiswa tertentu.
* **Fungsi Admin:**
  1. Melihat statistik global secara anonim (jumlah pengguna & total transaksi sistem).
  2. Mengelola akun mahasiswa (lihat daftar, ubah status Aktif/Nonaktif, reset password ke default).
  3. Mengelola Master Kategori transaksi bawaan sistem (CRUD).
  4. Mengelola konten Tips Keuangan yang tampil di dashboard mahasiswa (CRUD).

---

## 3. Matriks Hak Akses (RBAC)

| Modul / Fitur | Mahasiswa | Admin |
| :--- | :---: | :---: |
| Registrasi Akun Baru | Ya | Tidak (Seeder DB) |
| Login & Logout | Ya | Ya |
| Dashboard Keuangan Pribadi & Grafik CSS | Ya | Tidak |
| CRUD Transaksi, Budget Bulanan, & Target Tabungan | Ya | Tidak |
| Cetak / Simpan Laporan Keuangan PDF | Ya | Tidak |
| Dashboard Statistik Global (Anonim) | Tidak | Ya |
| Manajemen Pengguna (Aktif/Nonaktif/Reset) | Tidak | Ya |
| CRUD Master Kategori | Tidak | Ya |
| CRUD Tips Keuangan | Tidak | Ya |

---

## 4. Spesifikasi Database (MySQL — Nama Tabel Bahasa Indonesia)

Gunakan engine `InnoDB` dan charset `utf8mb4`.

### 4.1 Tabel `pengguna`
* `id` INT AUTO_INCREMENT PRIMARY KEY
* `nama` VARCHAR(100) NOT NULL
* `email` VARCHAR(100) NOT NULL UNIQUE
* `password` VARCHAR(255) NOT NULL
* `role` ENUM('admin', 'mahasiswa') NOT NULL DEFAULT 'mahasiswa'
* `status` ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'aktif'
* `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP

### 4.2 Tabel `kategori`
* `id` INT AUTO_INCREMENT PRIMARY KEY
* `nama_kategori` VARCHAR(100) NOT NULL
* `jenis` ENUM('pemasukan', 'pengeluaran') NOT NULL

### 4.3 Tabel `transaksi`
* `id` INT AUTO_INCREMENT PRIMARY KEY
* `user_id` INT NOT NULL (FK -> `pengguna.id` ON DELETE CASCADE)
* `kategori_id` INT NOT NULL (FK -> `kategori.id` ON DELETE RESTRICT)
* `jenis` ENUM('pemasukan', 'pengeluaran') NOT NULL
* `nominal` DECIMAL(15,2) NOT NULL
* `tanggal` DATE NOT NULL
* `catatan` TEXT NULL
* `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP

### 4.4 Tabel `anggaran` (Disederhanakan: 1 Batas Total per Bulan)
* `id` INT AUTO_INCREMENT PRIMARY KEY
* `user_id` INT NOT NULL (FK -> `pengguna.id` ON DELETE CASCADE)
* `bulan` TINYINT NOT NULL (1-12)
* `tahun` SMALLINT NOT NULL
* `batas_nominal` DECIMAL(15,2) NOT NULL

### 4.5 Tabel `target_tabungan`
* `id` INT AUTO_INCREMENT PRIMARY KEY
* `user_id` INT NOT NULL (FK -> `pengguna.id` ON DELETE CASCADE)
* `nama_target` VARCHAR(100) NOT NULL
* `target_nominal` DECIMAL(15,2) NOT NULL
* `terkumpul_nominal` DECIMAL(15,2) NOT NULL DEFAULT 0.00
* `tenggat_tanggal` DATE NULL
* `status` ENUM('berjalan', 'tercapai') NOT NULL DEFAULT 'berjalan'

### 4.6 Tabel `tips_keuangan`
* `id` INT AUTO_INCREMENT PRIMARY KEY
* `judul` VARCHAR(150) NOT NULL
* `konten` TEXT NOT NULL
* `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP

---

## 5. Kebutuhan Fungsional (Functional Requirements)

### 5.1 Modul Autentikasi
* **FR-01 (Registrasi — `register.php`):** Form pendaftaran khusus `mahasiswa` (nama, email, password). Validasi email unik dan simpan password dengan `password_hash()`.
* **FR-02 (Login — `login.php`):** Validasi email, password (`password_verify()`), dan pastikan `status === 'aktif'`. Redirect otomatis ke `admin/dashboard.php` atau `mahasiswa/dashboard.php`.
* **FR-03 (Logout — `logout.php`):** Hancurkan sesi (`session_destroy()`) dan arahkan ke `login.php`.

### 5.2 Modul Mahasiswa (`/mahasiswa/`)
* **FR-04 (Dashboard — `dashboard.php`):**
  * Kartu ringkasan: Total Saldo (keseluruhan pemasukan - pengeluaran), Pemasukan Bulan Ini, Pengeluaran Bulan Ini.
  * Banner peringatan budget otomatis jika total pengeluaran bulan ini mencapai ≥80% dari `anggaran.batas_nominal`.
  * Grafik perbandingan pemasukan vs pengeluaran bulan ini menggunakan *bar chart* CSS murni.
  * Menampilkan 1 tips keuangan terbaru dari tabel `tips_keuangan`.
* **FR-05 (Transaksi — `transaksi.php`):**
  * Form tambah/edit dan tabel riwayat transaksi dalam satu file.
  * Filter sederhana berdasarkan bulan/tahun atau jenis (`pemasukan`/`pengeluaran`).
* **FR-06 (Budget Bulanan — `budget.php`):**
  * Form sederhana untuk mengatur 1 batas pengeluaran total untuk bulan & tahun berjalan.
  * Hitung persentase: `(SUM pengeluaran bulan ini / batas_nominal) * 100`.
  * Indikator warna: `< 80%` Hijau (Aman), `80% - 99%` Kuning (Peringatan), `>= 100%` Merah (Melebihi Batas).
* **FR-07 (Target Tabungan — `tabungan.php`):**
  * CRUD target tabungan + form kecil "Tambah Setoran" yang menambah `terkumpul_nominal`.
  * Jika `terkumpul_nominal >= target_nominal`, ubah `status` otomatis menjadi `'tercapai'`.
  * Tampilkan *progress bar* CSS (0%–100%).
* **FR-08 (Laporan PDF — `laporan.php`):**
  * Pilih bulan & tahun → tampilkan kop laporan, ringkasan saldo periode tersebut, dan tabel transaksi.
  * Tombol **"Cetak / Simpan PDF"** dengan `onclick="window.print()"`.
  * Aturan `@media print` di bagian bawah `style.css` menyembunyikan menu navigasi, form filter, dan tombol cetak.

### 5.3 Modul Administrator (`/admin/`)
* **FR-09 (Dashboard Admin — `dashboard.php`):**
  * Menampilkan jumlah total mahasiswa terdaftar, jumlah akun aktif/nonaktif, dan total seluruh transaksi di sistem (anonim).
* **FR-10 (Manajemen Pengguna — `pengguna.php`):**
  * Tabel daftar `pengguna` dengan `role = 'mahasiswa'`.
  * Tombol ubah status (`aktif` <-> `nonaktif`) dan tombol **Reset Password** ke `mahasiswa123` (di-hash).
* **FR-11 (Master Kategori — `kategori.php`):**
  * CRUD `kategori` (nama kategori & jenis: `pemasukan` atau `pengeluaran`) dalam satu file.
* **FR-12 (Tips Keuangan — `tips.php`):**
  * CRUD `tips_keuangan` (judul & konten singkat) dalam satu file.

---

## 6. Struktur Folder Proyek (Versi Ringkas)

```text
Sistem_Keuangan_Mahasiswa/
├── PRD.md                  # Dokumen spesifikasi & batasan proyek
├── config.php              # Koneksi mysqli + session_start() + format_rupiah() + cek_login()
├── schema.sql              # Query CREATE TABLE + Seeder Admin & Master Kategori
├── index.php               # Redirect otomatis ke login / dashboard sesuai sesi
├── login.php               # Halaman login (Admin & Mahasiswa)
├── register.php            # Halaman pendaftaran akun Mahasiswa
├── logout.php              # Hapus sesi & redirect ke login
├── style.css               # CSS3 responsif + aturan @media print (PDF) di bagian bawah
├── mahasiswa/
│   ├── dashboard.php       # Ringkasan saldo, grafik CSS, alert budget, & tips terbaru
│   ├── transaksi.php       # Form + Tabel CRUD transaksi (jadi 1 file)
│   ├── budget.php          # Atur 1 batas pengeluaran total bulanan
│   ├── tabungan.php        # CRUD target tabungan + tambah setoran
│   └── laporan.php         # Rekap bulanan + tombol window.print()
└── admin/
    ├── dashboard.php       # Statistik sederhana (COUNT pengguna & transaksi)
    ├── pengguna.php        # Daftar mahasiswa + tombol Aktif/Nonaktif & Reset Password
    ├── kategori.php        # CRUD Master Kategori (jadi 1 file)
    └── tips.php            # CRUD Tips Keuangan (jadi 1 file)
```

---

## 7. Data Awal (Seeder Wajib di `schema.sql`)
1. **Akun Admin Default:** 1 akun dengan `role = 'admin'` (email: `admin@keuangan.test`, password di-hash dari `admin123`).
2. **Master Kategori Default:**
   * **Pemasukan:** Uang Saku, Beasiswa, Kerja Paruh Waktu, Lainnya.
   * **Pengeluaran:** Makan & Minum, Kos & Listrik, Transportasi, Tugas & Kuliah, Hiburan, Lainnya.

---

## 8. Referensi File yang Sudah Jadi

Beberapa file pondasi sudah dikerjakan dan **WAJIB dijadikan acuan** oleh semua anggota tim. Jangan ubah isi file-file ini tanpa koordinasi.

### 8.1 `config.php` (Sudah Jadi)
```php
<?php
session_start();

$koneksi = mysqli_connect('localhost', 'root', '', 'sistem_keuangan_mahasiswa');
if (!$koneksi) {
    die('Koneksi gagal: ' . mysqli_connect_error());
}

function format_rupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

function cek_login($role) {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== $role) {
        header('Location: /Sistem_Keuangan_Mahasiswa/login.php');
        exit;
    }
}
```

**Variabel & fungsi yang tersedia setelah `require_once '../config.php'`:**
* `$koneksi` — objek koneksi mysqli, gunakan untuk semua query.
* `format_rupiah($angka)` — format angka ke "Rp 1.000.000".
* `cek_login($role)` — proteksi halaman, panggil di baris pertama setelah require.
* `$_SESSION['user_id']` — ID user yang sedang login.
* `$_SESSION['nama']` — nama user yang sedang login.
* `$_SESSION['role']` — role user ('admin' atau 'mahasiswa').

### 8.2 `schema.sql` (Sudah Jadi)
Database: `sistem_keuangan_mahasiswa`. Nama tabel: `pengguna`, `kategori`, `transaksi`, `anggaran`, `target_tabungan`, `tips_keuangan`.

### 8.3 File Lain yang Sudah Jadi
* `index.php` — redirect berdasarkan sesi
* `logout.php` — destroy session & redirect
* `login.php` — backend + form login
* `register.php` — backend + form registrasi

---

## 9. Pembagian Tugas Tim (5 Anggota — Backend Dulu)

### Anggota 1 — Modul Autentikasi (Review)
| File | Status |
|---|---|
| `login.php` | ✅ Sudah jadi — review & pahami alurnya |
| `register.php` | ✅ Sudah jadi — review & pahami alurnya |

> Setelah review, bantu anggota lain atau mulai kerjakan frontend (styling) untuk `login.php` & `register.php`.

### Anggota 2 — Dashboard & Laporan Mahasiswa
| File | Tugas |
|---|---|
| `mahasiswa/dashboard.php` | Ringkasan saldo, grafik CSS, alert budget, 1 tips terbaru |
| `mahasiswa/laporan.php` | Rekap bulanan + tombol `window.print()` |

### Anggota 3 — Transaksi & Budget Mahasiswa
| File | Tugas |
|---|---|
| `mahasiswa/transaksi.php` | CRUD transaksi + filter bulan/tahun/jenis |
| `mahasiswa/budget.php` | Set batas pengeluaran bulanan + indikator warna |

### Anggota 4 — Target Tabungan Mahasiswa
| File | Tugas |
|---|---|
| `mahasiswa/tabungan.php` | CRUD target tabungan + tambah setoran + progress bar |

> Setelah selesai, bantu Anggota 2 atau 3.

### Anggota 5 — Semua Modul Admin
| File | Tugas |
|---|---|
| `admin/dashboard.php` | Statistik global (COUNT pengguna & transaksi) |
| `admin/pengguna.php` | Daftar mahasiswa + toggle status + reset password |
| `admin/kategori.php` | CRUD master kategori |
| `admin/tips.php` | CRUD tips keuangan |

---

## 10. Panduan Prompt AI untuk Anggota Tim

> **PENTING:** Setiap anggota tim yang menggunakan AI untuk coding **WAJIB** melampirkan file `PRD.md` ini sebagai konteks agar hasil kode tetap selaras.

### 10.1 Template Prompt

Salin dan sesuaikan prompt berikut saat meminta AI generate kode:

```
Saya sedang mengerjakan proyek kelompok "Sistem Keuangan Mahasiswa".
Saya lampirkan file PRD.md sebagai acuan utama.

Tugas saya: mengerjakan BACKEND file [NAMA_FILE].
Lihat bagian [NOMOR_FR] di PRD untuk spesifikasi lengkapnya.

Aturan wajib:
1. Baca dan patuhi SELURUH isi PRD.md yang dilampirkan (tech stack, larangan, keamanan).
2. Gunakan `require_once '../config.php'` di baris pertama.
3. Panggil `cek_login('mahasiswa')` atau `cek_login('admin')` sesuai modul.
4. Gunakan variabel `$koneksi` untuk query (mysqli prosedural).
5. Gunakan Prepared Statement untuk semua input user.
6. Tambahkan `WHERE user_id = $_SESSION['user_id']` di semua query modul mahasiswa.
7. Gunakan `htmlspecialchars()` saat menampilkan teks dari database.
8. Nama tabel: pengguna, kategori, transaksi, anggaran, target_tabungan, tips_keuangan.
9. Untuk HTML, buat minimal/sederhana saja (frontend dikerjakan nanti bareng-bareng).
10. JANGAN gunakan framework, library, CDN, atau JavaScript terpisah.

Generate kode PHP untuk file [NAMA_FILE] sesuai PRD.
```

### 10.2 Contoh Prompt Per Anggota

**Anggota 2 (Dashboard):**
```
Tugas saya: mengerjakan BACKEND file mahasiswa/dashboard.php.
Lihat bagian FR-04 di PRD untuk spesifikasi lengkapnya.
[... aturan wajib di atas ...]
```

**Anggota 3 (Transaksi):**
```
Tugas saya: mengerjakan BACKEND file mahasiswa/transaksi.php.
Lihat bagian FR-05 di PRD untuk spesifikasi lengkapnya.
[... aturan wajib di atas ...]
```

**Anggota 5 (Admin Kategori):**
```
Tugas saya: mengerjakan BACKEND file admin/kategori.php.
Lihat bagian FR-11 di PRD untuk spesifikasi lengkapnya.
[... aturan wajib di atas ...]
```

### 10.3 Mapping File → FR (Functional Requirement)

| File | FR | Deskripsi |
|---|---|---|
| `mahasiswa/dashboard.php` | FR-04 | Dashboard keuangan + grafik + alert + tips |
| `mahasiswa/transaksi.php` | FR-05 | CRUD transaksi + filter |
| `mahasiswa/budget.php` | FR-06 | Budget bulanan + indikator warna |
| `mahasiswa/tabungan.php` | FR-07 | Target tabungan + setoran + progress bar |
| `mahasiswa/laporan.php` | FR-08 | Laporan bulanan + cetak PDF |
| `admin/dashboard.php` | FR-09 | Statistik global anonim |
| `admin/pengguna.php` | FR-10 | Manajemen akun mahasiswa |
| `admin/kategori.php` | FR-11 | CRUD master kategori |
| `admin/tips.php` | FR-12 | CRUD tips keuangan |
