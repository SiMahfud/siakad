# SI-AKADEMIK - Sistem Informasi Akademik SMAN 1 Campurdarat

Selamat datang di SI-AKADEMIK, sebuah Sistem Informasi Akademik Harian yang dirancang khusus untuk mendukung proses pembelajaran di SMAN 1 Campurdarat sesuai dengan Kurikulum Merdeka.

Aplikasi ini berfungsi sebagai **"Bank Nilai"** yang terpusat dan terstruktur, memudahkan guru dalam mencatat penilaian formatif dan sumatif. Tujuan utamanya adalah menyederhanakan administrasi akademik dan menyediakan data yang akurat dan siap pakai untuk aplikasi **e-Rapor Kemdikbud**, bukan untuk menggantikannya.

---

## ✨ Fitur Utama

SI-AKADEMIK menyediakan fungsionalitas yang disesuaikan untuk setiap peran di lingkungan sekolah.

### Untuk Administrasi Sekolah (Admin, Staf TU, Kepala Sekolah)
- **Manajemen Data Induk**: CRUD (Create, Read, Update, Delete) penuh untuk data Siswa, Guru, Mata Pelajaran, dan Kelas (Rombel).
- **Manajemen Akademik**: Mengelola jadwal pelajaran, penugasan guru ke kelas, dan penawaran mata pelajaran pilihan.
- **Manajemen Pengguna**: Mengelola akun dan hak akses untuk semua pengguna sistem.
- **Monitoring & Pelaporan**: Melihat rekapitulasi presensi dan pilihan mata pelajaran, serta mengakses dasbor eksekutif dengan ringkasan data sekolah (khusus Kepala Sekolah).
- **Manajemen P5**: Mengelola seluruh aspek Projek Penguatan Profil Pelajar Pancasila, mulai dari tema, projek, hingga alokasi siswa dan fasilitator.
- **Konfigurasi Sistem**: Mengatur parameter global sekolah seperti tahun ajaran dan semester aktif.

### Untuk Guru (Guru & Wali Kelas)
- **Input Penilaian**: Mencatat nilai formatif dan sumatif dengan antarmuka yang dinamis dan efisien.
- **Input Presensi**: Mencatat kehadiran siswa per jam pelajaran dengan mudah.
- **Akses Terfilter**: Melihat jadwal mengajar, daftar kelas, dan siswa yang relevan secara otomatis.
- **Rekapitulasi**: Memantau rekap nilai dan presensi untuk kelas yang diampu.
- **Ekspor e-Rapor (Wali Kelas)**: Mengekspor data nilai sumatif semesteran ke format Excel (.xlsx) yang kompatibel dengan aplikasi e-Rapor.
- **Penilaian P5**: Menginput penilaian kualitatif (BB, MB, BSH, SB) untuk siswa dalam projek P5 yang difasilitasi.

### Untuk Siswa
- **Transparansi Akademik**: Melihat jadwal pelajaran, rekap presensi pribadi, dan transkrip nilai sementara.
- **Pemilihan Mata Pelajaran**: Siswa Fase F dapat melakukan pemilihan mata pelajaran pilihan secara mandiri.
- **Notifikasi**: Menerima notifikasi penting terkait aktivitas akademik.

### Untuk Orang Tua
- **Monitoring Anak**: Memantau rekapitulasi kehadiran dan transkrip nilai anak-anak mereka.
- **Notifikasi**: Menerima notifikasi otomatis jika terdeteksi tingkat ketidakhadiran anak yang tinggi.

---

## 🚀 Tumpukan Teknologi

*   **Framework**: CodeIgniter 4.6.1
*   **Bahasa**: PHP 8.3.6
*   **Database**: SQLite (pengembangan) & MySQL (target produksi)
*   **Frontend**: Bootstrap 5, jQuery, DataTables.net, Chart.js, FullCalendar.js (via CDN)
*   **Manajemen Dependensi**: Composer

---

## 🏁 Memulai

Panduan ini untuk pengembang atau pengguna teknis yang ingin menjalankan aplikasi ini secara lokal.

### Prasyarat
- PHP 8.1+
- Composer 2.x
- Akses ke terminal/command line

### Instalasi & Pengaturan
1.  **Clone Repositori**:
    ```bash
    git clone https://github.com/username/si-akademik.git
    cd si-akademik
    ```
2.  **Instal Dependensi**:
    ```bash
    composer install
    ```
3.  **Salin File Environment**:
    Salin file `env` menjadi `.env` dan sesuaikan jika perlu (misalnya, `CI_ENVIRONMENT` ke `development`).
    ```bash
    cp env .env
    ```
4.  **Jalankan Migrasi Database**:
    Perintah ini akan membuat struktur tabel database. Untuk SQLite, file database akan otomatis dibuat di `writable/database.sqlite`.
    ```bash
    php spark migrate
    ```
