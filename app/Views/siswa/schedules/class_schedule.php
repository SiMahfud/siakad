<?= $this->extend('layouts/admin_default') // Or a specific student layout if created ?>

<?= $this->section('content') ?>
<div class="container-fluid">

    <h1 class="h3 mb-2 text-gray-800"><?= esc($pageTitle ?? 'My Class Schedule') ?></h1>
    <p class="mb-4">
        Viewing schedule for class: <strong><?= esc($className ?? 'N/A') ?></strong>.
        Use filters to select academic year and semester if needed.
    </p>

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
            <h6 class="m-0 font-weight-bold text-primary">Class Schedule Details</h6>
        </div>
        <div class="card-body">
            <!-- Filter Form -->
            <form method="get" action="<?= site_url('siswa/my-schedule') ?>" class="mb-4">
                <div class="row">
                    <div class="col-md-5 mb-2">
                        <label for="academic_year_filter" class="form-label">Academic Year</label>
                        <input type="text" class="form-control form-control-sm" id="academic_year_filter" name="academic_year" value="<?= esc($filters['academic_year'] ?? '') ?>" placeholder="e.g., 2023/2024">
                    </div>
                    <div class="col-md-4 mb-2">
                        <label for="semester_filter" class="form-label">Semester</label>
                        <select class="form-select form-select-sm" id="semester_filter" name="semester">
                            <option value="">All</option>
                            <option value="1" <?= ($filters['semester'] ?? '') == '1' ? 'selected' : '' ?>>Ganjil</option>
                            <option value="2" <?= ($filters['semester'] ?? '') == '2' ? 'selected' : '' ?>>Genap</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-info btn-sm">Filter</button>
                        <a href="<?= site_url('siswa/my-schedule') ?>" class="btn btn-outline-secondary btn-sm ms-2">Reset</a>
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
                                    <th>Subject</th>
                                    <th>Teacher</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($daySchedules as $schedule) : ?>
                                    <tr>
                                        <td><?= esc(date('H:i', strtotime($schedule['start_time']))) ?> - <?= esc(date('H:i', strtotime($schedule['end_time']))) ?></td>
                                        <td><?= esc($schedule['subject_name']) ?></td>
                                        <td><?= esc($schedule['teacher_name']) ?></td>
                                        <td><?= esc($schedule['notes']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endforeach; ?>
            <?php else : ?>
                <p>No class schedule found for the selected criteria in your class.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    // Optional: any JS for this page
</script>
<?= $this->endSection() ?>
