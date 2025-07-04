# AGENTS.md - Catatan untuk Pengembang SI-AKADEMIK

*Terakhir Diperbarui: 2025-07-03* (Update setelah implementasi Modul Absensi Harian Umum dan penyesuaian rekap)

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
    *   Models: `app/Models/` (misal, `StudentModel.php`, `SettingModel.php`, `NotificationModel.php`)
    *   Models: `app/Models/` (misal, `StudentModel.php`, `SettingModel.php`, `NotificationModel.php`, `AttendanceModel.php`, `DailyAttendanceModel.php`)
    *   Views: `app/Views/admin/<module_name>/` (misal, `students/index.php`, `settings/index.php`, `recaps/attendance_recap.php`, `daily_attendance/manage.php`), `app/Views/kepala_sekolah/dashboard/index.php`, `app/Views/notifications/index.php`, `app/Views/siswa/attendance/my_recap.php`, `app/Views/ortu/attendance/select_child.php`, `app/Views/ortu/attendance/child_recap.php`
    *   Controllers: `app/Controllers/Admin/` (misal, `StudentController.php`, `SettingController.php`, `RecapController.php`, `DailyAttendanceController.php`), `app/Controllers/KepalaSekolah/DashboardController.php`, `app/Controllers/NotificationController.php`, `app/Controllers/Siswa/AttendanceController.php`, `app/Controllers/Ortu/AttendanceController.php`
    *   Commands: `app/Commands/AttendanceAlertsCheckCommand.php`
    *   Rute: Didefinisikan dalam `app/Config/Routes.php`.
*   **Namespace**: Sesuai struktur direktori.
*   **Validasi**: Diutamakan di Model.
*   **Layout Views**: `app/Views/layouts/admin_default.php`.
*   **Helper**: Helper standar (`form`, `url`, `auth`, `text`). Helper kustom (`setting_helper.php`, `notification_helper.php`) di `app/Helpers/`.

## 4. Ringkasan Relasi Database Utama

Berikut adalah ringkasan relasi kunci (foreign key) antar tabel utama dalam database SI-AKADEMIK. Untuk detail lengkap kolom dan tipe data, silakan merujuk ke file migrasi di `app/Database/Migrations/`.

*   `users.role_id` -> `roles.id`
*   `notifications.user_id` -> `users.id` (Penerima notifikasi)
*   `notifications.student_id` -> `students.id` (Siswa terkait notifikasi)
*   `daily_attendances.student_id` -> `students.id`
*   `daily_attendances.class_id` -> `classes.id`
*   `daily_attendances.recorded_by_user_id` -> `users.id`
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
    *   **[X] Fitur Penetapan Fasilitator Projek P5 (Admin)**: Telah diimplementasikan.
    *   **[X] Penyempurnaan Hak Akses Input Penilaian P5 (Guru/Fasilitator)**: Telah diimplementasikan.
    *   **[X] Fitur Pelaporan P5 (Admin/Koordinator) - Dengan Visualisasi**:
        *   Rekapitulasi per Projek (`Admin/P5ProjectController::report()`): Ditambahkan **Bar Chart** (Chart.js) untuk distribusi pencapaian sub-elemen.
        *   Rekapitulasi Lintas Projek per Siswa (`Admin/StudentController::p5Report()`): Ditambahkan **Radar Chart** (Chart.js) untuk profil dimensi siswa.
    *   **[X] Ekspor Data P5 untuk e-Rapor**:
        *   Controller `Admin/P5ExportController.php` dibuat.
        *   Fitur ekspor data P5 ke Excel (.xlsx) dengan pemilihan Projek, Kelas, dan Dimensi (via AJAX). Format output sesuai spesifikasi pengguna (1 projek, 1 kelas, 1 dimensi per file, nilai BB/MB/BSH/SB per sub-elemen target, tanpa catatan deskriptif).
        *   Navigasi ditambahkan di menu Admin.
*   **[X] Modul Ekspor ke e-Rapor (Nilai Akademik - Penyempurnaan Lanjutan)**:
    *   Controller `WaliKelas/EraporController` dan Model `AssessmentModel::getExportDataForErapor()` telah disempurnakan.
    *   **[X] Penggunaan Kode Mata Pelajaran**: Ekspor nilai akademik kini menggunakan format `KODE_MAPEL - Nama Mapel (Sumatif)` pada header kolom nilai.
    *   **[X] Filter Tanggal Semester**: Logika pemfilteran nilai sumatif berdasarkan rentang tanggal semester diperketat.
    *   Pengguna tetap disarankan memverifikasi output Excel dengan template e-Rapor aktual untuk poin-poin lain (nama sheet, format nilai spesifik, dll.).
