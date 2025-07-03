<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($pageTitle ?? 'SI-AKADEMIK') ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Bootstrap Icons CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- DataTables Bootstrap 5 CSS -->
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <!-- DataTables Buttons Bootstrap 5 CSS -->
    <link href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css" rel="stylesheet">
    <!-- Font Awesome CSS (for fas icons) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" integrity="sha512-1ycn6IcaQQ40/MKBW2W4Rhis/DbILU74C1vSrLJxCq57o941Ym01SwNsOMqvEBFlcgUA6xLiPY/NS5R+E6ztJQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        /* Custom DataTables Buttons Styling */
        .dt-buttons .btn {
            margin-right: 0.25rem; /* Add some space between buttons */
            margin-bottom: 0.5rem; /* Add some space below buttons if they wrap */
        }
        div.dt-buttons {
            margin-bottom: 0.5rem; /* Add space below the button container */
        }

        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .main-content {
            flex: 1;
            padding-top: 4.5rem; /* Adjust based on navbar height */
        }
        .footer {
            background-color: #f8f9fa;
            padding: 1rem 0;
            font-size: 0.9rem;
            text-align: center;
        }
        /* Custom styles for adminLTE-like feel if desired later, or general improvements */
        .navbar {
            box-shadow: 0 2px 4px rgba(0,0,0,.1);
        }
    </style>
