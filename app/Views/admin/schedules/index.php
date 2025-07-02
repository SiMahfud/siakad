<?= $this->extend('layouts/admin_default') ?>

<?= $this->section('content') ?>
<div class="container-fluid">

    <h1 class="h3 mb-2 text-gray-800"><?= esc($pageTitle ?? 'Manage Schedules') ?></h1>
    <p class="mb-4">Manage class schedules. Use filters to narrow down the list.</p>

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

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Schedule List</h6>
            <a href="<?= site_url('admin/schedules/new') ?>" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Add New Schedule
            </a>
        </div>
        <div class="card-body">
            <!-- Filter Form -->
            <form method="get" action="<?= site_url('admin/schedules') ?>" class="mb-4">
                <div class="row">
                    <div class="col-md-3 mb-2">
                        <label for="academic_year_filter" class="form-label">Academic Year</label>
                        <input type="text" class="form-control form-control-sm" id="academic_year_filter" name="academic_year" value="<?= esc($filters['academic_year'] ?? '') ?>" placeholder="e.g., 2023/2024">
                    </div>
                    <div class="col-md-2 mb-2">
                        <label for="semester_filter" class="form-label">Semester</label>
                        <select class="form-select form-select-sm" id="semester_filter" name="semester">
                            <option value="">All</option>
                            <option value="1" <?= ($filters['semester'] ?? '') == '1' ? 'selected' : '' ?>>Ganjil</option>
                            <option value="2" <?= ($filters['semester'] ?? '') == '2' ? 'selected' : '' ?>>Genap</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-2">
                        <label for="class_id_filter" class="form-label">Class</label>
                        <select class="form-select form-select-sm" id="class_id_filter" name="class_id">
                            <option value="">All Classes</option>
                            <?php foreach ($classes as $class) : ?>
                                <option value="<?= $class['id'] ?>" <?= ($filters['class_id'] ?? '') == $class['id'] ? 'selected' : '' ?>>
                                    <?= esc($class['class_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <label for="day_of_week_filter" class="form-label">Day</label>
                        <select class="form-select form-select-sm" id="day_of_week_filter" name="day_of_week">
                            <option value="">All Days</option>
                            <option value="1" <?= ($filters['day_of_week'] ?? '') == '1' ? 'selected' : '' ?>>Monday</option>
                            <option value="2" <?= ($filters['day_of_week'] ?? '') == '2' ? 'selected' : '' ?>>Tuesday</option>
                            <option value="3" <?= ($filters['day_of_week'] ?? '') == '3' ? 'selected' : '' ?>>Wednesday</option>
                            <option value="4" <?= ($filters['day_of_week'] ?? '') == '4' ? 'selected' : '' ?>>Thursday</option>
                            <option value="5" <?= ($filters['day_of_week'] ?? '') == '5' ? 'selected' : '' ?>>Friday</option>
                            <option value="6" <?= ($filters['day_of_week'] ?? '') == '6' ? 'selected' : '' ?>>Saturday</option>
                            <option value="7" <?= ($filters['day_of_week'] ?? '') == '7' ? 'selected' : '' ?>>Sunday</option>
                        </select>
                    </div>
                    <div class="col-md-2 mb-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-info btn-sm">Filter</button>
                         <a href="<?= site_url('admin/schedules') ?>" class="btn btn-outline-secondary btn-sm ms-2">Reset</a>
                    </div>
                </div>
            </form>
            <hr/>

            <?php
            $dayMap = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'];
            ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="dataTableSchedules" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Academic Year</th>
                            <th>Semester</th>
                            <th>Day</th>
                            <th>Time</th>
                            <th>Class</th>
                            <th>Subject</th>
                            <th>Teacher</th>
                            <th>Notes</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($schedules)) : ?>
                            <?php $i = 1; ?>
                            <?php foreach ($schedules as $schedule) : ?>
                                <tr>
                                    <td><?= $i++ ?></td>
                                    <td><?= esc($schedule['academic_year']) ?></td>
                                    <td><?= $schedule['semester'] == 1 ? 'Ganjil' : 'Genap' ?></td>
                                    <td><?= esc($dayMap[$schedule['day_of_week']] ?? 'N/A') ?></td>
                                    <td><?= esc(date('H:i', strtotime($schedule['start_time']))) ?> - <?= esc(date('H:i', strtotime($schedule['end_time']))) ?></td>
                                    <td><?= esc($schedule['class_name']) ?></td>
                                    <td><?= esc($schedule['subject_name']) ?></td>
                                    <td><?= esc($schedule['teacher_name']) ?></td>
                                    <td><?= esc($schedule['notes']) ?></td>
                                    <td>
                                        <a href="<?= site_url('admin/schedules/edit/' . $schedule['id']) ?>" class="btn btn-warning btn-sm mb-1" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="<?= site_url('admin/schedules/delete/' . $schedule['id']) ?>" method="post" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this schedule?');">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-danger btn-sm mb-1" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="10" class="text-center">No schedules found matching your criteria.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<!-- Font Awesome for icons (if not already included globally) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<!-- DataTables -->
<script>
    $(document).ready(function() {
        $('#dataTableSchedules').DataTable({
            "order": [], // Disable initial sorting
            // Add other configurations as needed
        });
    });
</script>
<?= $this->endSection() ?>
