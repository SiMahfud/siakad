<?= $this->extend('layouts/admin_default') ?>

<?= $this->section('content') ?>
<div class="container-fluid">

    <h1 class="h3 mb-2 text-gray-800"><?= esc($pageTitle ?? 'Manage Subject Offerings') ?></h1>
    <p class="mb-4">Manage elective subject offerings for students.</p>

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
            <h6 class="m-0 font-weight-bold text-primary">Subject Offering List</h6>
            <a href="<?= site_url('admin/subject-offerings/new') ?>" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Add New Offering
            </a>
        </div>
        <div class="card-body">
            <!-- Filter Form -->
            <form method="get" action="<?= site_url('admin/subject-offerings') ?>" class="mb-4">
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
                    <div class="col-md-3 mb-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-info btn-sm">Filter</button>
                         <a href="<?= site_url('admin/subject-offerings') ?>" class="btn btn-outline-secondary btn-sm ms-2">Reset</a>
                    </div>
                </div>
            </form>
            <hr/>

            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="dataTableOfferings" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Subject Name</th>
                            <th>Code</th>
                            <th>Academic Year</th>
                            <th>Semester</th>
                            <th>Max Quota</th>
                            <th>Enrolled</th>
                            <th>Status</th>
                            <th>Description</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($offerings)) : ?>
                            <?php $i = 1; ?>
                            <?php foreach ($offerings as $offering) : ?>
                                <tr>
                                    <td><?= $i++ ?></td>
                                    <td><?= esc($offering['subject_name']) ?></td>
                                    <td><?= esc($offering['subject_code']) ?></td>
                                    <td><?= esc($offering['academic_year']) ?></td>
                                    <td><?= $offering['semester'] == 1 ? 'Ganjil' : 'Genap' ?></td>
                                    <td><?= esc($offering['max_quota'] ?? 'N/A') ?></td>
                                    <td><?= esc($offering['current_enrollment']) ?></td>
                                    <td>
                                        <?php if ($offering['is_active']) : ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php else : ?>
                                            <span class="badge bg-danger">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= esc($offering['description']) ?></td>
                                    <td>
                                        <a href="<?= site_url('admin/subject-offerings/edit/' . $offering['id']) ?>" class="btn btn-warning btn-sm mb-1" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="<?= site_url('admin/subject-offerings/delete/' . $offering['id']) ?>" method="post" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this offering? This cannot be undone if students have already made choices based on it and those choices are not removed first.');">
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
                                <td colspan="10" class="text-center">No subject offerings found matching your criteria.</td>
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
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<script>
    $(document).ready(function() {
        $('#dataTableOfferings').DataTable({
             "order": [[2, "asc"], [3, "asc"]], // Sort by academic year, then semester
        });
    });
</script>
<?= $this->endSection() ?>
