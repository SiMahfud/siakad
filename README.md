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

### Modul Inti & Administrasi

*   **[X] Modul Autentikasi & Manajemen Pengguna (Dasar)**
    *   [X] Struktur Tabel Database (`roles`, `users`) & Model (`UserModel`, `RoleModel`)
    *   [X] Login, Logout, Manajemen Akun (CRUD Admin), Hak Akses Dasar & Halaman 403
*   **[X] Modul Manajemen Data Induk (MVP)**
    *   **Data Siswa**: [X] CRUD Lengkap (DB, Model, Controller, View)
    *   **Data Guru**: [X] CRUD Lengkap (DB, Model, Controller, View)
    *   **Data Mata Pelajaran**: [X] CRUD Lengkap (DB, Model, Controller, View)
    *   **Data Kelas (Rombongan Belajar)**: [X] CRUD (DB, Model, Controller, View), termasuk pemilihan Wali Kelas & manajemen siswa per kelas.
    *   **Manajemen Penugasan Guru-Kelas-Mapel (Admin)**: [X] CRUD Dasar (DB, Model, Controller, View)

### Modul Akademik & Penilaian

*   **[X] Modul Akademik Harian (MVP)**
    *   [X] Manajemen Jadwal Pelajaran (Admin: CRUD; Guru & Siswa: View)
    *   [X] Input Presensi Harian oleh Guru
    *   [X] Rekapitulasi Presensi (View, Filter, Export untuk Admin/Wali Kelas/Kepala Sekolah)
    *   [X] Pemilihan Mata Pelajaran Pilihan (Siswa Fase F: Pilih; Admin: Setup)
    *   [X] Rekapitulasi Pilihan Mata Pelajaran (View, Filter, Export untuk Admin/Wali Kelas/Kepala Sekolah)
*   **[X] Modul Penilaian (Bank Nilai) (Fungsional Dasar)**
    *   [X] Struktur Data & Model (`assessments`) dengan validasi skor & tanggal.
    *   [X] Input Nilai oleh Guru (pemilihan konteks kelas/mapel, form input batch dinamis per siswa).
    *   [X] Penyimpanan & Validasi Batch Nilai (formatif/sumatif), termasuk error handling.
    *   [X] Rekapitulasi & Pengelolaan Nilai (Lihat, Edit, Hapus oleh Guru; Lihat oleh Siswa & Ortu).
    *   [X] Filter cerdas untuk Guru (kelas diajar/wali, mapel diajar di kelas).

### Modul Projek Penguatan Profil Pelajar Pancasila (P5)

*   **[X] Pengelolaan Data & Struktur P5 (Admin/Koordinator)**
    *   [X] Desain Database & Model (8 tabel: `p5_themes`, `p5_projects`, `p5_dimensions`, `p5_elements`, `p5_sub_elements`, `p5_project_target_sub_elements`, `p5_project_students`, `p5_assessments`).
    *   [X] CRUD untuk Master Data P5 (Tema, Dimensi, Elemen, Sub-elemen).
    *   [X] CRUD untuk Projek P5 (termasuk pemilihan tema, target sub-elemen).
    *   [X] Alokasi Siswa ke Projek P5.
    *   [ ] Penentuan fasilitator/guru pendamping projek.
*   **[ ] Fitur Penilaian P5 (Fasilitator/Guru)**
    *   [ ] Antarmuka input penilaian kualitatif (BB, MB, BSH, SB) & catatan deskriptif per siswa per sub-elemen.
*   **[ ] Fitur Pelaporan P5**
    *   [ ] Rekapitulasi penilaian P5 (per siswa, per projek).
    *   [ ] Ekspor data P5 untuk e-Rapor (jika didukung).

### Modul Pendukung

*   **[P] Modul Ekspor ke e-Rapor (Fitur Kunci - Tahap Awal)**
    *   [X] Antarmuka Wali Kelas untuk parameter ekspor (Kelas, Tahun Ajaran, Semester).
    *   [P] Proses penarikan data nilai sumatif (rata-rata per mapel).
        *   *Catatan: Akurasi filter semester pada nilai sumatif perlu penyempurnaan.*
    *   [X] Penyusunan & Unduh file Excel (.xlsx).
    *   [ ] Ekspor data P5 (menyusul setelah fitur penilaian & pelaporan P5 lengkap).