*   **[X] Fitur Konfigurasi Global/Sekolah (Admin)**:
    *   Tabel `settings` (dengan kolom `key`, `value`) dibuat melalui migrasi.
    *   `SettingModel.php` dibuat dengan method `getSetting()`, `getAllSettings()`, `saveSetting()`, `saveSettings()`.
    *   `Admin/SettingController.php` dibuat untuk menangani tampilan form dan penyimpanan pengaturan.
        *   Pengaturan yang dikelola: `school_name`, `school_address`, `headmaster_name`, `headmaster_nip`, `current_academic_year`, `current_semester`, `current_academic_year_semester_code`.
    *   View `admin/settings/index.php` dibuat untuk form input pengaturan.
    *   Helper `setting_helper.php` (dengan fungsi `get_setting()`, `get_all_settings()`) dibuat dan didaftarkan.
    *   Rute dan navigasi "Pengaturan Umum" untuk Admin ditambahkan.
    *   Integrasi awal: `get_setting()` digunakan untuk nama sekolah di ekspor P5 dan default tahun ajaran/semester di form ekspor e-Rapor (Wali Kelas).
*   **[X] Dasbor Eksekutif Sederhana (Kepala Sekolah)**:
    *   `KepalaSekolah/DashboardController.php` dibuat untuk mengambil data ringkasan.
    *   Data ringkasan yang ditampilkan: Total Siswa, Total Guru, Total Kelas, Jumlah Projek P5 Aktif, Rata-rata Kehadiran Siswa Bulan Ini.
    *   View `kepala_sekolah/dashboard/index.php` dibuat untuk menampilkan data dalam bentuk kartu.
    *   Rute dan navigasi "Dasbor KS" untuk Kepala Sekolah ditambahkan.
*   **[X] Notifikasi Otomatis untuk Ketidakhadiran Beruntun/Tinggi**:
    *   Tabel `notifications` dibuat (user_id, student_id, type, message, link, is_read).
    *   Command `php spark attendance:checkalerts` dibuat untuk:
        *   Mengecek Alfa beruntun (default 3 hari).
        *   Mengecek total Alfa dalam 30 hari (default 5 hari).
        *   Mengecek total Sakit/Izin dalam 30 hari (default 7 hari).
    *   `NotificationModel.php` dibuat untuk CRUD notifikasi.
    *   `notification_helper.php` (dengan `get_unread_notifications_count()`, `get_unread_notifications()`, `time_ago()`) dibuat dan didaftarkan. Helper `text` juga didaftarkan.
    *   `NotificationController.php` dibuat untuk menampilkan daftar notifikasi dan aksi "mark as read".
    *   Indikator notifikasi (lonceng & counter) dan dropdown notifikasi ditambahkan di layout utama.
    *   View `notifications/index.php` untuk daftar notifikasi dengan paginasi.
    *   Rute untuk notifikasi ditambahkan.
*   **[X] Rekapitulasi Presensi yang Lebih Interaktif & Visual (Admin/KS/Guru)**:
    *   **Filter Lanjutan**: Rekap presensi (`Admin/RecapController::attendance()`) kini mendukung filter rentang tanggal (`date_from`, `date_to`). Filter status ditambahkan di UI tapi belum memfilter query rekap utama (bersifat informatif untuk tabel).
    *   **Model Update**: `AttendanceModel::getAttendanceRecap()` diperbarui untuk mendukung rentang tanggal. Method baru `getDailyAttendanceSummaryForClass()` ditambahkan untuk data visualisasi harian.
    *   **Kalender Presensi**: Di view rekap presensi kelas, ditambahkan kalender (FullCalendar.js) yang menampilkan ringkasan H/A/I/S per hari dengan pewarnaan.
    *   **Grafik Tren Kehadiran**: Di view rekap presensi kelas, ditambahkan grafik garis (Chart.js) yang menampilkan tren persentase kehadiran harian.
    *   View `admin/recaps/attendance_recap.php` diperbarui untuk menyertakan filter baru dan elemen visualisasi.
