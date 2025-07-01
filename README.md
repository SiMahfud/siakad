# SI-AKADEMIK - Sistem Informasi Akademik Harian SMAN 1 Campurdarat

Versi: 1.0 (Pengembangan Awal)
Tanggal Proyek Dimulai: 30 Juni 2025 (sesuai dokumen desain)

## Deskripsi Aplikasi

SI-AKADEMIK adalah aplikasi Sistem Informasi Akademik Harian yang dirancang khusus untuk SMAN 1 Campurdarat. Aplikasi ini bertujuan untuk mendukung implementasi Kurikulum Merdeka dengan fokus utama pada pencatatan aktivitas pembelajaran harian dan pengelolaan penilaian (formatif dan sumatif) oleh guru secara efisien.

Sistem ini tidak dirancang untuk menghasilkan rapor final secara langsung. Sebaliknya, SI-AKADEMIK akan berfungsi sebagai "Bank Nilai" yang terstruktur dan terpusat. Fitur kunci dari sistem ini adalah kemampuannya untuk mengekspor data nilai dalam format Excel (.xlsx) yang kompatibel dan siap diimpor ke dalam aplikasi e-Rapor Kemdikbud yang sudah digunakan oleh sekolah.

### Tujuan Utama Pengembangan:
*   **Sentralisasi Data Penilaian**: Menciptakan satu sumber data tunggal untuk semua jenis penilaian.
*   **Efisiensi Kerja Guru**: Memudahkan guru dalam mengelola dan mendokumentasikan nilai.
*   **Transparansi Proses Pembelajaran**: Memberikan akses bagi siswa dan orang tua untuk memantau perkembangan belajar.
*   **Integrasi dengan e-Rapor**: Menyederhanakan proses pengisian rapor akhir semester.
*   **Mendukung Kurikulum Merdeka**: Memfasilitasi administrasi mata pelajaran pilihan dan penilaian Projek Penguatan Profil Pelajar Pancasila (P5).

### Spesifikasi Teknologi (Saat Ini):
*   **Backend Framework**: CodeIgniter 4
*   **Bahasa Pemrograman**: PHP 8.3.6 (Desain awal: PHP 7.4+)
*   **Frontend**: HTML, CSS, JavaScript (Telah diintegrasikan dengan Bootstrap 5 via CDN)
*   **Database**: SQLite (untuk pengembangan awal, sesuai desain bisa MySQL)

## Status Implementasi Fitur

Berikut adalah status implementasi fitur berdasarkan dokumen desain:

### Modul Utama

*   **[X] Modul Autentikasi & Manajemen Pengguna (Dasar)**
    *   [X] Struktur Tabel Database (`roles`, `users`)
    *   [X] Model (`UserModel`, `RoleModel`) dengan validasi dan hashing password
    *   [X] Seeder untuk `roles`
    *   [X] Login & Logout (Controller `AuthController`, view `auth/login`)
    *   [X] Manajemen Akun (CRUD oleh Admin via `Admin/UserController` dan views `admin/users/`)
    *   [X] Hak Akses Peran (Dasar, via Filter `AuthFilter` untuk path `/admin`)
*   **[X] Modul Manajemen Data Induk (MVP)**
    *   **Data Siswa**
        *   [X] Struktur Tabel Database (`students`)
        *   [X] Model (`StudentModel`) dengan validasi dasar
        *   [X] Controller (`Admin/StudentController`) dengan fungsi CRUD
        *   [X] Views (daftar, tambah, edit siswa - dengan Bootstrap 5)
    *   **Data Guru**
        *   [X] Struktur Tabel Database (`teachers`)
        *   [X] Model (`TeacherModel`) dengan validasi dasar
        *   [X] Controller (`Admin/TeacherController`) dengan fungsi CRUD
        *   [X] Views (daftar, tambah, edit guru - dengan Bootstrap 5)
    *   **Data Mata Pelajaran**
        *   [X] Struktur Tabel Database (`subjects`)
        *   [X] Model (`SubjectModel`) dengan validasi dasar
        *   [X] Controller (`Admin/SubjectController`) dengan fungsi CRUD
        *   [X] Views (daftar, tambah, edit mata pelajaran - dengan Bootstrap 5)
    *   **Data Kelas (Rombongan Belajar)**
        *   [X] Struktur Tabel Database (`classes`, `class_student`)
        *   [X] Model (`ClassModel`) dengan validasi dasar & relasi dasar ke guru
        *   [X] Controller (`Admin/ClassController`) dengan fungsi CRUD (termasuk pemilihan Wali Kelas)
        *   [X] Views (daftar, tambah, edit kelas - dengan Bootstrap 5)
        *   [ ] Manajemen siswa per kelas (tabel `class_student`)
