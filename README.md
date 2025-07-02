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
    *   [X] Login & Logout (Controller `AuthController`, view `auth/login` dengan Bootstrap 5)
    *   [X] Manajemen Akun (CRUD oleh Admin via `Admin/UserController` dan views `admin/users/` dengan Bootstrap 5)
    *   [X] Hak Akses Peran (Dasar, via Filter `AuthFilter` untuk path `/admin` dan pengecekan peran di Controller)
    *   [X] Halaman `Unauthorized Access` (403)
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
        *   [X] Manajemen siswa per kelas (tabel `class_student`)
    *   **[X] Manajemen Penugasan Guru-Kelas-Mapel (Admin)**
        *   [X] Struktur Tabel Database (`teacher_class_subject_assignments`)
        *   [X] Model (`TeacherClassSubjectAssignmentModel`)
        *   [X] Controller (`Admin/TeacherClassSubjectAssignmentController`) dengan fungsi CRUD dasar (index, new, create, delete).
        *   [X] Views (daftar, tambah penugasan - dengan Bootstrap 5).
*   **[P] Modul Akademik Harian**
    *   [X] Manajemen Jadwal Pelajaran (Admin: CRUD; Guru & Siswa: View)
    *   [P] Input Presensi Harian oleh Guru (Guru: Input; Rekap Admin/Wali Kelas: Belum)
    *   [ ] Pemilihan Mata Pelajaran Pilihan (Siswa Fase F)
*   **[X] Modul Penilaian (Bank Nilai) (Tahap Awal)**
    *   [X] Struktur Tabel Database (`assessments`)
    *   [X] Model (`AssessmentModel`) dengan validasi dasar (termasuk rentang skor, tanggal valid).
    *   [X] Controller (`Guru/AssessmentController`) untuk pemilihan konteks (kelas/mapel) & form input nilai.
        *   [X] Metode `index` untuk pemilihan Kelas & Mapel.
        *   [X] Metode `showInputForm` untuk menampilkan form input nilai dengan daftar siswa.
        *   [X] Metode `saveAssessments` untuk memproses & menyimpan batch data penilaian.
    *   [X] Views (`guru/assessments/select_context.php`, `guru/assessments/input_form.php`):
        *   [X] Form input nilai dengan JavaScript untuk menambah/menghapus baris entri nilai per siswa secara dinamis.
    *   [X] Rute untuk fitur penilaian guru (`/guru/assessments/...`), diproteksi oleh peran Guru.
    *   [X] Logika penyimpanan batch nilai (formatif/sumatif) ke database.
    *   [X] Validasi input komprehensif (sisi controller & model) untuk tipe asesmen, judul, tanggal, skor (khusus sumatif), dan ketergantungan antar field.
    *   [X] Tampilan error validasi yang jelas di form input, menunjukkan nama siswa, entri keberapa, dan field yang bermasalah.
    *   [X] Telah dilakukan testing manual untuk berbagai skenario input (valid, invalid, berbagai tipe).
    *   [X] Tampilan rekap nilai per siswa dan per mata pelajaran (Guru: pemilihan konteks, tampilan tabel, aksi Edit/Hapus; Siswa: tampilan nilai per mapel; Ortu: pemilihan anak & tampilan nilai anak).
    *   [X] Filter kelas/mapel yang diajar guru pada halaman pemilihan konteks (Filter kelas berdasarkan wali kelas; Filter mapel berdasarkan penugasan guru di kelas tersebut).
    *   [X] Fitur edit/hapus data penilaian yang sudah dimasukkan (Controller, View dasar, Rute, Hak Akses dasar, terintegrasi di halaman rekap).
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
    *   [X] Mengelola akun dan hak akses semua pengguna (CRUD Users).
    *   [X] Mengelola Data Induk (Siswa, Guru, Mapel, Kelas - CRUD Penuh).
    *   [X] Mengelola Jadwal Pelajaran (CRUD).
    *   [ ] Melihat Rekap Presensi (Belum).
    *   [ ] Mengatur konfigurasi tahun ajaran, data sekolah, struktur kurikulum.
    *   [ ] Mengelola data master tema dan dimensi P5.
    *   [ ] Maintenance dan backup database.
*   **Staf Tata Usaha (TU)**:
    *   [X] Mengelola Data Induk (Siswa, Guru, Mapel, Kelas - CRUD Penuh).
    *   [X] Mengatur pembagian siswa ke dalam rombel.
    *   [X] Mengelola Jadwal Pelajaran (CRUD).
    *   [ ] Melihat Rekap Presensi (Belum).
*   **Kepala Sekolah**:
    *   [X] Akses read-only ke Data Induk (via Controller, halaman index).
    *   [ ] Melihat Rekap Presensi (Belum).
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
    *   [ ] Memantau rekapitulasi absensi dan nilai kelas perwalian (Rekap Presensi Belum).
    *   [ ] Menginput catatan perilaku/perkembangan siswa.
    *   [ ] Membimbing siswa memilih mapel pilihan.
    *   [ ] Validasi Kelengkapan Nilai.
    *   [ ] Ekspor Data ke e-Rapor.
*   **Siswa**:
    *   [X] Melihat Jadwal Pelajaran Kelas.
    *   [ ] Melihat rekap absensi pribadi.
    *   [X] Melihat transkrip nilai sementara.
    *   [ ] Melakukan pemilihan mapel pilihan.
*   **Orang Tua / Wali**:
    *   [ ] Memantau kehadiran dan rekap absensi anak.
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
