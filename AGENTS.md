# AGENTS.md - Catatan untuk Pengembang SI-AKADEMIK

Dokumen ini berisi catatan, konvensi, dan panduan untuk agen (termasuk AI atau pengembang manusia) yang bekerja pada proyek SI-AKADEMIK SMAN 1 Campurdarat.

## 1. Ringkasan Teknologi

*   **Framework**: CodeIgniter 4 (saat ini versi 4.6.1)
*   **Bahasa**: PHP (saat ini menggunakan versi 8.3.6)
*   **Database**: SQLite (lokasi: `writable/database.sqlite`) untuk pengembangan awal. Desain akhir menargetkan MySQL.
*   **Manajemen Dependensi**: Composer
*   **Frontend**:
    *   Bootstrap 5 (via CDN) sebagai dasar UI.
    *   jQuery (via CDN) untuk beberapa fungsionalitas JavaScript.
    *   DataTables.net (via CDN) untuk tabel interaktif (sorting, filter, pagination).
        *   Ekstensi Buttons DataTables.net (via CDN) untuk fungsionalitas export data.
        *   Dependensi untuk Buttons: JSZip (untuk Excel), pdfmake (untuk PDF).
    *   Menggunakan master layout `app/Views/layouts/admin_default.php`.

## 2. Setup Lingkungan Pengembangan Lokal

1.  **Clone Repositori**:
    ```bash
    git clone <url_repositori>
    cd <nama_direktori_proyek>
    ```
2.  **Instal Dependensi PHP**:
    Pastikan Composer terinstal.
    ```bash
    composer install
    ```
3.  **Konfigurasi Database**:
    *   Saat ini, aplikasi dikonfigurasi untuk menggunakan SQLite. File database akan secara otomatis dibuat di `writable/database.sqlite`.
    *   Konfigurasi database utama ada di `app/Config/Database.php`.
4.  **Jalankan Migrasi Database**:
    Untuk membuat struktur tabel:
    ```bash
    php spark migrate
    ```
    Jika ada _reset_ atau perubahan besar pada migrasi, Anda mungkin perlu menjalankan `php spark migrate:refresh` (hati-hati, ini akan menghapus semua data).
5.  **Jalankan Database Seeder**:
    Untuk mengisi data awal (seperti peran pengguna):
    ```bash
    php spark db:seed DatabaseSeeder
    ```
    Seeder individual juga bisa dijalankan, misal `php spark db:seed RoleSeeder`.
6.  **Jalankan Server Pengembangan Lokal**:
    ```bash
    php spark serve
    ```
    Aplikasi akan tersedia di `http://localhost:8080` secara default.

## 3. Struktur Proyek & Konvensi Penting

*   **Modul Data Induk**:
    *   Models: `app/Models/` (misal, `StudentModel.php`)
    *   Views: `app/Views/admin/<module_name>/` (misal, `app/Views/admin/students/index.php`)
    *   Controllers: `app/Controllers/Admin/` (misal, `StudentController.php`)
    *   Rute: Didefinisikan dalam `app/Config/Routes.php` menggunakan grup `admin`.
*   **Namespace**: Gunakan namespace `App\Controllers\Admin` untuk controller admin, `App\Models` untuk model, dst.
*   **Validasi**: Sebisa mungkin, letakkan aturan validasi utama di dalam Model terkait. Controller dapat mengambil aturan ini atau menambahinya jika perlu.
*   **Layout Views**: Master layout admin adalah `app/Views/layouts/admin_default.php`. Views konten harus `extend` layout ini dan menempatkan konten dalam `section('content')`.
*   **Helper**: Helper `form` dan `url` umumnya dibutuhkan di controller yang menangani form dan view.

## 4. Ringkasan Relasi Database Utama

Berikut adalah ringkasan relasi kunci (foreign key) antar tabel utama dalam database SI-AKADEMIK. Untuk detail lengkap kolom dan tipe data, silakan merujuk ke file migrasi di `app/Database/Migrations/`.

