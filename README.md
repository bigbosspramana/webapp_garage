# Sistem Informasi Sewa Garasi Kendaraan (Smart Multi-Level Garage Booking System)

Aplikasi manajemen dan persewaan garasi kendaraan bertingkat berbasis web (*full-stack*) yang dirancang khusus untuk memenuhi standar sertifikasi kompetensi **LSP Pemrogram Komputer** (Skema Pemrogram). Sistem ini memfasilitasi operasional persewaan garasi untuk berbagai tipe kendaraan (Sepeda, Sepeda Motor, Mobil, dan Mobil Listrik / EV) lengkap dengan kalkulasi pengisian daya listrik, pemesanan berbasis denah gedung bertingkat, verifikasi identitas, perpanjangan sewa, serta laporan rekapitulasi real-time.

---

## 1. Panduan Menjalankan Aplikasi di Lokal (Local Setup)

Ikuti langkah-langkah di bawah ini untuk menjalankan backend dan frontend di komputer lokal Anda:

### A. Prasyarat Sistem (Prerequisites)
Pastikan perangkat Anda telah terpasang:
- **PHP** versi 8.2 atau lebih baru (disarankan PHP 8.3/8.4)
- **Composer** (Package manager PHP)
- **Node.js** versi 18 atau lebih baru & **npm**
- **MySQL Database Server** (melalui XAMPP, Laragon, Docker, atau MySQL Server langsung)

---

### B. Menjalankan Backend (Laravel 13 API)

1. **Buka terminal dan navigasikan ke direktori backend**:
   ```bash
   cd garage/garage_backend
   ```

2. **Pasang dependensi PHP via Composer**:
   ```bash
   composer install
   ```

3. **Konfigurasi Environment (`.env`)**:
   Salin file `.env.example` menjadi `.env` jika belum ada:
   ```bash
   copy .env.example .env
   ```
   Pastikan pengaturan database pada file `.env` sudah sesuai dengan MySQL lokal Anda:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=garage_db
   DB_USERNAME=root
   DB_PASSWORD=
   ```
   *(Pastikan database `garage_db` sudah dibuat di phpMyAdmin / MySQL)*.

4. **Generate Application Key & Storage Symlink**:
   ```bash
   php artisan key:generate
   php artisan storage:link
   ```

5. **Migrasi Database & Seeder Data Awal**:
   Perintah ini akan membuat seluruh tabel dan mengisi data awal (garasi S/C/E 48 slot, akun admin, penyewa, kendaraan, dan transaksi contoh):
   ```bash
   php artisan migrate:fresh --seed
   ```

6. **Jalankan Server Backend**:
   ```bash
   php artisan serve --port=8000
   ```
   Backend API sekarang aktif di: `http://127.0.0.1:8000`

---

### C. Menjalankan Frontend (React.js)

1. **Buka terminal baru dan masuk ke direktori frontend**:
   ```bash
   cd garage/garage_frontend
   ```

2. **Pasang seluruh dependensi Node.js**:
   ```bash
   npm install
   ```

3. **Jalankan Development Server Frontend**:
   ```bash
   npm start
   ```
   Frontend akan terbuka otomatis di browser pada: `http://localhost:3000`

---

### D. Akun Pengujian Siap Pakai (Default Credentials)

| Role / Peran | Email | Password | Keterangan |
| :--- | :--- | :--- | :--- |
| **Administrator** | `admin@gmail.com` | `admin123` | Akses penuh dashboard Admin, verifikasi KTP, perpanjangan sewa, dan laporan |
| **Penyewa (Approved)** | `budi@gmail.com` | `password123` | Akun aktif terverifikasi; memiliki kendaraan dan dapat langsung menyewa garasi |
| **Penyewa (Pending)** | `sari@gmail.com` | `password123` | Akun baru menunggu persetujuan verifikasi KTP oleh Admin |

---

## 2. Tech Stack yang Digunakan

### Backend Arsitektur
- **Bahasa Pemrograman**: PHP 8.4
- **Framework**: Laravel 13 (RESTful API Architecture)
- **Autentikasi & Otorisasi**: Laravel Sanctum (Bearer Token)
- **Database**: MySQL 8.0 / MariaDB
- **ORM**: Eloquent ORM
- **Testing Framework**: PHPUnit 12