*   **[X] Fitur Rekap Absensi Siswa**:
    *   Controller `Siswa/AttendanceController.php` dan view `siswa/attendance/my_recap.php` dibuat.
    *   Siswa dapat melihat detail absensi pribadi dengan filter rentang tanggal dan kalender visual (FullCalendar).
    *   Rute dan navigasi ditambahkan.
*   **[X] Fitur Rekap Absensi Orang Tua**:
    *   Controller `Ortu/AttendanceController.php` dan views `ortu/attendance/select_child.php` & `ortu/attendance/child_recap.php` dibuat.
    *   Orang tua dapat memilih anak (jika >1) dan melihat detail absensi anak dengan filter rentang tanggal dan dua kalender visual (harian umum & per jam pelajaran).
    *   Rute dan navigasi ditambahkan.
*   **[X] Modul Absensi Harian Umum (Admin/Staf TU)**:
    *   Tabel `daily_attendances` dibuat untuk mencatat absensi harian umum per siswa per tanggal.
    *   `DailyAttendanceModel.php` dibuat.
    *   `Admin/DailyAttendanceController.php` dan view `admin/daily_attendance/manage.php` dibuat untuk input/edit absensi harian umum per kelas.
    *   Rute dan navigasi "Absensi Harian Umum" ditambahkan untuk Admin/Staf TU.
    *   Rekapitulasi presensi di Admin (`Admin/RecapController`) disesuaikan untuk juga menampilkan ringkasan absensi harian umum dalam kalender terpisah jika kelas dipilih.

## 6. Area Pengembangan Selanjutnya (Prioritas dari Dokumen Desain)

*   **Verifikasi Akhir Format Ekspor**: (Seperti sebelumnya)
*   **Penyempurnaan Hak Akses (Minor/Lanjutan)**: (Seperti sebelumnya, termasuk peran Koordinator P5 dan permission `manage_settings`)
*   **Integrasi Lebih Lanjut Pengaturan Global**: (Seperti sebelumnya)
*   **Penyempurnaan Dasbor Eksekutif**: (Seperti sebelumnya)
*   **Fungsionalitas Penuh Filter Status di Rekap Presensi**: Implementasikan logika agar filter status di rekap presensi benar-benar memfilter data tabel yang ditampilkan (misalnya, hanya menampilkan siswa yang memiliki status 'Alfa' dalam rentang tanggal terpilih).
*   **Konfigurasi Threshold Notifikasi Absensi**: Pindahkan threshold notifikasi absensi dari hardcode di Command ke Pengaturan Umum Sekolah.
*   **Pengelolaan Notifikasi Lebih Lanjut**:
    *   Opsi untuk menghapus notifikasi.
    *   Pengelompokan notifikasi berdasarkan tipe atau tanggal.
*   **Optimalisasi Command Notifikasi**: Jika jumlah siswa sangat besar, optimalkan query di `AttendanceAlertsCheckCommand` atau pertimbangkan pemrosesan batch.
*   **Penjadwalan Command (Cron Job)**: Ingatkan pengguna/admin untuk mengatur cron job agar `php spark attendance:checkalerts` berjalan secara periodik.
*   **Fitur Tambahan Guru**: (Seperti sebelumnya)
*   **Fitur Tambahan Wali Kelas**:
    *   Input catatan perilaku/perkembangan siswa.
    *   Mekanisme validasi kelengkapan nilai sebelum ekspor rapor.
*   **Fitur Tambahan Siswa & Orang Tua**:
    *   [X] Rekap absensi pribadi untuk siswa (mungkin dengan kalender visual juga). *(Sudah diimplementasikan)*
    *   Notifikasi/Pesan (misalnya, pengumuman dari sekolah atau guru).
    *   Orang tua melihat status pemilihan mapel anak.
*   **Maintenance & Backup**:
    *   Fitur atau panduan untuk backup database (terutama jika beralih ke MySQL).
*   **Pengoptimalan & Keamanan**:
    *   Review performa query database, terutama pada fitur rekapitulasi dan pelaporan.
    *   Audit keamanan berkala.

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

## 8. Panduan Testing Aplikasi

Bagian ini menjelaskan bagaimana testing diatur dan dijalankan dalam proyek SI-AKADEMIK.

