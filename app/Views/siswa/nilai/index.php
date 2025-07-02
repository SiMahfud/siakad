<?= $this->extend('layouts/admin_default') // Or a specific student layout if created ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= esc($pageTitle ?? 'Transkrip Nilai Sementara') ?></h1>
    </div>

    <?php if (session()->getFlashdata('info')) : ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <?= session()->getFlashdata('info') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')) : ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= session()->getFlashdata('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if ($student && $currentClass) : ?>
        <div class="mb-3">
            <p><strong>Nama Siswa:</strong> <?= esc($student['full_name']) ?></p>
            <p><strong>NISN:</strong> <?= esc($student['nisn'] ?? 'N/A') ?></p>
            <p><strong>Kelas:</strong> <?= esc($currentClass['class_name']) ?> (T.A. <?= esc($currentClass['academic_year']) ?>)</p>
        </div>
        <hr>

        <?php if (!empty($mapelDenganNilai)) : ?>
            <?php foreach ($mapelDenganNilai as $item) : ?>
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="bi bi-book-fill"></i> <?= esc($item['subject_info']['subject_name']) ?>
                            (<?= esc($item['subject_info']['subject_code']) ?>)
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($item['assessments'])) : ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped table-sm" width="100%" cellspacing="0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Tanggal</th>
                                            <th>Judul Asesmen</th>
                                            <th>Tipe</th>
                                            <th>Skor</th>
                                            <th>Deskripsi/Catatan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($item['assessments'] as $assessment) : ?>
                                            <tr>
                                                <td><?= esc(date('d M Y', strtotime($assessment['assessment_date']))) ?></td>
                                                <td><?= esc($assessment['assessment_title']) ?></td>
                                                <td>
                                                    <span class="badge bg-<?= $assessment['assessment_type'] === 'SUMATIF' ? 'success' : 'info' ?>">
                                                        <?= esc(ucfirst(strtolower($assessment['assessment_type']))) ?>
                                                    </span>
                                                </td>
                                                <td><?= $assessment['assessment_type'] === 'SUMATIF' ? esc($assessment['score'] ?? '-') : '-' ?></td>
                                                <td><?= nl2br(esc($assessment['description'] ?? '-')) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else : ?>
                            <p class="text-muted">Belum ada data penilaian untuk mata pelajaran ini.</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else : ?>
            <div class="alert alert-info">
                Belum ada mata pelajaran yang terdaftar atau belum ada penilaian untuk Anda di kelas ini.
            </div>
        <?php endif; ?>

    <?php elseif ($student && !$currentClass) : ?>
         <!-- Handled by flashdata 'info' from controller if student not in class -->
         <p>Silakan hubungi administrator sekolah jika Anda seharusnya sudah terdaftar di sebuah kelas.</p>
    <?php endif; ?>

</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<style>
    .card-header h6 {
        display: flex;
        align-items: center;
    }
    .card-header .bi {
        margin-right: 0.5rem;
    }
    .table-sm td, .table-sm th {
        padding: 0.4rem;
        vertical-align: middle;
    }
</style>
<?= $this->endSection() ?>
