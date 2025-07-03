<?= $this->extend('layouts/admin_default') // Menggunakan layout admin yang sama, atau bisa layout khusus KS ?>

<?= $this->section('content') ?>
<div class="container-fluid">

    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= esc($title) ?></h1>
        <!-- <a href="#" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm"><i
                class="fas fa-download fa-sm text-white-50"></i> Generate Report</a> -->
    </div>
    <p class="mb-4">Selamat datang di dasbor Kepala Sekolah untuk <?= esc($school_name) ?>.</p>

    <!-- Content Row: Summary Cards -->
    <div class="row">

        <!-- Total Siswa Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Siswa Aktif</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= esc($summary['total_students'] ?? 0) ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Guru Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Total Guru Aktif</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= esc($summary['total_teachers'] ?? 0) ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chalkboard-teacher fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Kelas Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Kelas
                            </div>
                            <div class="row no-gutters align-items-center">
                                <div class="col-auto">
                                    <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800"><?= esc($summary['total_classes'] ?? 0) ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-school fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Projek P5 Aktif Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Projek P5 Aktif</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= esc($summary['active_p5_projects'] ?? 0) ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-cubes fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Content Row: Attendance -->
    <div class="row">
        <div class="col-lg-6 mb-4">
            <!-- Monthly Attendance -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Rata-rata Kehadiran Siswa (Bulan Ini)</h6>
                </div>
                <div class="card-body">
                    <?php if (($summary['monthly_attendance_percentage'] ?? 0) > 0) : ?>
                        <h4 class="small font-weight-bold">Kehadiran <span
                                class="float-right"><?= esc($summary['monthly_attendance_percentage']) ?>%</span></h4>
                        <div class="progress mb-4">
                            <div class="progress-bar bg-success" role="progressbar" style="width: <?= esc($summary['monthly_attendance_percentage']) ?>%"
                                aria-valuenow="<?= esc($summary['monthly_attendance_percentage']) ?>" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <p class="small text-muted">Persentase ini dihitung berdasarkan status "Hadir" dari total catatan kehadiran di bulan <?= date('F Y') ?>.</p>
                    <?php else : ?>
                        <p class="text-center"><em>Data kehadiran untuk bulan ini belum cukup untuk ditampilkan atau tidak ada catatan kehadiran.</em></p>
                    <?php endif; ?>
                     <a href="<?= site_url('admin/recaps/attendance') // Asumsi KS punya akses ke rekap detail ?>">Lihat Rekap Presensi Detail &rarr;</a>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-4">
            <!-- Placeholder for other charts or summaries -->
             <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Ringkasan Lain (Contoh)</h6>
                </div>
                <div class="card-body">
                    <p>Area ini dapat digunakan untuk menampilkan ringkasan atau grafik lain yang relevan bagi Kepala Sekolah, seperti:</p>
                    <ul>
                        <li>Ringkasan pencapaian P5 tingkat sekolah.</li>
                        <li>Distribusi nilai rata-rata per mata pelajaran.</li>
                        <li>Informasi penting lainnya.</li>
                    </ul>
                    <p class="text-center mt-3">
                        <i class="fas fa-chart-line fa-3x text-gray-300"></i>
                    </p>
                </div>
            </div>
        </div>
    </div>

</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<!-- Page level plugins -->
<!-- <script src="vendor/chart.js/Chart.min.js"></script> -->

<!-- Page level custom scripts -->
<!-- <script src="js/demo/chart-area-demo.js"></script> -->
<!-- <script src="js/demo/chart-pie-demo.js"></script> -->
<?= $this->endSection() ?>