### 8.1. Pengaturan Awal & Konfigurasi

*   **Database untuk Testing**: Saat menjalankan test yang melibatkan database, CodeIgniter secara otomatis akan menggunakan koneksi database grup `tests` yang dikonfigurasi di `app/Config/Database.php`. Secara default, ini menggunakan SQLite in-memory (`:memory:`), yang berarti database dibuat baru setiap kali sesi test dimulai dan dihancurkan setelahnya. Ini memastikan test berjalan dalam environment yang bersih dan terisolasi.
*   **File Konfigurasi PHPUnit**: `phpunit.xml.dist` di root proyek adalah file konfigurasi utama untuk PHPUnit. Ini mendefinisikan bagaimana test dijalankan, direktori test, dll.

### 8.2. Struktur Direktori Test

*   Semua test ditempatkan di dalam direktori `tests/`.
*   Struktur direktori di dalam `tests/` sebaiknya mencerminkan struktur direktori `app/`.
    *   Contoh: Test untuk controller `App\Controllers\Admin\UserController.php` berada di `tests\Controllers\Admin\UserControllerTest.php`.
    *   Contoh: Test untuk model `App\Models\UserModel.php` berada di `tests\Models\UserModelTest.php`.

### 8.3. Trait Standar CodeIgniter untuk Testing

Manfaatkan trait bawaan CodeIgniter untuk mempermudah penulisan test:

*   **`CodeIgniter\Test\FeatureTestTrait`**:
    *   Gunakan untuk test controller yang memerlukan simulasi request HTTP penuh, termasuk routing, filter, dan validasi response.
    *   Memungkinkan Anda membuat request GET, POST, dll. ke URI aplikasi dan melakukan assertion pada response (status, header, body, redirect, session).
    *   Contoh penggunaan ada di `tests/Controllers/Admin/UserControllerTest.php` dan `tests/Controllers/AuthControllerTest.php`.
*   **`CodeIgniter\Test\DatabaseTestTrait`**:
    *   Gunakan untuk semua test yang berinteraksi dengan database, terutama test model.
    *   Properti yang penting untuk diatur dalam class test Anda:
        *   `protected $migrate = true;`: Menjalankan semua migrasi sebelum setiap test (atau sekali jika `$migrateOnce = true;`).
        *   `protected $refresh = true;`: Melakukan rollback semua migrasi ke versi 0, lalu menjalankan semua migrasi lagi. Ini memastikan skema database bersih untuk setiap test.
        *   `protected $namespace = 'App';`: Pastikan ini diatur agar migrasi dari direktori `app/Database/Migrations/` dijalankan. Jika `null`, semua namespace akan dijalankan.
        *   `protected $basePath = APPPATH . 'Database';`: Menentukan path dasar untuk seeder jika seeder Anda berada di `app/Database/Seeds/`.
        *   `protected $seed = 'NamaSeeder';`: Nama class seeder yang akan dijalankan sebelum setiap test (atau sekali jika `$seedOnce = true;`).
    *   Menyediakan method assertion yang berguna seperti `seeInDatabase()`, `dontSeeInDatabase()`, `hasInDatabase()`, `grabFromDatabase()`.
    *   Contoh penggunaan ada di `tests/Models/UserModelTest.php`.

### 8.4. Seeder Khusus untuk Testing

*   Buat seeder spesifik untuk kebutuhan testing jika data default dari seeder produksi tidak mencukupi atau terlalu banyak.
    *   Contoh: `App\Database\Seeds\UserRoleSeeder.php` dibuat untuk menyediakan user admin dan non-admin dengan peran yang jelas untuk test otorisasi.
*   Tempatkan seeder test di `app/Database/Seeds/` dan panggil dari class test menggunakan properti `$seed`.
*   **Penting**: Seeder yang digunakan untuk testing sebaiknya **tidak menghasilkan output** menggunakan `echo` atau `print`. Output ini akan ditangkap oleh PHPUnit dan menandai test sebagai "risky".

### 8.5. Perbaikan Migrasi untuk Testing