*   `users.role_id` -> `roles.id` (Menentukan peran pengguna)
*   `students.user_id` -> `users.id` (Akun login untuk siswa)
*   `students.parent_user_id` -> `users.id` (Akun login untuk orang tua siswa)
*   `teachers.user_id` -> `users.id` (Akun login untuk guru)
*   `classes.wali_kelas_id` -> `teachers.id` (Menentukan wali kelas untuk sebuah rombongan belajar)
*   `class_student.class_id` -> `classes.id` (Keterkaitan siswa dengan rombongan belajar)
*   `class_student.student_id` -> `students.id` (Keterkaitan rombongan belajar dengan siswa)
*   `assessments.student_id` -> `students.id` (Siswa yang dinilai)
*   `assessments.subject_id` -> `subjects.id` (Mata pelajaran yang dinilai)
*   `assessments.class_id` -> `classes.id` (Kelas tempat penilaian dilakukan)
*   `assessments.teacher_id` -> `teachers.id` (Guru yang melakukan penilaian/input nilai)

*(Catatan: ON DELETE/ON UPDATE behavior seperti CASCADE atau SET NULL juga didefinisikan dalam migrasi).*

## 5. Status Implementasi Saat Ini (untuk Pengembang)

*   **[X] Fondasi Proyek**: PHP, Composer, CodeIgniter 4 setup.
*   **[X] Database**: Skema database (semua tabel dari dokumen desain dan `teacher_class_subject_assignments`) telah dimigrasikan. SQLite digunakan.
*   **[X] Seeding**: Seeder komprehensif telah dibuat dan dijalankan via `DatabaseSeeder`:
    *   `RoleSeeder`: Mengisi tabel `roles`.
    *   `UserSeeder`: Membuat user default untuk setiap peran (admin, staf, kepsek, guru1, guru2, siswa1, ortu1).
    *   `TeacherSeeder`: Membuat data guru untuk user `guru1` dan `guru2`.
    *   `StudentSeeder`: Membuat data siswa untuk user `siswa1` dan mengaitkan dengan `ortu1`.
    *   `SubjectSeeder`: Mengisi beberapa mata pelajaran default.
    *   `ClassSeeder`: Mengisi beberapa kelas default, termasuk penetapan wali kelas.
    *   `ClassStudentSeeder`: Memasukkan siswa default ke kelas default.
    *   `TeacherClassSubjectAssignmentSeeder`: Membuat beberapa penugasan mengajar default.
*   **[X] Modul Data Induk (MVP)**:
    *   CRUD dasar (Create, Read, Update, Delete) untuk Siswa, Guru, Mata Pelajaran, dan Kelas (Rombel) telah diimplementasikan.
    *   Ini termasuk model, controller namespaced admin, views dasar, dan routing.
    *   Validasi dasar sisi server diimplementasikan dalam model.
    *   Form untuk Kelas (Rombel) menyertakan pemilihan Wali Kelas dari data Guru.
    *   Navigasi dasar antar modul data induk telah dibuat.
*   **[X] Modul Autentikasi & Manajemen Pengguna (Dasar)**:
    *   `UserModel` dan `RoleModel` dibuat/diperbarui.
    *   `AuthController` untuk proses login/logout.
    *   View login dasar (`auth/login.php`).
    *   `Admin/UserController` untuk CRUD pengguna.
    *   Views dasar untuk manajemen pengguna (`admin/users/`).
    *   Password di-hash secara otomatis saat disimpan.
    *   Rute untuk autentikasi dan manajemen pengguna telah ditambahkan.
    *   Filter `AuthFilter` dibuat dan diterapkan pada rute `/admin` untuk proteksi dasar.
*   **[X] UI Refactor (Bootstrap 5)**:
    *   Master layout baru `app/Views/layouts/admin_default.php` dibuat menggunakan Bootstrap 5 (via CDN).
    *   Halaman login dan semua views CRUD untuk Modul Data Induk (Users, Students, Teachers, Subjects, Classes) telah direfactor untuk menggunakan layout baru dan styling Bootstrap 5.
    *   Navigasi utama menggunakan komponen Navbar Bootstrap dan bersifat dinamis (menampilkan link berdasarkan status login & peran).
