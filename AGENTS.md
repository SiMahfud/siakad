# AGENTS.md - Catatan untuk Pengembang SI-AKADEMIK

*Terakhir Diperbarui: 2025-07-03*

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

*   **Relasi Modul P5:**
    *   `p5_projects.p5_theme_id` -> `p5_themes.id` (Projek terkait dengan Tema P5)
    *   `p5_elements.p5_dimension_id` -> `p5_dimensions.id` (Elemen P5 terkait dengan Dimensi P5)
    *   `p5_sub_elements.p5_element_id` -> `p5_elements.id` (Sub-elemen P5 terkait dengan Elemen P5)
    *   `p5_project_target_sub_elements.p5_project_id` -> `p5_projects.id` (Target sub-elemen untuk projek P5)
    *   `p5_project_target_sub_elements.p5_sub_element_id` -> `p5_sub_elements.id` (Target sub-elemen P5)
    *   `p5_project_students.p5_project_id` -> `p5_projects.id` (Siswa yang terlibat dalam projek P5)
    *   `p5_project_students.student_id` -> `students.id` (Siswa yang terlibat dalam projek P5)
    *   `p5_assessments.p5_project_student_id` -> `p5_project_students.id` (Penilaian P5 untuk siswa dalam projek)
    *   `p5_assessments.p5_sub_element_id` -> `p5_sub_elements.id` (Penilaian P5 untuk sub-elemen tertentu)
    *   `p5_assessments.assessed_by` -> `teachers.id` (Guru yang melakukan penilaian P5)

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
*   **[X] Manajemen Siswa dalam Kelas (Admin/TU)**:
    *   Fungsionalitas untuk menambah/mengeluarkan siswa dari sebuah kelas (mengelola tabel `class_student`) telah diimplementasikan di `Admin/ClassController::manageStudents()`, `addStudentToClass()`, `removeStudentFromClass()`.
*   **[X] Manajemen Penugasan Guru-Kelas-Mapel (Admin)**
    *   Tabel `teacher_class_subject_assignments` dibuat (via Migrasi).
    *   Model `TeacherClassSubjectAssignmentModel` dibuat, termasuk method helper `getAssignmentsDetails()` dan `getSubjectsForTeacherInClass()`.
    *   Controller `Admin/TeacherClassSubjectAssignmentController` dibuat dengan fungsi CRUD dasar (index, new, create, delete) untuk mengelola penugasan.
    *   Views `admin/assignments/index.php` dan `admin/assignments/new.php` dibuat.
    *   Rute resource `admin/assignments` ditambahkan dan diproteksi untuk Administrator Sistem.
*   **[X] Modul Akademik Harian - Manajemen Jadwal Pelajaran**:
    *   [X] Tabel `schedules` dibuat (via Migrasi).
    *   [X] Model `ScheduleModel` dibuat dengan validasi dasar dan method helper `getScheduleDetails()`.
    *   [X] Controller `Admin/ScheduleController` dibuat dengan fungsi CRUD (index, new, create, edit, update, delete) untuk mengelola jadwal pelajaran.
    *   [X] Views `admin/schedules/index.php`, `new.php`, dan `edit.php` dibuat.
    *   [X] Rute resource `admin/schedules` ditambahkan dan diproteksi untuk Administrator Sistem dan Staf Tata Usaha.
    *   [X] Navigasi admin diperbarui untuk menyertakan link ke Manajemen Jadwal.
    *   [X] Guru dapat melihat jadwal mengajarnya sendiri via `Guru/ClassViewController::mySchedule()` dan view `guru/schedules/my_schedule.php`. Navigasi guru diperbarui.
    *   [X] Siswa dapat melihat jadwal kelasnya via `Siswa/ScheduleController::classSchedule()` dan view `siswa/schedules/class_schedule.php`. Navigasi siswa diperbarui.
*   **[X] Modul Akademik Harian - Input Presensi Harian oleh Guru**:
    *   [X] Tabel `attendances` dibuat (via Migrasi) untuk mencatat kehadiran siswa per jadwal per tanggal.
    *   [X] Model `AttendanceModel` dibuat dengan konstanta status, validasi, dan method helper.
    *   [X] Controller `Guru/AttendanceController` dibuat dengan method `selectSchedule`, `showAttendanceForm`, dan `saveAttendance`.
    *   [X] Views `guru/attendances/select_schedule.php` dan `guru/attendances/attendance_form.php` dibuat.
    *   [X] Rute dan navigasi guru diperbarui.
