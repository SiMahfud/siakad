<?= $this->extend('layouts/admin_default') ?>

<?= $this->section('content') ?>
<div class="container-fluid">

    <h1 class="h3 mb-2 text-gray-800"><?= esc($pageTitle ?? 'My Teaching Schedule') ?></h1>
    <p class="mb-4">View your teaching schedule. Use filters to select academic year and semester.</p>

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
            <h6 class="m-0 font-weight-bold text-primary">My Schedule Details</h6>
        </div>
        <div class="card-body">
            <!-- Filter Form -->
            <form method="get" action="<?= site_url('guru/my-schedule') ?>" class="mb-4">
                <div class="row">
                    <div class="col-md-4 mb-2">
                        <label for="academic_year_filter" class="form-label">Academic Year</label>
                        <input type="text" class="form-control form-control-sm" id="academic_year_filter" name="academic_year" value="<?= esc($filters['academic_year'] ?? '') ?>" placeholder="e.g., 2023/2024">
                    </div>
                    <div class="col-md-3 mb-2">
                        <label for="semester_filter" class="form-label">Semester</label>
                        <select class="form-select form-select-sm" id="semester_filter" name="semester">
                            <option value="">All</option>
                            <option value="1" <?= ($filters['semester'] ?? '') == '1' ? 'selected' : '' ?>>Ganjil</option>
                            <option value="2" <?= ($filters['semester'] ?? '') == '2' ? 'selected' : '' ?>>Genap</option>
                        </select>
                    </div>
                    <?php if (isAdmin() && !isset($loggedInTeacherId)) : // Only show teacher selector for admin viewing other teachers ?>
                        <div class="col-md-3 mb-2">
                            <label for="teacher_id_for_admin_filter" class="form-label">View Schedule For (Teacher ID)</label>
                            <input type="number" class="form-control form-control-sm" id="teacher_id_for_admin_filter" name="teacher_id_for_admin" value="<?= esc($filters['teacher_id'] ?? '') ?>" placeholder="Teacher ID">
                        </div>
                    <?php endif; ?>
                    <div class="col-md-2 mb-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-info btn-sm">Filter</button>
                        <a href="<?= site_url('guru/my-schedule') ?>" class="btn btn-outline-secondary btn-sm ms-2">Reset</a>
                    </div>
                </div>
            </form>
            <hr/>

            <?php
            $dayMap = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'];
            $groupedSchedules = [];
            if (!empty($schedules)) {
                foreach ($schedules as $schedule) {
                    $groupedSchedules[$schedule['day_of_week']][] = $schedule;
                }
                ksort($groupedSchedules); // Sort by day index
            }
            ?>

            <?php if (!empty($groupedSchedules)) : ?>
                <?php foreach ($groupedSchedules as $dayNum => $daySchedules) : ?>
                    <h5 class="mt-4 mb-3"><?= esc($dayMap[$dayNum] ?? 'Unknown Day') ?></h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Class</th>
                                    <th>Subject</th>
                                    <th>Academic Year</th>
                                    <th>Semester</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($daySchedules as $schedule) : ?>
                                    <tr>
                                        <td><?= esc(date('H:i', strtotime($schedule['start_time']))) ?> - <?= esc(date('H:i', strtotime($schedule['end_time']))) ?></td>
                                        <td><?= esc($schedule['class_name']) ?></td>
                                        <td><?= esc($schedule['subject_name']) ?></td>
                                        <td><?= esc($schedule['academic_year']) ?></td>
                                        <td><?= $schedule['semester'] == 1 ? 'Ganjil' : 'Genap' ?></td>
                                        <td><?= esc($schedule['notes']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endforeach; ?>
            <?php else : ?>
                <p>No teaching schedule found for the selected criteria.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<!-- Add any specific JS for this page if needed -->
<script>
    // Optional: any JS for this page
</script>
<?= $this->endSection() ?>
