<?= $this->extend('layouts/admin_default') ?>

<?= $this->section('content') ?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= esc($title ?? 'Manage Subjects') ?></h1>
        <?php if (hasRole(['Administrator Sistem', 'Staf Tata Usaha'])) : ?>
            <a href="<?= site_url('admin/subjects/new') ?>" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg"></i> Add New Subject
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
            <h6 class="m-0 font-weight-bold text-primary">Subject List</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="dataTableSubjects" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Subject Code</th>
                            <th>Subject Name</th>
                            <th>Type</th>
                            <?php if (hasRole(['Administrator Sistem', 'Staf Tata Usaha'])) : ?>
                                <th>Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($subjects) && is_array($subjects)) : ?>
                            <?php foreach ($subjects as $subject) : ?>
                                <tr>
                                    <td><?= esc($subject['id']) ?></td>
                                    <td><?= esc($subject['subject_code']) ?></td>
                                    <td><?= esc($subject['subject_name']) ?></td>
                                    <td>
                                        <?php if ($subject['is_pilihan']) : ?>
                                            <span class="badge bg-info">Pilihan (Elective)</span>
                                        <?php else : ?>
                                            <span class="badge bg-secondary">Wajib (Core)</span>
                                        <?php endif; ?>
                                    </td>
                                    <?php if (hasRole(['Administrator Sistem', 'Staf Tata Usaha'])) : ?>
                                        <td>
                                            <a href="<?= site_url('admin/subjects/edit/' . $subject['id']) ?>" class="btn btn-warning btn-sm" title="Edit">
                                                <i class="bi bi-pencil-fill"></i>
                                            </a>
                                            <a href="<?= site_url('admin/subjects/delete/' . $subject['id']) ?>" class="btn btn-danger btn-sm" title="Delete" onclick="return confirm('Are you sure you want to delete this subject? This action cannot be undone.');">
                                                <i class="bi bi-trash-fill"></i>
                                            </a>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="<?= hasRole(['Administrator Sistem', 'Staf Tata Usaha']) ? 5 : 4 ?>" class="text-center">No subjects found.</td>
                                <td colspan="<?= hasRole(['Administrator Sistem', 'Staf Tata Usaha']) ? 5 : 4 ?>" class="text-center">No subjects found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