*   **[X] Modul Akademik Harian - Pemilihan Mata Pelajaran Pilihan (Siswa Fase F)**:
    *   [X] Tabel `subject_offerings` dan `student_subject_choices` dibuat (via Migrasi).
    *   [X] Model `SubjectOfferingModel` dan `StudentSubjectChoiceModel` dibuat.
    *   [X] Controller `Admin/SubjectOfferingController` dibuat untuk CRUD penawaran mapel oleh admin. Views terkait dibuat.
    *   [X] Controller `Siswa/SubjectChoiceController` dibuat untuk siswa melihat penawaran dan membuat/membatalkan pilihan (via AJAX). View terkait dibuat.
    *   [X] Rute dan navigasi Admin & Siswa diperbarui.
*   **[X] Modul Akademik Harian - Rekapitulasi (Admin/Wali Kelas/Kepala Sekolah)**:
    *   [X] Controller `Admin/RecapController` dibuat.
    *   **Rekapitulasi Presensi**:
        *   Method `RecapController::attendance()` dan `AttendanceModel::getAttendanceRecap()`.
        *   View `admin/recaps/attendance_recap.php` dengan filter tanggal, kelas, dan ekspor DataTables.
        *   Menampilkan total H, I, S, A dan persentase kehadiran per siswa.
    *   **Rekapitulasi Pilihan Mata Pelajaran**:
        *   Method `RecapController::subjectChoices()` dan `StudentSubjectChoiceModel::getSubjectChoiceRecap()`.
        *   View `admin/recaps/subject_choice_recap.php` dengan filter tahun ajaran, semester, mapel, dan opsi sertakan nama siswa, serta ekspor DataTables.
        *   Menampilkan jumlah peminat per mapel, sisa kuota, dan daftar siswa (opsional).
    *   Rute dan navigasi menu "Rekapitulasi" ditambahkan.
*   **[X] Penyempurnaan Hak Akses (Lanjutan) (Sebagian Besar Selesai)**:
    *   Implementasi hak akses granular telah ditingkatkan:
        *   Guru hanya dapat mengelola data (input nilai, input presensi, lihat rekap presensi) yang terkait langsung dengan kelas/mapel yang diajar atau kelas perwaliannya.
        *   Siswa hanya dapat melihat data (jadwal, nilai) miliknya sendiri.
        *   Orang tua hanya dapat melihat data (nilai) anak-anaknya.
        *   Pengecekan kepemilikan data untuk operasi sensitif seperti edit/hapus asesmen telah diimplementasikan (hanya pembuat atau admin).
    *   Fitur untuk guru melihat daftar kelas yang diampu dan siswa di dalamnya telah tersedia (`Guru/ClassViewController`).