5.  **Jalankan Database Seeder**:
    Perintah ini akan mengisi database dengan data awal yang penting, termasuk peran dan akun pengguna default.
    ```bash
    php spark db:seed DatabaseSeeder
    ```
6.  **Jalankan Server Lokal**:
    ```bash
    php spark serve
    ```
    Aplikasi sekarang dapat diakses di **http://localhost:8080**.

### Akun Login Default
Setelah menjalankan seeder, Anda dapat login menggunakan akun default berikut.
**Password** untuk semua akun adalah: `password123`

| Peran                  | Username  |
| ---------------------- | --------- |
| Administrator Sistem   | `admin`   |
| Staf Tata Usaha        | `staf`    |
| Kepala Sekolah         | `kepsek`  |
| Guru 1                 | `guru1`   |
| Guru 2                 | `guru2`   |
| Siswa 1                | `siswa1`  |
| Orang Tua 1            | `ortu1`   |

---

## 📖 Panduan Penggunaan

Berikut adalah alur kerja dasar untuk beberapa tugas kunci di dalam aplikasi.

#### Sebagai Guru: Menginput Nilai
1.  Login sebagai `guru1`.
2.  Navigasi ke menu **Penilaian -> Input Nilai**.
3.  Pilih **Kelas** dan **Mata Pelajaran** yang Anda ajar. Daftar mata pelajaran akan muncul secara dinamis setelah kelas dipilih.
4.  Klik **"Lanjutkan"**.
5.  Form input akan muncul, menampilkan daftar siswa di kelas tersebut. Anda dapat menambahkan beberapa baris penilaian untuk setiap siswa.
6.  Isi detail penilaian (jenis, judul, tanggal, skor/deskripsi).
7.  Klik **"Simpan Penilaian"** di bagian bawah halaman.

#### Sebagai Wali Kelas: Mengekspor Data e-Rapor
1.  Login sebagai guru yang ditunjuk sebagai Wali Kelas (misal, `guru1` jika diset di database).
2.  Navigasi ke menu **Wali Kelas -> Ekspor e-Rapor**.
3.  Pilih **Tahun Ajaran** dan **Semester**.
4.  Klik **"Proses & Unduh Excel"**.
5.  Sistem akan menghasilkan file `.xlsx` yang berisi rata-rata nilai sumatif untuk setiap siswa di kelas perwalian Anda, siap untuk diimpor ke aplikasi e-Rapor.

---

## 👨‍💻 Untuk Pengembang

Informasi teknis tambahan untuk berkontribusi pada proyek ini.

### Struktur Proyek
- **Controllers**: `app/Controllers/` - Logika utama aplikasi, diorganisir dalam sub-direktori berdasarkan peran (misal, `Admin`, `Guru`).
- **Models**: `app/Models/` - Interaksi database dan aturan validasi data.
- **Views**: `app/Views/` - File tampilan (UI), diorganisir dalam sub-direktori berdasarkan peran.
- **Routes**: `app/Config/Routes.php` - Mendefinisikan URL dan menghubungkannya ke controller.
- **Database Migrations**: `app/Database/Migrations/` - Skema database dalam bentuk kode.
- **Database Seeders**: `app/Database/Seeds/` - Data awal untuk pengembangan.
- **Helpers**: `app/Helpers/` - Fungsi-fungsi bantuan kustom (misal, `auth_helper.php`).

### Kontrol Akses
Aplikasi menggunakan kombinasi **Filter Rute** dan **Pengecekan di dalam Controller** untuk mengelola hak akses.
- **Filter Rute**: Di `app/Config/Routes.php`, grup rute dilindungi dengan filter `auth` yang dapat menerima argumen peran (misal, `'filter' => 'auth:Administrator Sistem,Staf Tata Usaha'`).
- **Helper `hasRole()`**: Di dalam controller, fungsi `hasRole(['Nama Peran'])` digunakan untuk memberikan pengamanan lapis kedua pada aksi-aksi spesifik (misal, CUD).

### Testing
Proyek ini menggunakan PHPUnit untuk testing.
- **Menjalankan Semua Test**:
  ```bash
  composer test
  ```
- **Menjalankan File Test Spesifik**:
  ```bash
  php vendor/bin/phpunit tests/Controllers/Admin/StudentControllerTest.php
  ```
- File konfigurasi test ada di `phpunit.xml.dist`. Test database menggunakan database SQLite in-memory untuk isolasi dan kecepatan.

---

## 🤝 Berkontribusi

Kontribusi sangat kami hargai! Silakan fork repositori ini, buat branch baru untuk fitur atau perbaikan Anda, dan ajukan Pull Request.

## 📜 Lisensi

Proyek ini dilisensikan di bawah [MIT License](LICENSE).