### Frontend Arsitektur
- **Framework UI**: React.js 18 (Hooks-based: `useState`, `useEffect`, `useMemo`, `useCallback`, `useRef`)
- **HTTP Client**: Fetch API terintegrasi dengan modul sentral (`src/services/api.js`)
- **Desain & Tampilan**: Pure CSS3 Modern Responsive (Desain mobile app frame untuk penyewa & desktop admin)
- **Bundler / Toolchain**: React Scripts (Create React App)

---

## 3. Penjelasan Library & Pre-Existing Components (UK: J.620100.019.02)

| No | Nama Library / Komponen | Versi | Penjelasan dan Fungsi Penggunaan dalam Aplikasi |
| :---: | :--- | :---: | :--- |
| 1 | **Laravel Framework** | `13.x` | Kerangka kerja backend utama untuk menangani routing REST API, request validation, middleware, dan interaksi basis data. |
| 2 | **Laravel Sanctum** | `^4.0` | Sistem autentikasi token berbasis API (Personal Access Token) yang ringan untuk mengamankan endpoint pengguna dan admin. |
| 3 | **Carbon** | `^3.8` | Library manipulasi tanggal dan waktu pada PHP untuk perhitungan durasi sewa, batas kabisat bulan Februari, dan periode laporan. |
| 4 | **PHPUnit** | `12.5.x` | Library otomatisasi pengujian unit (Unit Testing) untuk mengeksekusi skenario uji fungsional resmi UT-01 s.d. UT-11. |
| 5 | **React.js** | `18.3.x` | Library UI JavaScript berbasis komponen deklaratif untuk membangun antarmuka pengguna interaktif dan responsif. |
| 6 | **Lucide Icons** / SVG Icons | Custom SVG | Kumpulan komponen ikon vektor (mobil, motor, sepeda, kilat baterai, garasi, kalender, upload, tempat sampah) untuk memperkuat visual UX. |

---

## 4. Fitur-Fitur Utama Aplikasi

### A. Fitur Portal Penyewa (User Portal)
1. **Pendaftaran & Validasi NIK**: Formulir registrasi penyewa dengan validasi 16 digit NIK, nomor telepon, email unik, dan status awal `pending`.
2. **Proteksi Login Akun**: Penyewa yang belum diverifikasi atau ditolak admin tidak dapat login ke sistem.
3. **Manajemen Kendaraan Saya**:
   - Menambahkan dan menghapus data kendaraan (Sepeda, Sepeda Motor, Mobil, Mobil Listrik).
   - Validasi nomor plat (wajib untuk motor/mobil, opsional untuk sepeda).
   - Input kapasitas baterai (kWh) khusus mobil listrik.
4. **Sewa Garasi 3 Tahap Terpadu**:
   - **Tahap 1 (Formulir Sewa)**: Memilih kendaraan, tipe garasi terkunci otomatis sesuai kendaraan, durasi sewa harian / bulanan, dan pop-up kalender tanggal selesai yang membatasi durasi sewa harian (maksimal hari dalam bulan $- 1$).
   - **Tahap 2 (Visualisasi Gedung Garasi & Lift)**: Denah bertingkat 2 kolom slot garasi (kiri & kanan) dengan lift di bagian tengah, dilengkapi scroll vertikal untuk lantai $\ge 10$, indikator slot tersedia/terisi, serta pergerakan indikator lift.
   - **Tahap 3 (Rincian Biaya & Upload Bukti Pelunasan)**: Ringkasan pemesanan, kalkulasi otomatis biaya sewa dan biaya pengisian listrik mobil listrik, formulir upload bukti transfer pembayaran (JPG/PNG).
5. **Riwayat Sewa & Invoice**: Menampilkan daftar sewa garasi aktif, status garasi `used`, dan detail invoice lengkap beserta bukti transfer.

### B. Fitur Portal Administrator (Admin Portal)
1. **Verifikasi Pendaftar Baru**: Panel peninjauan KTP pendaftar secara visual (modal popup) dengan tombol **Terima** dan **Tolak** yang memperbarui status penyewa secara instan.
2. **Pembuatan Garasi Berkelompok (Batch Generator)**:
   - Membuat garasi tipe Sepeda (`S`), Mobil (`C`), dan Mobil Listrik (`E`) secara otomatis hingga batas maksimal 999 unit per tipe.
   - Penomoran kode garasi otomatis berurutan inkremental (contoh: `S001`, `C001`, `E001`).
