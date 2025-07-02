<?= $this->extend('layouts/admin_default') ?>

<?= $this->section('content') ?>
<div class="container-fluid">

    <h1 class="h3 mb-2 text-gray-800"><?= esc($pageTitle ?? 'Select Schedule for Attendance') ?></h1>
    <p class="mb-4">Select the date and then choose a schedule slot to input attendance.</p>

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
    <?php if (session()->getFlashdata('info')) : ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <?= session()->getFlashdata('info') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Select Date and Schedule</h6>
        </div>
        <div class="card-body">
            <form method="get" action="<?= site_url('guru/attendance/select-schedule') ?>" class="mb-4">
                <div class="row align-items-end">
                    <div class="col-md-4 mb-2">
                        <label for="date_filter" class="form-label">Select Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control form-control-sm" id="date_filter" name="date" value="<?= esc($selectedDate ?? date('Y-m-d')) ?>" required>
                    </div>
                    <?php if (isAdmin() && !$loggedInTeacherId) : // For admin to select a specific teacher's schedule ?>
                        <div class="col-md-4 mb-2">
                            <label for="teacher_id_for_admin_filter" class="form-label">Teacher ID (for Admin)</label>
                            <input type="number" class="form-control form-control-sm" id="teacher_id_for_admin_filter" name="teacher_id_for_admin"
                                   value="<?= esc($this->request->getGet('teacher_id_for_admin') ?? '') ?>" placeholder="Enter Teacher ID">
                        </div>
                    <?php endif; ?>
                    <div class="col-md-2 mb-2">
                        <button type="submit" class="btn btn-info btn-sm">View Schedules</button>
                    </div>
                </div>
            </form>
            <hr />

            <?php if (!empty($schedules)) : ?>
                <h5 class="mt-3">Available Schedules for <?= esc($dayMap[date('N', strtotime($selectedDate))] ?? '') ?>, <?= esc(date('d M Y', strtotime($selectedDate))) ?>:</h5>
                <div class="table-responsive mt-3">
                    <table class="table table-bordered table-hover" id="dataTableSchedules" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Class</th>
                                <th>Subject</th>
                                <th>Academic Year</th>
                                <th>Semester</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($schedules as $schedule) : ?>
                                <tr>
                                    <td><?= esc(date('H:i', strtotime($schedule['start_time']))) ?> - <?= esc(date('H:i', strtotime($schedule['end_time']))) ?></td>
                                    <td><?= esc($schedule['class_name']) ?></td>
                                    <td><?= esc($schedule['subject_name']) ?></td>
                                    <td><?= esc($schedule['academic_year']) ?></td>
                                    <td><?= $schedule['semester'] == 1 ? 'Ganjil' : 'Genap' ?></td>
                                    <td>
                                        <a href="<?= site_url('guru/attendance/form?schedule_id=' . $schedule['id'] . '&date=' . $selectedDate) ?>" class="btn btn-primary btn-sm">
                                            Input Attendance
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else : ?>
                <p class="mt-3">No schedules found for you on <?= esc(date('d M Y', strtotime($selectedDate))) ?> (<?= esc($dayMap[date('N', strtotime($selectedDate))] ?? '') ?>).</p>
                <?php if (isAdmin() && !$loggedInTeacherId && $this->request->getGet('teacher_id_for_admin')): ?>
                     <p>Or the specified teacher ID has no schedule on this date.</p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    $(document).ready(function() {
        $('#dataTableSchedules').DataTable({
            "order": [[0, "asc"]], // Sort by time
            "pageLength": 10
        });
    });
</script>
<?= $this->endSection() ?>