*   **[ ] Modul Akademik Harian**
    *   [ ] Manajemen Jadwal Pelajaran
    *   [ ] Input Presensi Harian oleh Guru
    *   [ ] Pemilihan Mata Pelajaran Pilihan (Siswa Fase F)
*   **[ ] Modul Penilaian (Bank Nilai)**
    *   [X] Struktur Tabel Database (`assessments`)
    *   [ ] Antarmuka input nilai formatif dan sumatif oleh guru
    *   [ ] Tampilan rekap nilai per siswa dan per mata pelajaran
*   **[ ] Modul Projek P5**
    *   [ ] Struktur Tabel Database (perlu dirancang lebih detail berdasarkan dokumen)
    *   [ ] Pengaturan projek oleh koordinator
    *   [ ] Pencatatan penilaian kualitatif (BB, MB, BSH, SB) oleh fasilitator
*   **[ ] Modul Ekspor ke e-Rapor (Fitur Kunci)**
    *   [ ] Antarmuka Wali Kelas untuk memilih parameter ekspor
    *   [ ] Proses penarikan data nilai sumatif dan P5
    *   [ ] Penyusunan data ke format Excel template e-Rapor
    *   [ ] Tombol unduh file Excel

### Fitur Berdasarkan Peran Pengguna

*   **Administrator Sistem**:
    *   [ ] Mengelola akun dan hak akses semua pengguna.
    *   [ ] Mengatur konfigurasi tahun ajaran, data sekolah, struktur kurikulum.
    *   [ ] Mengelola data master tema dan dimensi P5.
    *   [ ] Maintenance dan backup database.
*   **Staf Tata Usaha (TU)**:
    *   [X] Mengelola data induk siswa (sebagian via Modul Data Induk)
    *   [X] Mengelola data induk guru (sebagian via Modul Data Induk)
    *   [X] Mengatur pembagian siswa ke dalam rombel (dasar via Modul Data Induk - Kelas, detail penempatan siswa belum)
*   **Kepala Sekolah**:
    *   [ ] Akses read-only ke seluruh data.
    *   [ ] Dasbor eksekutif.
    *   [ ] Memantau aktivitas guru.
    *   [ ] Membuat dan menyebarkan pengumuman.
*   **Guru Mata Pelajaran**:
    *   [ ] Menginput absensi harian.
    *   [ ] Menginput nilai asesmen (formatif, sumatif).
    *   [ ] Mengunggah materi ajar/tugas.
*   **Wali Kelas**:
    *   (Semua fitur Guru Mata Pelajaran)
    *   [ ] Memantau rekapitulasi absensi dan nilai kelas perwalian.
    *   [ ] Menginput catatan perilaku/perkembangan siswa.
    *   [ ] Membimbing siswa memilih mapel pilihan.
    *   [ ] Validasi Kelengkapan Nilai.
    *   [ ] Ekspor Data ke e-Rapor.
*   **Siswa**:
    *   [ ] Melihat jadwal pelajaran.
    *   [ ] Melihat rekap absensi pribadi.
    *   [ ] Melihat transkrip nilai sementara.
    *   [ ] Melakukan pemilihan mapel pilihan.
*   **Orang Tua / Wali**:
    *   [ ] Memantau kehadiran dan rekap absensi anak.
    *   [ ] Melihat transkrip nilai sementara anak.
    *   [ ] Menerima pengumuman dan pesan.

## Setup Pengembangan Awal

1.  Clone repositori ini.
2.  Pastikan PHP (versi 8.1+ direkomendasikan, saat ini menggunakan 8.3.6) dan Composer terinstal.
3.  Jalankan `composer install` untuk menginstal dependensi.
4.  Database SQLite (`writable/database.sqlite`) akan dibuat dan dimigrasikan secara otomatis saat pertama kali menjalankan migrasi.
5.  Jalankan migrasi: `php spark migrate`
6.  Jalankan seeder (untuk data awal seperti peran): `php spark db:seed DatabaseSeeder`
7.  Jalankan server pengembangan: `php spark serve`
8.  Akses aplikasi melalui `http://localhost:8080`. Fitur admin data induk tersedia di bawah path `/admin/...` (misal, `/admin/students`).

---
*Readme ini bersifat sementara dan akan diperbarui seiring progres pengembangan.*