*   **[X] Modul Projek P5 (Pengelolaan Data & Struktur Dasar)**:
    *   [X] **Skema Database**: 8 tabel baru telah dirancang dan dimigrasikan:
        *   `p5_themes`: Tema-tema P5.
        *   `p5_projects`: Detail projek P5, terhubung ke `p5_themes`.
        *   `p5_dimensions`: Dimensi Profil Pelajar Pancasila.
        *   `p5_elements`: Elemen turunan dari dimensi, terhubung ke `p5_dimensions`.
        *   `p5_sub_elements`: Sub-elemen spesifik, terhubung ke `p5_elements`.
        *   `p5_project_target_sub_elements`: Sub-elemen yang menjadi target dalam sebuah projek, menghubungkan `p5_projects` dan `p5_sub_elements`.
        *   `p5_project_students`: Siswa yang berpartisipasi dalam projek, menghubungkan `p5_projects` dan `students`.
        *   `p5_assessments`: Penilaian kualitatif P5, menghubungkan `p5_project_students`, `p5_sub_elements`, dan `teachers` (sebagai penilai).
    *   [X] **Model**: Model untuk setiap tabel P5 telah dibuat (`P5ThemeModel`, `P5ProjectModel`, `P5DimensionModel`, `P5ElementModel`, `P5SubElementModel`, `P5ProjectTargetSubElementModel`, `P5ProjectStudentModel`, `P5AssessmentModel`) dengan:
        *   Properti dasar (`$table`, `$primaryKey`, `$allowedFields`, `$useTimestamps`).
        *   Aturan validasi awal.
        *   Aturan validasi kustom `valid_date_range_if_set` ditambahkan di `App\Validation\CustomRules.php` dan diterapkan pada `P5ProjectModel` untuk memastikan `end_date` tidak lebih awal dari `start_date`. Aturan ini juga telah didaftarkan di `Config\Validation.php` dan pesan errornya ditambahkan di `Language\en\Validation.php`.
        *   Metode helper dasar di `P5ProjectStudentModel` (misalnya, `getProject()`, `getStudent()`).
    *   [X] **Pengembangan Fitur Pengelolaan P5 (Admin/Koordinator)**:
        *   **Controllers**:
            *   `Admin/P5ThemeController.php`
            *   `Admin/P5DimensionController.php`
            *   `Admin/P5ElementController.php` (mengelola relasi ke `p5_dimensions`)
            *   `Admin/P5SubElementController.php` (mengelola relasi ke `p5_elements`)
            *   `Admin/P5ProjectController.php` (mengelola relasi ke `p5_themes`, `p5_project_target_sub_elements`, dan `p5_project_students`)
                *   Metode: `index`, `new`, `create`, `edit`, `update`, `delete`.
                *   Metode untuk alokasi siswa: `manageStudents()`, `addStudentToProject()`, `removeStudentFromProject()`.
        *   **Views**:
            *   `app/Views/admin/p5themes/` (index, new, edit)
            *   `app/Views/admin/p5dimensions/` (index, new, edit)
            *   `app/Views/admin/p5elements/` (index, new, edit) - Form menyertakan pemilihan Dimensi Induk.
            *   `app/Views/admin/p5subelements/` (index, new, edit) - Form menyertakan pemilihan Elemen Induk.
            *   `app/Views/admin/p5projects/` (index, new, edit) - Form menyertakan pemilihan Tema dan multi-seleksi Target Sub-elemen.
            *   `app/Views/admin/p5projects/manage_students.php` - View untuk alokasi siswa ke projek.
        *   **Routes**:
            *   Resource routes: `admin/p5themes`, `admin/p5dimensions`, `admin/p5elements`, `admin/p5subelements`, `admin/p5projects`.
            *   Rute spesifik untuk P5 Project Student Management: `admin/p5projects/manage-students/(:num)`, `admin/p5projects/add-student/(:num)`, `admin/p5projects/remove-student/(:num)/(:num)`.
        *   **Navigation**: Link navigasi "P5 Management" (Themes, Dimensions, Elements, Sub-elements, Projects) dan link "Manage Students" pada daftar projek P5.
        *   **Permissions Used**: `manage_p5_themes`, `manage_p5_dimensions`, `manage_p5_elements`, `manage_p5_sub_elements`, `manage_p5_projects`, `manage_p5_project_students`.
    *   [X] **Alokasi Siswa ke Projek P5**: Telah diimplementasikan sebagai bagian dari fitur pengelolaan P5 di atas.
*   **[P] Modul Ekspor ke e-Rapor (Tahap Awal Selesai, Perlu Penyempurnaan)**:
    *   Controller `WaliKelas/EraporController` dibuat untuk form dan proses ekspor.
    *   Model `AssessmentModel::getExportDataForErapor()` diimplementasikan untuk mengambil rata-rata nilai sumatif.
        *   Logika pemfilteran nilai sumatif berdasarkan rentang tanggal semester (Ganjil: Juli-Des, Genap: Jan-Juni dari tahun ajaran terkait) **telah disempurnakan**.
    *   View `wali_kelas/erapor/export_form.php` dibuat.
    *   Library `PhpSpreadsheet` diinstal dan digunakan untuk generate file `.xlsx`.
    *   Rute dan navigasi ditambahkan untuk Wali Kelas.
    *   Pengguna disarankan memverifikasi output Excel dengan template e-Rapor aktual.