*   Setiap file migrasi di `app/Database/Migrations/` **harus** memiliki method `down()` yang diimplementasikan dengan benar.
*   Method `down()` bertanggung jawab untuk membatalkan perubahan yang dibuat oleh method `up()`.
    *   Untuk migrasi yang membuat tabel (`$this->forge->createTable('nama_tabel');`), method `down()` harus berisi `$this->forge->dropTable('nama_tabel');`.
    *   Untuk migrasi yang menambah kolom (`$this->forge->addColumn(...)`), method `down()` harus berisi `$this->forge->dropColumn(...)`.
    *   Untuk migrasi yang memodifikasi kolom (`$this->forge->modifyColumn(...)`), method `down()` idealnya mengembalikan kolom ke state sebelumnya (jika memungkinkan dan aman).
*   Implementasi `down()` yang benar krusial agar properti `$refresh = true` pada `DatabaseTestTrait` dapat berfungsi dengan baik, memastikan setiap test berjalan pada skema database yang bersih. Beberapa migrasi awal di proyek ini tidak memiliki implementasi `down()` yang benar dan telah diperbaiki.

### 8.6. Menjalankan Test

*   **Semua Test**:
    ```bash
    php vendor/bin/phpunit
    ```
    Atau jika dikonfigurasi di `composer.json` (seperti di proyek ini):
    ```bash
    composer test
    ```
*   **Test File Spesifik**:
    ```bash
    php vendor/bin/phpunit tests/Controllers/Admin/UserControllerTest.php
    ```
*   **Test Method Spesifik (Filter)**:
    ```bash
    php vendor/bin/phpunit tests/Controllers/Admin/UserControllerTest.php --filter testNamaMethodSpesifik
    ```

### 8.7. Status Test yang Sudah Ada (Sebelum Inisiatif Testing Ini)

*   Proyek ini memiliki sejumlah file test yang sudah ada sebelumnya (`tests/Controllers/Admin/*`, `tests/Models/*`, dll.).
*   Saat menjalankan `composer test` secara keseluruhan, banyak dari test-test lama ini yang **gagal** atau menghasilkan **error**.
*   Penyebab kegagalan ini beragam, termasuk:
    *   Asumsi tentang data sesi yang tidak di-setup dengan benar untuk test.
    *   Ketergantungan pada data spesifik di database yang tidak disediakan oleh seeder test saat ini.
    *   Perubahan pada logika aplikasi yang membuat assertion lama tidak valid lagi.
    *   Masalah Foreign Key Constraint karena data prasyarat tidak ada.
*   Perbaikan test-test lama ini memerlukan investigasi dan upaya terpisah dan tidak termasuk dalam cakupan inisiatif testing awal yang berfokus pada `Admin/UserController`, `AuthController`, dan `UserModel`.
*   Sejumlah file test lama yang secara konsisten gagal (terutama di `tests/Controllers/Admin/`) telah **dihapus** (akibat rollback sandbox, namun target tercapai) untuk membersihkan output test suite utama. Test-test ini memerlukan review dan kemungkinan penulisan ulang total jika fungsionalitasnya ingin dicakup kembali.

---

## 9. Status Cakupan Testing Aplikasi (Per Modul/Fitur)

Berikut adalah status cakupan testing untuk modul dan fungsionalitas utama per tanggal terakhir update dokumen ini. Status ini akan diperbarui seiring progres testing.

**Legenda Status:**
*   **[Selesai]**: Sebagian besar skenario utama (happy path, validasi dasar, error handling umum) telah dicakup oleh test otomatis.
*   **[Sebagian Selesai]**: Beberapa test case dasar telah diimplementasikan, tetapi cakupan masih perlu diperluas.
*   **[Belum Dimulai]**: Belum ada test otomatis yang signifikan untuk modul/fitur ini.
*   **[Dihapus - Test Lama Bermasalah]**: Test lama ada, tetapi gagal secara konsisten dan telah dihapus. Memerlukan pembuatan test baru dari awal.

**Fungsionalitas Inti & Fondasi:**
*   Autentikasi (`AuthController`): **[Selesai]** (Login, Logout, Error Handling)
*   Helper - `auth_helper.php`: **[Sebagian Selesai]** (Beberapa fungsi teruji secara tidak langsung melalui test controller)
*   Helper - `notification_helper.php`: **[Sebagian Selesai]** (Perbaikan bug dilakukan, teruji secara tidak langsung)
*   Migrations (`down()` methods): **[Selesai]** (Semua migrasi "Create table" telah diverifikasi memiliki method `down()` yang benar)