### Fitur Berdasarkan Peran Pengguna
*   **Administrator Sistem**:
    *   [X] Mengelola akun dan hak akses semua pengguna (CRUD Users).
    *   [X] Mengelola Data Induk (Siswa, Guru, Mapel, Kelas - CRUD Penuh).
    *   [X] Mengelola Jadwal Pelajaran (CRUD).
    *   [X] Mengelola Penawaran Mata Pelajaran Pilihan (CRUD).
    *   [X] Melihat Rekap Presensi.
    *   [X] Melihat Rekap Pemilihan Mapel.
    *   [ ] Mengatur konfigurasi tahun ajaran, data sekolah, struktur kurikulum.
    *   [ ] Mengelola data master tema dan dimensi P5.
    *   [ ] Maintenance dan backup database.
*   **Staf Tata Usaha (TU)**:
    *   [X] Mengelola Data Induk (Siswa, Guru, Mapel, Kelas - CRUD Penuh).
    *   [X] Mengatur pembagian siswa ke dalam rombel.
    *   [X] Mengelola Jadwal Pelajaran (CRUD).
    *   [X] Mengelola Penawaran Mata Pelajaran Pilihan (CRUD).
    *   [X] Melihat Rekap Presensi.
    *   [X] Melihat Rekap Pemilihan Mapel.
*   **Kepala Sekolah**:
    *   [X] Akses read-only ke Data Induk (via Controller, halaman index).
    *   [X] Melihat Rekap Presensi.
    *   [X] Melihat Rekap Pemilihan Mapel.
    *   [ ] Dasbor eksekutif.
    *   [ ] Memantau aktivitas guru.
    *   [ ] Membuat dan menyebarkan pengumuman.
*   **Guru Mata Pelajaran**:
    *   [X] Melihat Jadwal Mengajar.
    *   [X] Menginput Presensi Harian.
    *   [P] Menginput nilai asesmen (formatif, sumatif) - Alur dasar input dan penyimpanan batch sudah ada.
    *   [X] Melihat daftar kelas yang diajar dan siswa di dalamnya.
    *   [ ] Mengunggah materi ajar/tugas.
*   **Wali Kelas**:
    *   (Semua fitur Guru Mata Pelajaran)
    *   [X] Melihat daftar siswa di kelas perwalian.
    *   [X] Memantau rekapitulasi absensi kelas perwalian (via Rekap Presensi).
    *   [X] Melihat Rekap Pemilihan Mapel (untuk memantau pilihan siswa secara umum, jika relevan).
    *   [ ] Menginput catatan perilaku/perkembangan siswa.
    *   [ ] Validasi Kelengkapan Nilai.
    *   [P] Ekspor Data ke e-Rapor (Tahap Awal: nilai sumatif).
    *   [ ] Mengelola Projek P5 (jika ditunjuk sebagai Koordinator P5).
*   **Siswa**:
    *   [X] Melihat Jadwal Pelajaran Kelas.
    *   [X] Melakukan Pemilihan Mata Pelajaran Pilihan (Fase F).
    *   [ ] Melihat rekap absensi pribadi.
    *   [X] Melihat transkrip nilai sementara.
*   **Orang Tua / Wali**:
    *   [ ] Memantau kehadiran dan rekap absensi anak.
    *   [ ] Melihat status pemilihan mapel anak (Belum).
    *   [X] Melihat transkrip nilai sementara anak.
    *   [ ] Menerima pengumuman dan pesan.

## Setup Pengembangan Awal

1.  Clone repositori ini.
2.  Pastikan PHP (versi 8.1+ direkomendasikan, saat ini menggunakan 8.3.6) dan Composer terinstal.
3.  Jalankan `composer install` untuk menginstal dependensi.
4.  Database SQLite (`writable/database.sqlite`) akan dibuat dan dimigrasikan secara otomatis saat pertama kali menjalankan migrasi.
5.  Jalankan migrasi: `php spark migrate`
6.  Jalankan seeder untuk mengisi data awal: `php spark db:seed DatabaseSeeder`
    *   Perintah ini akan membuat peran default, user default untuk setiap peran, data guru dan siswa terkait, serta beberapa data master untuk mata pelajaran, kelas, dan penugasan mengajar.
    *   Beberapa user default yang bisa digunakan untuk login (password untuk semua: `password123`):
        *   Administrator Sistem: `admin`
        *   Staf Tata Usaha: `staf`
        *   Kepala Sekolah: `kepsek`
        *   Guru 1: `guru1`
        *   Guru 2: `guru2`
        *   Siswa 1: `siswa1`
        *   Orang Tua 1: `ortu1`
7.  Jalankan server pengembangan: `php spark serve`
8.  Akses aplikasi melalui `http://localhost:8080`. Fitur admin data induk tersedia di bawah path `/admin/...` (misal, `/admin/students`).

---
*Readme ini bersifat sementara dan akan diperbarui seiring progres pengembangan.*