*   **[X] Modul Projek P5 (Pengembangan Lanjutan)**:
    *   **Fitur Penetapan Fasilitator Projek P5 (Admin)**:
        *   Tabel database `p5_project_facilitators` (kolom: `id`, `p5_project_id`, `teacher_id`) dibuat dan dimigrasikan.
        *   Model `P5ProjectFacilitatorModel.php` dibuat.
        *   Method `manageFacilitators()`, `addFacilitatorToProject()`, `removeFacilitatorFromProject()` ditambahkan ke `Admin/P5ProjectController.php`.
        *   View `admin/p5projects/manage_facilitators.php` dibuat.
        *   Rute terkait ditambahkan: `admin/p5projects/(:num)/manage-facilitators`, `admin/p5projects/(:num)/add-facilitator`, `admin/p5projects/(:num)/remove-facilitator/(:num)`.
        *   Link navigasi "Kelola Fasilitator" ditambahkan ke daftar projek P5.
    *   **Penyempurnaan Hak Akses Input Penilaian P5 (Guru/Fasilitator)**:
        *   `Guru/P5AssessmentController.php` dimodifikasi untuk menggunakan `P5ProjectFacilitatorModel`.
        *   Method `selectProject()` kini hanya menampilkan projek yang difasilitasi guru tersebut (atau semua untuk Admin).
        *   Method `showAssessmentForm()` dan `saveAssessments()` kini memvalidasi apakah guru adalah fasilitator projek yang diakses (Admin tetap diizinkan).
    *   **Fitur Pelaporan P5 (Admin/Koordinator) - Komprehensif Sebagian**:
        *   **Rekapitulasi per Projek**: Method `report($project_id)` di `Admin/P5ProjectController.php` dan view `admin/p5projects/report.php` sudah ada sebelumnya.
        *   **Rekapitulasi Lintas Projek per Siswa**:
            *   Method `p5Report($student_id)` ditambahkan ke `Admin/StudentController.php`.
            *   Model terkait (`P5ProjectStudentModel`, `P5AssessmentModel`, `P5ProjectModel`, `P5SubElementModel`) digunakan untuk mengambil data.
            *   View `admin/students/p5_report.php` dibuat untuk menampilkan laporan P5 siswa dari semua projek yang diikutinya.
            *   Rute `admin/students/(:num)/p5-report` ditambahkan.
            *   Link "Lihat Laporan P5" ditambahkan ke halaman daftar siswa (`admin/students/index.php`).
        *   Hak akses dikontrol oleh filter grup admin dan permission yang relevan.

## 6. Area Pengembangan Selanjutnya (Prioritas dari Dokumen Desain)

1.  **Modul Projek P5 (Fitur Lanjutan)**:
    *   [ ] Fitur Pelaporan P5 yang lebih detail dan analitik (misalnya, visualisasi progres per dimensi/elemen untuk siswa atau projek).
    *   [ ] Ekspor data P5 untuk e-Rapor (setelah format dan kebutuhan ditentukan dengan jelas).
2.  **Penyempurnaan Modul Ekspor ke e-Rapor**:
    *   Verifikasi lebih lanjut format kolom Excel terhadap template e-Rapor aktual oleh pengguna/pengembang dengan akses ke template. (Lihat panduan di bawah).
    *   Implementasi ekspor data P5 ke Excel jika formatnya sudah ada dan kompatibel.

### Panduan Verifikasi dan Penyesuaian Format Ekspor e-Rapor

Fitur ekspor ke e-Rapor (`WaliKelas/EraporController.php` dan `Models/AssessmentModel::getExportDataForErapor()`) menghasilkan file Excel (.xlsx) yang ditujukan untuk impor ke aplikasi e-Rapor Kemdikbud. Namun, format spesifik e-Rapor dapat bervariasi atau memiliki persyaratan detail. **Pengguna atau pengembang yang memiliki akses ke contoh template e-Rapor Kemdikbud yang valid sangat disarankan untuk melakukan verifikasi dan penyesuaian berikut:**

1.  **Nama dan Urutan Sheet:**
    *   Pastikan nama _sheet_ dalam file Excel sesuai dengan yang diharapkan oleh aplikasi e-Rapor. Saat ini, nama sheet default ("Worksheet") atau nama sheet pertama yang aktif yang digunakan.
    *   Untuk mengganti nama sheet, gunakan: `$spreadsheet->getActiveSheet()->setTitle('NamaSheetSesuaiERapor');` di `EraporController::processExport()`.