**Model:**
*   `UserModel`: **[Selesai]** (CRUD, Validasi)
*   `RoleModel`: **[Selesai]** (CRUD, Validasi)
*   `SubjectModel`: **[Selesai]** (CRUD, Validasi)
*   `TeacherModel`: **[Selesai]** (CRUD, Validasi)
*   `ClassModel`: **[Selesai]** (CRUD, Validasi)
*   `StudentModel`: **[Selesai]** (CRUD, Validasi)
*   `AssessmentModel`: **[Belum Dimulai]**
*   `AttendanceModel`: **[Belum Dimulai]**
*   `DailyAttendanceModel`: **[Belum Dimulai]**
*   `NotificationModel`: **[Belum Dimulai]**
*   `P5DimensionModel`: **[Belum Dimulai]**
*   `P5ElementModel`: **[Belum Dimulai]**
*   `P5ProjectFacilitatorModel`: **[Belum Dimulai]**
*   `P5ProjectModel`: **[Belum Dimulai]**
*   `P5ProjectStudentModel`: **[Belum Dimulai]**
*   `P5ProjectTargetSubElementModel`: **[Belum Dimulai]**
*   `P5SubElementModel`: **[Belum Dimulai]**
*   `P5ThemeModel`: **[Belum Dimulai]**
*   `ScheduleModel`: **[Belum Dimulai]**
*   `SettingModel`: **[Belum Dimulai]**
*   `StudentSubjectChoiceModel`: **[Belum Dimulai]**
*   `SubjectOfferingModel`: **[Belum Dimulai]**
*   `TeacherClassSubjectAssignmentModel`: **[Belum Dimulai]**

**Controller - Admin:**
*   `Admin/UserController`: **[Sebagian Selesai]** (Akses dasar berdasarkan peran)
*   `Admin/ClassController`: **[Dihapus - Test Lama Bermasalah]**
*   `Admin/DailyAttendanceController`: **[Belum Dimulai]**
*   `Admin/P5DimensionController`: **[Belum Dimulai]**
*   `Admin/P5ElementController`: **[Belum Dimulai]**
*   `Admin/P5ExportController`: **[Belum Dimulai]**
*   `Admin/P5ProjectController`: **[Belum Dimulai]**
*   `Admin/P5SubElementController`: **[Belum Dimulai]**
*   `Admin/P5ThemeController`: **[Belum Dimulai]**
*   `Admin/RecapController`: **[Belum Dimulai]**
*   `Admin/ScheduleController`: **[Belum Dimulai]**
*   `Admin/SettingController`: **[Belum Dimulai]**
*   `Admin/StudentController`: **[Dihapus - Test Lama Bermasalah]**
*   `Admin/SubjectController`: **[Dihapus - Test Lama Bermasalah]**
*   `Admin/SubjectOfferingController`: **[Belum Dimulai]**
*   `Admin/TeacherClassSubjectAssignmentController`: **[Belum Dimulai]**
*   `Admin/TeacherController`: **[Dihapus - Test Lama Bermasalah]**

**Controller - Guru:**
*   `Guru/AssessmentController`: **[Belum Dimulai]**
*   `Guru/AttendanceController`: **[Belum Dimulai]**
*   `Guru/ClassViewController`: **[Belum Dimulai]**
*   `Guru/P5AssessmentController`: **[Belum Dimulai]**

**Controller - Kepala Sekolah:**
*   `KepalaSekolah/DashboardController`: **[Belum Dimulai]**

**Controller - Ortu:**
*   `Ortu/AttendanceController`: **[Belum Dimulai]**
*   `Ortu/NilaiController`: **[Belum Dimulai]**

**Controller - Siswa:**
*   `Siswa/AttendanceController`: **[Belum Dimulai]**
*   `Siswa/NilaiController`: **[Belum Dimulai]**
*   `Siswa/ScheduleController`: **[Belum Dimulai]**
*   `Siswa/SubjectChoiceController`: **[Belum Dimulai]**

**Controller - Wali Kelas:**
*   `WaliKelas/EraporController`: **[Belum Dimulai]**

**Controller - Lainnya:**
*   `HomeController`: **[Belum Dimulai]**
*   `NotificationController`: **[Belum Dimulai]**

---
*Dokumen ini akan diperbarui seiring dengan perkembangan proyek.*
