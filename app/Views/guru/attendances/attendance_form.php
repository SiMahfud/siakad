<?= $this->extend('layouts/admin_default') ?>

<?= $this->section('content') ?>
<div class="container-fluid">

    <h1 class="h3 mb-2 text-gray-800"><?= esc($pageTitle ?? 'Input Attendance') ?></h1>
    <?php if (isset($schedule) && isset($attendanceDate)) : ?>
        <p class="mb-1">
            <strong>Class:</strong> <?= esc($schedule['class_name']) ?> |
            <strong>Subject:</strong> <?= esc($schedule['subject_name']) ?> |
            <strong>Teacher:</strong> <?= esc($schedule['teacher_name']) ?>
        </p>
        <p class="mb-3">
            <strong>Date:</strong> <?= esc(date('D, d M Y', strtotime($attendanceDate))) ?> |
            <strong>Time:</strong> <?= esc(date('H:i', strtotime($schedule['start_time']))) ?> - <?= esc(date('H:i', strtotime($schedule['end_time']))) ?>
        </p>
    <?php endif; ?>

    <?php if (session()->getFlashdata('success')) : ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= session()->getFlashdata('success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')) : ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= session()->getFlashdata('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if (isset($validation)) : ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <h4 class="alert-heading">Validation Errors!</h4>
            <hr>
            <?= $validation->listErrors() ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Student List & Attendance Status</h6>
            <a href="<?= site_url('guru/attendance/select-schedule?date=' . $attendanceDate) ?>" class="btn btn-sm btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Schedule Selection
            </a>
        </div>
        <div class="card-body">
            <?php if (!empty($students)) : ?>
                <form action="<?= site_url('guru/attendance/save') ?>" method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="schedule_id" value="<?= esc($schedule['id'] ?? '') ?>">
                    <input type="hidden" name="attendance_date" value="<?= esc($attendanceDate ?? '') ?>">

                    <div class="table-responsive">
                        <table class="table table-bordered" id="attendanceTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th style="width: 5%;">No</th>
                                    <th style="width: 15%;">NISN</th>
                                    <th style="width: 30%;">Full Name</th>
                                    <th style="width: 25%;">Status <span class="text-danger">*</span></th>
                                    <th style="width: 25%;">Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $i = 1; ?>
                                <?php foreach ($students as $student) : ?>
                                    <?php
                                    $currentStatus = $attendanceData[$student['id']]['status'] ?? $defaultStatus;
                                    $currentRemarks = $attendanceData[$student['id']]['remarks'] ?? '';
                                    ?>
                                    <tr>
                                        <td><?= $i++ ?></td>
                                        <td><?= esc($student['nisn'] ?? 'N/A') ?></td>
                                        <td><?= esc($student['full_name']) ?></td>
                                        <td>
                                            <input type="hidden" name="attendance[<?= $student['id'] ?>][student_id]" value="<?= $student['id'] ?>">
                                            <select name="attendance[<?= $student['id'] ?>][status]" class="form-select form-select-sm" required>
                                                <?php foreach ($statusOptions as $key => $value) : ?>
                                                    <option value="<?= $key ?>" <?= ($currentStatus == $key) ? 'selected' : '' ?>>
                                                        <?= esc($value) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="text" name="attendance[<?= $student['id'] ?>][remarks]" class="form-control form-control-sm" value="<?= esc($currentRemarks) ?>" placeholder="Optional remarks">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <hr>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Attendance
                    </button>
                </form>
            <?php else : ?>
                <p>No students found in this class (<?= esc($schedule['class_name'] ?? 'N/A') ?>).</p>
            <?php endif; ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<!-- Font Awesome for icons (if not already included globally) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<script>
    $(document).ready(function() {
        // No DataTable needed for this form usually, as all students should be visible for input.
        // If class lists are very long, pagination could be considered, but might complicate batch submission.
    });
</script>
<?= $this->endSection() ?>
