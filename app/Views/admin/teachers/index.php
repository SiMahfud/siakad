<?= $this->extend('layouts/admin_default') ?>

<?= $this->section('content') ?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= esc($title ?? 'Manage Teachers') ?></h1>
        <a href="<?= site_url('admin/teachers/new') ?>" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg"></i> Add New Teacher
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
            <h6 class="m-0 font-weight-bold text-primary">Teacher List</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="dataTableTeachers" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>NIP</th>
                            <th>Full Name</th>
                            <th>User ID (Login)</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($teachers) && is_array($teachers)) : ?>
                            <?php foreach ($teachers as $teacher) : ?>
                                <tr>
                                    <td><?= esc($teacher['id']) ?></td>
                                    <td><?= esc($teacher['nip']) ?></td>
                                    <td><?= esc($teacher['full_name']) ?></td>
                                    <td><?= esc($teacher['user_id']) ?></td>
                                    <td>
                                        <a href="<?= site_url('admin/teachers/edit/' . $teacher['id']) ?>" class="btn btn-warning btn-sm" title="Edit">
                                            <i class="bi bi-pencil-fill"></i>
                                        </a>
                                        <a href="<?= site_url('admin/teachers/delete/' . $teacher['id']) ?>" class="btn btn-danger btn-sm" title="Delete" onclick="return confirm('Are you sure you want to delete this teacher? This action cannot be undone.');">
                                            <i class="bi bi-trash-fill"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="5" class="text-center">No teachers found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