</head>
<body>
    <?php $session = session(); ?>

    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?= site_url('/') ?>">SI-AKADEMIK</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavDropdown" aria-controls="navbarNavDropdown" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNavDropdown">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <?php if (is_logged_in()): ?>
                        <?php if (hasRole(['Administrator Sistem', 'Staf Tata Usaha', 'Kepala Sekolah'])): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle <?= (strpos(uri_string(), 'admin/students') !== false || strpos(uri_string(), 'admin/teachers') !== false || strpos(uri_string(), 'admin/subjects') !== false || strpos(uri_string(), 'admin/classes') !== false) ? 'active' : '' ?>"
                                   href="#" id="dataIndukDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    Data Induk
                                </a>
                                <ul class="dropdown-menu" aria-labelledby="dataIndukDropdown">
                                    <li><a class="dropdown-item <?= (strpos(uri_string(), 'admin/students') !== false) ? 'active' : '' ?>" href="<?= site_url('admin/students') ?>">Students</a></li>
                                    <li><a class="dropdown-item <?= (strpos(uri_string(), 'admin/teachers') !== false) ? 'active' : '' ?>" href="<?= site_url('admin/teachers') ?>">Teachers</a></li>
                                    <li><a class="dropdown-item <?= (strpos(uri_string(), 'admin/subjects') !== false) ? 'active' : '' ?>" href="<?= site_url('admin/subjects') ?>">Subjects</a></li>
                                    <li><a class="dropdown-item <?= (strpos(uri_string(), 'admin/classes') !== false) ? 'active' : '' ?>" href="<?= site_url('admin/classes') ?>">Classes</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item <?= (strpos(uri_string(), 'admin/schedules') !== false) ? 'active' : '' ?>" href="<?= site_url('admin/schedules') ?>">Schedule Management</a></li>
                                    <li><a class="dropdown-item <?= (strpos(uri_string(), 'admin/subject-offerings') !== false) ? 'active' : '' ?>" href="<?= site_url('admin/subject-offerings') ?>">Subject Offerings</a></li>
                                </ul>
                            </li>
                        <?php endif; ?>

                        <?php if (hasRole(['Administrator Sistem', 'Staf Tata Usaha'])): // Or a specific P5 Coordinator role ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle <?= (strpos(uri_string(), 'admin/p5') !== false) ? 'active' : '' ?>"
                                   href="#" id="p5ManagementDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    P5 Management
                                </a>
                                <ul class="dropdown-menu" aria-labelledby="p5ManagementDropdown">
                                <ul class="dropdown-menu" aria-labelledby="p5ManagementDropdown">
                                    <li><a class="dropdown-item <?= (strpos(uri_string(), 'admin/p5themes') !== false) ? 'active' : '' ?>" href="<?= site_url('admin/p5themes') ?>">P5 Themes</a></li>
                                <ul class="dropdown-menu" aria-labelledby="p5ManagementDropdown">
                                    <li><a class="dropdown-item <?= (strpos(uri_string(), 'admin/p5themes') !== false) ? 'active' : '' ?>" href="<?= site_url('admin/p5themes') ?>">P5 Themes</a></li>
                                    <li><a class="dropdown-item <?= (strpos(uri_string(), 'admin/p5dimensions') !== false) ? 'active' : '' ?>" href="<?= site_url('admin/p5dimensions') ?>">P5 Dimensions</a></li>
                                    <li><a class="dropdown-item <?= (strpos(uri_string(), 'admin/p5elements') !== false) ? 'active' : '' ?>" href="<?= site_url('admin/p5elements') ?>">P5 Elements</a></li>
                                    <li><a class="dropdown-item <?= (strpos(uri_string(), 'admin/p5subelements') !== false) ? 'active' : '' ?>" href="<?= site_url('admin/p5subelements') ?>">P5 Sub-elements</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item <?= (strpos(uri_string(), 'admin/p5projects') !== false) ? 'active' : '' ?>" href="<?= site_url('admin/p5projects') ?>">P5 Projects</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item <?= (strpos(uri_string(), 'admin/p5export') !== false) ? 'active' : '' ?>" href="<?= site_url('admin/p5export') ?>">Ekspor P5 ke e-Rapor</a></li>
                                </ul>
                            </li>
                        <?php endif; ?>

                        <?php if (isAdmin()): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= (strpos(uri_string(), 'admin/users') !== false) ? 'active' : '' ?>" href="<?= site_url('admin/users') ?>">User Management</a>
                        </li>
                        <?php endif; ?>

                        <!-- Guru Menu (includes My Classes and Assessments) -->
                        <?php if (hasRole(['Guru', 'Administrator Sistem'])): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle <?= (strpos(uri_string(), 'guru/my-classes') !== false || strpos(uri_string(), 'guru/assessments') !== false) ? 'active' : '' ?>"
                                   href="#" id="guruMenuDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                   <i class="bi bi-chalkboard-teacher"></i> Menu Guru
                                </a>
                                <ul class="dropdown-menu" aria-labelledby="guruMenuDropdown">
                                    <li>
                                        <a class="dropdown-item <?= (strpos(uri_string(), 'guru/my-classes') !== false) ? 'active' : '' ?>"
                                           href="<?= site_url('guru/my-classes') ?>">Kelas & Siswa Saya</a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item <?= (strpos(uri_string(), 'guru/my-schedule') !== false) ? 'active' : '' ?>"
                                           href="<?= site_url('guru/my-schedule') ?>">Jadwal Mengajar Saya</a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item <?= (strpos(uri_string(), 'guru/attendance') !== false) ? 'active' : '' ?>"
                                           href="<?= site_url('guru/attendance/select-schedule') ?>">Input Presensi Harian</a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><h6 class="dropdown-header">Penilaian</h6></li>
                                    <li>
                                        <a class="dropdown-item <?= (uri_string() == 'guru/assessments' || uri_string() == 'guru/assessments/input') ? 'active' : '' ?>"
                                           href="<?= site_url('guru/assessments') ?>">Input Nilai</a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item <?= (strpos(uri_string(), 'guru/assessments/recap') !== false || strpos(uri_string(), 'guru/assessments/show-recap') !== false) ? 'active' : '' ?>"
                                           href="<?= route_to('guru_assessment_recap_select') ?>">Rekap Nilai</a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item <?= (strpos(uri_string(), 'guru/p5assessments') !== false) ? 'active' : '' ?>"
                                           href="<?= route_to('guru_p5assessment_select_project') ?>">Input Nilai P5</a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><h6 class="dropdown-header">Wali Kelas</h6></li>
                                    <li>
                                        <a class="dropdown-item <?= (strpos(uri_string(), 'wali-kelas/erapor/export') !== false) ? 'active' : '' ?>"
                                           href="<?= route_to('wali_kelas_erapor_form') ?>">Ekspor e-Rapor</a>
                                    </li>
                                </ul>
                            </li>
                        <?php endif; ?>

                        <!-- Siswa Menu -->
                        <?php if (hasRole('Siswa')): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle <?= (strpos(uri_string(), 'siswa/') !== false) ? 'active' : '' ?>"
                                   href="#" id="siswaMenuDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                   <i class="bi bi-person-student"></i> Menu Siswa
                                </a>
                                <ul class="dropdown-menu" aria-labelledby="siswaMenuDropdown">
                                    <li>
                                        <a class="dropdown-item <?= (strpos(uri_string(), 'siswa/my-schedule') !== false) ? 'active' : '' ?>"
                                           href="<?= site_url('siswa/my-schedule') ?>">Jadwal Kelas Saya</a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item <?= (strpos(uri_string(), 'siswa/nilai') !== false) ? 'active' : '' ?>"
                                           href="<?= route_to('siswa_nilai_index') ?>">Transkrip Nilai</a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item <?= (strpos(uri_string(), 'siswa/subject-choices') !== false) ? 'active' : '' ?>"
                                           href="<?= site_url('siswa/subject-choices') ?>">Pilih Mata Pelajaran</a>
                                    </li>
                                </ul>
                            </li>
                        <?php endif; ?>

                        <!-- Orang Tua Menu -->
                        <?php if (hasRole('Orang Tua')): ?>
                            <li class="nav-item">
                                <a class="nav-link <?= (strpos(uri_string(), 'ortu/nilai') !== false) ? 'active' : '' ?>"
                                   href="<?= route_to('ortu_nilai_index') ?>">Nilai Anak</a>
                            </li>
                        <?php endif; ?>


                        <!-- Rekapitulasi Menu -->
                        <?php if (hasRole(['Administrator Sistem', 'Staf Tata Usaha', 'Kepala Sekolah', 'Guru'])): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle <?= (strpos(uri_string(), 'admin/recaps') !== false) ? 'active' : '' ?>"
                                   href="#" id="rekapMenuDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                   <i class="bi bi-clipboard-data"></i> Rekapitulasi
                                </a>
                                <ul class="dropdown-menu" aria-labelledby="rekapMenuDropdown">
                                    <?php if (hasRole(['Administrator Sistem', 'Staf Tata Usaha', 'Kepala Sekolah', 'Guru'])): ?>
                                    <li>
                                        <a class="dropdown-item <?= (strpos(uri_string(), 'admin/recaps/attendance') !== false) ? 'active' : '' ?>"
                                           href="<?= route_to('admin_recap_attendance') ?>">Rekap Presensi</a>
                                    </li>
                                    <?php endif; ?>
                                    <?php if (hasRole(['Administrator Sistem', 'Staf Tata Usaha', 'Kepala Sekolah', 'Guru'])): // Peran yang bisa melihat rekap mapel pilihan ?>
                                    <li>
                                        <a class="dropdown-item <?= (strpos(uri_string(), 'admin/recaps/subject-choices') !== false) ? 'active' : '' ?>"
                                           href="<?= route_to('admin_recap_subject_choices'); ?>">Rekap Pilihan Mapel</a>
                                    </li>
                                    <?php endif; ?>
                                </ul>
                            </li>
                        <?php endif; ?>


                        <!-- Add other role-specific menus here later -->


                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <?php if ($session->get('is_logged_in')): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarUserDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-person-circle"></i> <?= esc($session->get('full_name') ?? $session->get('username')) ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarUserDropdown">
                                <!-- <li><a class="dropdown-item" href="#">Profile</a></li> -->
                                <!-- <li><hr class="dropdown-divider"></li> -->
                                <li><a class="dropdown-item" href="<?= site_url('logout') ?>"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link <?= (uri_string() == 'login') ? 'active' : '' ?>" href="<?= site_url('login') ?>">Login</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <main class="main-content container mt-4">
        <?= $this->renderSection('content') ?>
    </main>

    <footer class="footer mt-auto">
        <div class="container">
            <span class="text-muted">&copy; <?= date('Y') ?> SMAN 1 Campurdarat. SI-AKADEMIK.</span>
        </div>
    </footer>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <!-- Bootstrap JS Bundle (includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <!-- DataTables Buttons JS -->
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

    <?= $this->renderSection('scripts') ?>
</body>
</html>
