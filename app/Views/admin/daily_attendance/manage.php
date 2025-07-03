<?= $this->extend('layouts/admin_default') ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800"><?= esc($title) ?></h1>

    <?php if (session()->getFlashdata('message')) : ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= session()->getFlashdata('message') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')) : ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= session()->getFlashdata('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Filter Form -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Pilih Kelas dan Tanggal</h6>
        </div>
        <div class="card-body">
            <form method="get" action="<?= site_url('admin/daily-attendance') ?>" id="filterForm">
                <div class="row">
                    <div class="col-md-5 mb-3">
                        <label for="class_id" class="form-label">Kelas</label>
                        <select name="class_id" id="class_id" class="form-select" required>
                            <option value="">-- Pilih Kelas --</option>
                            <?php foreach ($classes as $class) : ?>
                                <option value="<?= $class['id'] ?>" <?= ($selected_class_id == $class['id']) ? 'selected' : '' ?>>
                                    <?= esc($class['class_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-5 mb-3">
                        <label for="date" class="form-label">Tanggal</label>
                        <input type="date" class="form-control" id="date" name="date" value="<?= esc($selected_date) ?>" required>
                    </div>
                    <div class="col-md-2 mb-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-info w-100">Tampilkan Siswa</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php if ($selected_class_id && $selected_date && !empty($students)) : ?>
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                Input Absensi Kelas: <?= esc($classes[array_search($selected_class_id, array_column($classes, 'id'))]['class_name'] ?? '') ?>
                - Tanggal: <?= esc(date('d M Y', strtotime($selected_date))) ?>
            </h6>
        </div>
        <div class="card-body">
            <form action="<?= site_url('admin/daily-attendance/save') ?>" method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="class_id" value="<?= esc($selected_class_id) ?>">
                <input type="hidden" name="attendance_date" value="<?= esc($selected_date) ?>">

                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="dailyAttendanceTable" width="100%" cellspacing="0">
                        <thead class="thead-light">
                            <tr>
                                <th style="width: 5%;">No</th>
                                <th style="width: 15%;">NIS</th>
                                <th>Nama Siswa</th>
                                <th style="width: 15%;">Status</th>
                                <th style="width: 30%;">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; ?>
                            <?php foreach ($students as $student) : ?>
                                <?php
                                $currentAttendance = $existing_attendance[$student['id']] ?? null;
                                $currentStatus = $currentAttendance['status'] ?? '';
                                $currentRemarks = $currentAttendance['remarks'] ?? '';
                                ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><?= esc($student['nis'] ?? 'N/A') ?></td>
                                    <td><?= esc($student['full_name']) ?></td>
                                    <td>
                                        <select name="attendance[<?= $student['id'] ?>][status]" class="form-select form-select-sm">
                                            <option value="">-- Belum Diabsen --</option>
                                            <?php foreach ($status_map as $code => $name) : ?>
                                                <option value="<?= $code ?>" <?= ($currentStatus == $code) ? 'selected' : '' ?>>
                                                    <?= esc($name) ?> (<?= esc(strtoupper(substr($name,0,1))) ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" name="attendance[<?= $student['id'] ?>][remarks]" class="form-control form-control-sm" value="<?= esc($currentRemarks) ?>" placeholder="Opsional">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Simpan Absensi Harian
                    </button>
                    <a href="<?= site_url('admin/daily-attendance') ?>" class="btn btn-secondary">Batal/Pilih Kelas Lain</a>
                </div>
            </form>
        </div>
    </div>
    <?php elseif ($selected_class_id && $selected_date) : ?>
        <div class="alert alert-warning">Tidak ada siswa yang ditemukan untuk kelas yang dipilih atau kelas belum memiliki siswa.</div>
    <?php else : ?>
        <div class="alert alert-info">Silakan pilih kelas dan tanggal untuk memulai input absensi harian.</div>
    <?php endif; ?>

</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(document).ready(function() {
    // Optional: DataTable for student list if very long, though usually not needed for single class view.
    // $('#dailyAttendanceTable').DataTable();

    // Auto-submit form on class or date change can be added if desired,
    // but manual "Tampilkan Siswa" button is also fine.
    // $('#class_id, #date').change(function() {
    //    $('#filterForm').submit();
    // });
});
</script>
<?= $this->endSection() ?>