*   **[X] Penyempurnaan Hak Akses (Dasar)**:
    *   Helper `auth_helper.php` dibuat untuk pengecekan peran (`hasRole()`, `isAdmin()`, dll.).
    *   `AuthFilter` dimodifikasi untuk menerima argumen peran dan membatasi akses rute.
    *   Rute admin diperbarui dengan filter peran spesifik (misal, User Management hanya untuk Admin, Data Induk untuk Admin & Staf TU, dengan KS bisa lihat).
    *   Controller Data Induk (`Students`, `Teachers`, `Subjects`, `Classes`) ditambahkan pengecekan peran untuk aksi CUD.
    *   Halaman `unauthorized` dibuat.
    *   Navigasi di layout utama disesuaikan dengan hak akses peran.
*   **[X] Modul Penilaian (Bank Nilai) (Tahap Awal Selesai)**:
    *   `AssessmentModel.php` dibuat dengan aturan validasi untuk field-field penilaian (termasuk `required`, `valid_date`, `decimal`, `between[0,100]` untuk skor).
    *   `Guru/AssessmentController.php` diimplementasikan:
        *   `index()`: Menampilkan form untuk guru memilih Kelas dan Mata Pelajaran.
        *   `showInputForm()`: Menampilkan form input nilai detail, mengambil daftar siswa dari kelas yang dipilih.
        *   `saveAssessments()`: Memproses data batch dari form, melakukan validasi kustom per entri (misalnya, skor wajib untuk Sumatif, judul wajib jika ada skor/deskripsi), memvalidasi dengan `AssessmentModel`, dan menyimpan data valid menggunakan `insertBatch()`.
    *   Views terkait di `app/Views/guru/assessments/`:
        *   `select_context.php`: Form pemilihan kelas dan subjek.
            *   `input_form.php`: Form input nilai utama. Menggunakan JavaScript untuk memungkinkan guru menambah/menghapus beberapa baris entri penilaian per siswa secara dinamis. Kini juga menyertakan pagination sisi klien (JavaScript) untuk menangani daftar siswa yang panjang.
    *   Validasi input yang komprehensif telah diimplementasikan, baik di sisi controller (untuk logika yang lebih kompleks antar field) maupun di model (untuk aturan per field).
    *   Tampilan pesan error validasi telah disempurnakan di `input_form.php` untuk menampilkan pesan yang jelas, termasuk nama siswa, nomor entri (jika ada beberapa untuk satu siswa), dan nama field yang bermasalah, serta pesan error spesifik.
    *   Rute untuk modul penilaian guru (`/guru/assessments/...`) telah dibuat dan diproteksi menggunakan filter `auth` untuk peran 'Guru' dan 'Administrator Sistem'.
    *   Modul ini telah diuji secara manual dengan berbagai skenario input (valid, berbagai tipe asesmen, data invalid untuk menguji aturan validasi dan tampilan error).
    *   **Penyempurnaan Tambahan (Modul Penilaian):**
        *   **[X] Filter Kelas/Mapel di Halaman Pemilihan Konteks (`AssessmentController::index`, `AssessmentController::showRecapSelection`):**
            *   Filter kelas berdasarkan status wali kelas atau penugasan guru di kelas.
            *   Filter mata pelajaran berdasarkan penugasan guru di kelas yang dipilih (menggunakan tabel `teacher_class_subject_assignments`), diimplementasikan dengan AJAX untuk pembaruan dinamis tanpa reload halaman.
            *   View `select_context.php` dan `select_recap_context.php` dimodifikasi untuk mendukung mekanisme filter AJAX ini.
            *   Controller `AssessmentController` memiliki method `ajaxGetSubjectsForClass()` untuk menangani request AJAX.
        *   **[X] Fitur Edit dan Hapus Data Penilaian:**
            *   Method `editAssessment`, `updateAssessment`, dan `deleteAssessment` telah ditambahkan di `AssessmentController`.
            *   View `edit_form.php` untuk form edit penilaian.
            *   Rute terkait telah dibuat (`guru_assessment_edit`, `guru_assessment_update`, `guru_assessment_delete`).
            *   Hak akses dasar (pembuat asesmen atau admin) diimplementasikan untuk operasi edit/hapus.
        *   **[X] Fitur Rekapitulasi Nilai (Guru, Siswa, Orang Tua):**
            *   **Guru**: Method `showRecapSelection` dan `displayRecap` di `AssessmentController`. Views `select_recap_context.php` dan `recap_display.php`. Tombol Edit/Hapus terintegrasi. Tabel rekap menggunakan DataTables.net untuk sorting, filter global & per kolom, pagination, dan export data (Copy, CSV, Excel, PDF, Print). Link navigasi "Rekap Nilai".
            *   **Siswa**: `Siswa/NilaiController::index()` dan view `siswa/nilai/index.php` untuk menampilkan nilai siswa yang login. Tabel rekap menggunakan DataTables.net (termasuk export dan filter per kolom). Link navigasi "Transkrip Nilai".
            *   **Orang Tua**: `Ortu/NilaiController::index()` (pemilihan anak) & `showStudentRecap()`. Views `ortu/nilai/select_student.php` & `ortu/nilai/recap_display.php`. Tabel rekap menggunakan DataTables.net (termasuk export dan filter per kolom). Link navigasi "Nilai Anak".
            *   Model `AssessmentModel` memiliki `getAssessmentsForRecap()`. `StudentModel` memiliki `findByParentUserId()`. `TeacherClassSubjectAssignmentModel` memiliki `getDistinctSubjectsForClass()`.
