<?= $this->extend('layouts/admin_default') ?>

<?= $this->section('content') ?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= esc($title ?? 'Manage Classes (Rombel)') ?></h1>
        <?php if (hasRole(['Administrator Sistem', 'Staf Tata Usaha'])) : ?>
            <a href="<?= site_url('admin/classes/new') ?>" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg"></i> Add New Class
            </a>
        <?php endif; ?>
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
            <h6 class="m-0 font-weight-bold text-primary">Class List</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="dataTableClasses" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Class Name</th>
                            <th>Academic Year</th>
                            <th>Fase</th>
                            <th>Wali Kelas</th>
                            <?php if (hasRole(['Administrator Sistem', 'Staf Tata Usaha'])) : ?>
                                <th>Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($classes) && is_array($classes)) : ?>
                            <?php foreach ($classes as $class_item) : ?>
                                <tr>
                                    <td><?= esc($class_item['id']) ?></td>
                                    <td><?= esc($class_item['class_name']) ?></td>
                                    <td><?= esc($class_item['academic_year']) ?></td>
                                    <td><?= esc($class_item['fase']) ?></td>
                                    <td><?= esc($class_item['wali_kelas_name'] ?? 'N/A') ?></td>
                                    <?php if (hasRole(['Administrator Sistem', 'Staf Tata Usaha'])) : ?>
                                        <td>
                                            <a href="<?= site_url('admin/classes/edit/' . $class_item['id']) ?>" class="btn btn-warning btn-sm" title="Edit">
                                                <i class="bi bi-pencil-fill"></i>
                                            </a>
                                            <a href="<?= site_url('admin/classes/delete/' . $class_item['id']) ?>" class="btn btn-danger btn-sm" title="Delete" onclick="return confirm('Are you sure you want to delete this class? This action cannot be undone and might affect related student records.');">
                                                <i class="bi bi-trash-fill"></i>
                                            </a>
                                            <!-- Add link to manage students in class later -->
                                            <!-- <a href="<?= site_url('admin/classes/students/' . $class_item['id']) ?>" class="btn btn-info btn-sm" title="Manage Students">
                                                <i class="bi bi-people-fill"></i>
                                            </a> -->
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="<?= hasRole(['Administrator Sistem', 'Staf Tata Usaha']) ? 6 : 5 ?>" class="text-center">No classes found.</td>
                                <td colspan="<?= hasRole(['Administrator Sistem', 'Staf Tata Usaha']) ? 6 : 5 ?>" class="text-center">No classes found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
