# AGENTS.md - Catatan untuk Pengembang SI-AKADEMIK

Dokumen ini berisi catatan, konvensi, dan panduan untuk agen (termasuk AI atau pengembang manusia) yang bekerja pada proyek SI-AKADEMIK SMAN 1 Campurdarat.

## 1. Ringkasan Teknologi

*   **Framework**: CodeIgniter 4 (saat ini versi 4.6.1)
*   **Bahasa**: PHP (saat ini menggunakan versi 8.3.6)
*   **Database**: SQLite (lokasi: `writable/database.sqlite`) untuk pengembangan awal. Desain akhir menargetkan MySQL.
*   **Manajemen Dependensi**: Composer
*   **Frontend**: Bootstrap 5 (via CDN) telah diintegrasikan sebagai dasar UI. Menggunakan master layout `app/Views/layouts/admin_default.php`.

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

## 4. Status Implementasi Saat Ini (untuk Pengembang)

*   **[X] Fondasi Proyek**: PHP, Composer, CodeIgniter 4 setup.
*   **[X] Database**: Skema database awal (semua tabel dari dokumen desain) telah dimigrasikan. SQLite digunakan.
*   **[X] Seeding**: Seeder untuk tabel `roles` telah dibuat dan dijalankan.
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

## 5. Area Pengembangan Selanjutnya (Prioritas dari Dokumen Desain)

1.  **Penyempurnaan Hak Akses**:
    *   Implementasi hak akses yang lebih granular berdasarkan peran (misalnya, tidak semua pengguna admin bisa mengakses semua fitur admin, guru hanya bisa akses data mapel dan kelasnya, dll.).
    *   Pastikan filter dan controller memeriksa peran sebelum mengizinkan aksi (misal, hanya admin yang bisa delete user).
3.  **Modul Penilaian (Bank Nilai)**:
    *   Antarmuka input nilai formatif dan sumatif.
    *   Logika penyimpanan dan tampilan nilai.
4.  **Manajemen Siswa dalam Kelas**:
    *   Fungsionalitas untuk menambah/mengeluarkan siswa dari sebuah kelas (mengelola tabel `class_student`).
5.  **Modul Projek P5**:
    *   Desain detail tabel jika diperlukan.
    *   Implementasi fitur terkait P5.
6.  **Modul Ekspor ke e-Rapor**:
    *   Ini adalah fitur kunci dan kompleks yang memerlukan koordinasi terkait format template Excel.

## 6. Perintah Berguna CodeIgniter Spark

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