*   **[X] Manajemen Penugasan Guru-Kelas-Mapel (Admin)**
    *   Tabel `teacher_class_subject_assignments` dibuat (via Migrasi).
    *   Model `TeacherClassSubjectAssignmentModel` dibuat, termasuk method helper `getAssignmentsDetails()` dan `getSubjectsForTeacherInClass()`.
    *   Controller `Admin/TeacherClassSubjectAssignmentController` dibuat dengan fungsi CRUD dasar (index, new, create, delete) untuk mengelola penugasan.
    *   Views `admin/assignments/index.php` dan `admin/assignments/new.php` dibuat.
    *   Rute resource `admin/assignments` ditambahkan dan diproteksi untuk Administrator Sistem.

## 6. Area Pengembangan Selanjutnya (Prioritas dari Dokumen Desain)

1.  **Modul Penilaian (Bank Nilai) (Lanjutan)**:
    *   (Item terkait optimasi form input dan penyempurnaan DataTables telah dianggap tuntas untuk lingkup saat ini. Pengembangan lebih lanjut pada area ini akan bersifat opsional atau berdasarkan kebutuhan baru).
2.  **Penyempurnaan Hak Akses (Lanjutan)**:
    *   Implementasi hak akses yang lebih granular (misal, guru hanya bisa mengelola data yang terkait langsung dengan dirinya/mapelnya/kelas walinya, siswa hanya lihat data sendiri).
    *   Pengecekan kepemilikan data secara lebih komprehensif.
3.  **[X] Manajemen Siswa dalam Kelas**:
    *   Fungsionalitas untuk menambah/mengeluarkan siswa dari sebuah kelas (mengelola tabel `class_student`) - **SELESAI**.
4.  **Modul Projek P5**:
    *   Desain detail tabel jika diperlukan.
    *   Implementasi fitur terkait P5.
5.  **Modul Ekspor ke e-Rapor**:
    *   Ini adalah fitur kunci dan kompleks yang memerlukan koordinasi terkait format template Excel.

## 7. Perintah Berguna CodeIgniter Spark

*   `php spark make:controller Admin/NamaController --suffix`
*   `php spark make:model NamaModel --suffix`
*   `php spark make:migration NamaMigration`
*   `php spark make:seeder NamaSeeder --suffix`
*   `php spark migrate`
*   `php spark migrate:rollback`
*   `php spark db:seed NamaSeeder`
*   `php spark routes` (untuk melihat daftar rute yang aktif)

---
*Dokumen ini akan diperbarui seiring dengan perkembangan proyek.*