3. **Perpanjangan Sewa Garasi**:
   - Menampilkan daftar garasi yang sedang aktif disewa hari ini.
   - Form perubahan tanggal selesai sewa baru.
   - Kalkulasi otomatis sisa hari tambahan dikalikan harga sewa harian (tanpa tambahan biaya listrik).
   - Wajib melampirkan bukti transfer perpanjangan sebelum menerbitkan invoice baru.
4. **Laporan Rekapitulasi Sewa Garasi (4 Filter Analisis)**:
   - **Per Bulan**: Grafik/tabel pendapatan bulanan dalam 1 tahun.
   - **Per Hari**: Rincian transaksi persewaan harian pada bulan tertentu.
   - **Per Tipe Garasi**: Rekapitulasi persewaan dan kontribusi omzet berdasarkan tipe garasi.
   - **Per Penyewa**: Rekapitulasi histori sewa per pengguna dengan fitur kolom pencarian nama/email dan tombol **Cari**.

---

## 5. Ringkasan Aturan Bisnis & Rumus Perhitungan

| Kode | Aturan Bisnis | Formula / Logika Implementasi |
| :---: | :--- | :--- |
| **R1** | Tipe Sewa | Harian atau Bulanan |
| **R2** | Tipe Garasi | Sepeda (`S`), Mobil (`C`), Mobil Listrik (`E`) |
| **R3** | Kesesuaian Garasi | Sepeda/Motor $\rightarrow$ Garasi Sepeda; Mobil $\rightarrow$ Garasi Mobil; Mobil Listrik $\rightarrow$ Garasi Mobil atau Garasi Mobil Listrik |
| **R4-R6**| Isi Listrik EV | Mobil listrik di garasi EV wajib cas penuh. $\text{kWh diisi} = \text{Kapasitas} - \text{Sisa Baterai}$. Biaya listrik $= \text{kWh diisi} \times \text{Tarif per kWh}$. |
| **R7** | Biaya Bulanan | $\text{Biaya Bulanan} = 25 \times \text{Harga Sewa Harian}$ |
| **R8-R9**| Durasi Sewa | Harian maks $= \text{Jumlah hari bulan itu} - 1$. Bulanan $= \text{Jumlah hari penuh bulan itu}$ (Februari otomatis presisi 28 atau 29 hari kabisat). |
| **R11-R12**| Perpanjangan Sewa | Menerbitkan invoice baru tipe `perpanjangan`. Biaya $= \text{Hari Tambahan} \times \text{Harga Harian}$ (tanpa biaya isi listrik ulang). |
| **R13** | Kapasitas Garasi | Maksimal 999 unit per tipe garasi dengan format kode 1 huruf prefiks + 3 digit nomor urut (`S001` - `S999`). |

---

## 6. Otomatisasi Pengujian (Unit Testing)

Unit testing resmi dirancang di file `garage/garage_backend/tests/Feature/LspScenarioTest.php` untuk memvalidasi kesesuaian sistem dengan indikator uji kompetensi LSP (UT-01 s.d. UT-11).

Untuk menjalankan seluruh rangkaian pengujian unit testing:
```bash
cd garage/garage_backend
vendor/bin/phpunit --filter=LspScenarioTest --testdox
```

### Hasil Eksekusi Uji Kompetensi (100% Passed)
```text
PHPUnit 12.5.45 by Sebastian Bergmann and contributors.

Runtime:       PHP 8.4.8
Configuration: D:\Kuliah\Sertifikat\LSP UC\garage\garage_backend\phpunit.xml

Lsp Scenario (Tests\Feature\LspScenario)
 ✔ UT 01 pendaftaran penyewa
 ✔ UT 02 verifikasi pendaftar baru
 ✔ UT 03 buat garasi berkelompok
 ✔ UT 04 validasi batas jumlah garasi
 ✔ UT 05 validasi kesesuaian kendaraan dan garasi
 ✔ UT 06 validasi durasi sewa harian
 ✔ UT 07 perhitungan biaya sewa bulanan
 ✔ UT 08 perhitungan isi listrik mobil listrik
 ✔ UT 09 simpan sewa dan status garasi
 ✔ UT 10 perpanjang status garasi
 ✔ UT 11 laporan sewa garasi

OK (11 tests, 58 assertions) - Waktu Eksekusi: ~4.2 detik
```