2.  **Header Kolom:**
    *   **Urutan Kolom Data Siswa:** Verifikasi urutan kolom seperti `NISN`, `NIS`, `Nama Siswa`. File saat ini: `NISN, NIS, Nama Siswa`.
    *   **Nama/Kode Mata Pelajaran:** Aplikasi e-Rapor mungkin mengharapkan **kode mata pelajaran** resmi, bukan hanya nama mata pelajaran. Saat ini, header mapel adalah `Nama Mata Pelajaran (Sumatif)`.
        *   Data kode mapel (`subject_code`) tersedia di `subjects` table dan bisa diambil dalam `AssessmentModel::getExportDataForErapor()` pada bagian query `$assignedSubjectsQuery`.
        *   Sesuaikan bagian ini di `EraporController::processExport()`:
            ```php
            // Contoh jika menggunakan subject_code:
            // $header[] = esc($subject['subject_code']);
            // atau kombinasi:
            // $header[] = esc($subject['subject_code']) . ' - ' . esc($subject['subject_name']);
            $header[] = esc($subject['subject_name']) . " (Sumatif)"; // Baris saat ini
            ```
    *   **Kolom Tambahan:** Periksa apakah ada kolom wajib lain sebelum atau sesudah daftar mata pelajaran (misalnya, jenis kelamin, tanggal lahir, dll.). Jika ada, data ini perlu:
        *   Ditambahkan ke query pengambilan data siswa di `AssessmentModel::getExportDataForErapor()`.
        *   Dimasukkan ke dalam array `$header` dan `$rowData` di `EraporController::processExport()`.

3.  **Format Data:**
    *   **Format Nilai:** Nilai saat ini dibulatkan ke bilangan bulat terdekat (`round($averageScore)`). Pastikan format ini diterima (misalnya, apakah perlu desimal, atau format teks). Jika perlu format spesifik, sesuaikan pembulatan atau konversi tipe data saat memasukkan ke `$rowData`.
    *   **Penanganan Nilai Kosong/Belum Diisi:** Saat ini, nilai yang tidak ada akan diekspor sebagai string kosong (`''`). Verifikasi apakah e-Rapor mengharapkan format lain (misalnya, angka 0, "Belum Ada Nilai", atau sel kosong). Ini diatur di `AssessmentModel` pada bagian:
        ```php
        // $studentScores[$subjectId] = ''; // Baris saat ini
        ```
    *   **Format Tanggal (jika ada kolom tanggal):** Pastikan format tanggal (misalnya, `dd-mm-yyyy` atau `yyyy-mm-dd`) sesuai.

4.  **Data Siswa Tambahan:**
    *   Jika e-Rapor memerlukan data siswa yang belum ada di ekspor (misal, tempat lahir, nama orang tua), query di `AssessmentModel::getExportDataForErapor()` pada bagian `$studentModel->select(...)` perlu dimodifikasi untuk mengambil field tambahan dari tabel `students` atau tabel terkait lainnya. Kemudian, data tersebut harus ditambahkan ke `$rowData` di `EraporController`.

5.  **Baris Header Tambahan atau Baris Awal:**
    *   Beberapa template mungkin memiliki beberapa baris header atau informasi sekolah di baris-baris awal. Jika demikian, penulisan data utama (`$sheet->fromArray($header, NULL, 'A1');` dan `$sheet->fromArray($rowData, NULL, 'A' . $rowIndex);`) mungkin perlu disesuaikan agar mulai dari baris yang benar.

**Contoh Penyesuaian (Ilustratif):**

*   **Menggunakan Kode Mapel sebagai Header di `EraporController.php`:**
    ```php
    // Ganti:
    // $header[] = esc($subject['subject_name']) . " (Sumatif)";
    // Dengan (jika subject_code sudah ada di $exportData['subjects'][$subjectId]['subject_code']):
    // $header[] = esc($subject['subject_code']);
    ```

*   **Menambahkan Jenis Kelamin ke Ekspor:**
    1.  Di `AssessmentModel.php` (`getExportDataForErapor`):
        ```php
        // Ubah query siswa:
        // $students = $studentModel->select('students.id, students.nisn, students.nis, students.full_name, students.gender') ...
        // Tambahkan ke array output siswa:
        // 'gender' => $student['gender'],
        ```
    2.  Di `EraporController.php` (`processExport`):
        ```php
        // Tambahkan ke header:
        // $header = ['NISN', 'NIS', 'Nama Siswa', 'Jenis Kelamin', ...];
        // Tambahkan ke rowData:
        // $rowData = [ ..., $student['gender'] ?? '', ...];
        ```

Dengan melakukan verifikasi dan penyesuaian ini, diharapkan file Excel yang dihasilkan dapat kompatibel secara penuh dengan aplikasi e-Rapor Kemdikbud yang digunakan sekolah.

3.  **Penyempurnaan Hak Akses (Minor/Lanjutan)**:
    *   Review dan audit berkelanjutan untuk memastikan konsistensi dan keamanan hak akses di seluruh modul.
    *   Implementasi peran "Koordinator P5" jika diperlukan, dengan hak akses spesifik.

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
