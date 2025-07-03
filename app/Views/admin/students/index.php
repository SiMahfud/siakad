<?= $this->extend('layouts/admin_default') ?>

<?= $this->section('content') ?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= esc($title ?? 'Manage Students') ?></h1>
        <a href="<?= site_url('admin/students/new') ?>" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg"></i> Add New Student
        </a>
    </div>

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
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Student List</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="dataTableStudents" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>NISN</th>
                            <th>Full Name</th>
                            <th>User ID (Login)</th>
                            <th>Parent User ID</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($students) && is_array($students)) : ?>
                            <?php foreach ($students as $student) : ?>
                                <tr>
                                    <td><?= esc($student['id']) ?></td>
                                    <td><?= esc($student['nisn']) ?></td>
                                    <td><?= esc($student['full_name']) ?></td>
                                    <td><?= esc($student['user_id']) ?></td>
                                    <td><?= esc($student['parent_user_id']) ?></td>
                                    <td>
                                        <a href="<?= site_url('admin/students/edit/' . $student['id']) ?>" class="btn btn-warning btn-sm my-1" title="Edit">
                                            <i class="bi bi-pencil-fill"></i>
                                        </a>
                                        <a href="<?= route_to('admin_student_p5_report', $student['id']) ?>" class="btn btn-info btn-sm my-1" title="Lihat Laporan P5">
                                            <i class="bi bi-bar-chart-steps"></i> P5
                                        </a>
                                        <a href="<?= site_url('admin/students/delete/' . $student['id']) ?>" class="btn btn-danger btn-sm my-1" title="Delete" onclick="return confirm('Are you sure you want to delete this student? This action cannot be undone.');">
                                            <i class="bi bi-trash-fill"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="6" class="text-center">No students found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
