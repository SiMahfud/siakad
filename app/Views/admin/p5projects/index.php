<?= $this->extend('layouts/admin_default') ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= $title ?></h1>
        <a href="<?= site_url('admin/p5projects/new') ?>" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm"><i class="fas fa-plus fa-sm text-white-50"></i> Add New P5 Project</a>
    </div>

    <?php if (session()->getFlashdata('message')) : ?>
        <div class="alert alert-success" role="alert">
            <?= session()->getFlashdata('message') ?>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')) : ?>
        <div class="alert alert-danger" role="alert">
            <?= session()->getFlashdata('error') ?>
        </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">P5 Project List</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Project Name</th>
                            <th>Theme</th>
                            <th>Description</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Target Sub-elements</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($projects)) : ?>
                            <?php foreach ($projects as $project) : ?>
                                <tr>
                                    <td><?= esc($project['id']) ?></td>
                                    <td><?= esc($project['name']) ?></td>
                                    <td><?= esc($project['theme_name']) ?></td>
                                    <td><?= esc(word_limiter($project['description'] ?? '', 20)) ?></td>
                                    <td><?= esc($project['start_date']) ?></td>
                                    <td><?= esc($project['end_date']) ?></td>
                                    <td>
                                        <?php if (!empty($project['target_sub_elements_names'])) : ?>
                                            <ul>
                                                <?php foreach ($project['target_sub_elements_names'] as $targetName) : ?>
                                                    <li><?= esc($targetName) ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php else : ?>
                                            N/A
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="<?= site_url('admin/p5projects/edit/' . $project['id']) ?>" class="btn btn-sm btn-warning mb-1">Edit</a>
                                        <button type="button" class="btn btn-sm btn-danger mb-1" onclick="confirmDelete('<?= site_url('admin/p5projects/delete/' . $project['id']) ?>')">Delete</button>
                                        <!-- Add link to manage students later -->
                                        <!-- <a href="<?= site_url('admin/p5projects/manage-students/' . $project['id']) ?>" class="btn btn-sm btn-info">Manage Students</a> -->
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="8" class="text-center">No P5 Projects found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(deleteUrl) {
    if (confirm("Are you sure you want to delete this P5 Project and all its related data (target sub-elements, student assignments, assessments)? This action cannot be undone.")) {
        window.location.href = deleteUrl;
    }
}
</script>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<!-- Page level plugins -->
<script src="<?= base_url('vendor/datatables/jquery.dataTables.min.js') ?>"></script>
<script src="<?= base_url('vendor/datatables/dataTables.bootstrap4.min.js') ?>"></script>

<!-- Page level custom scripts -->
<script src="<?= base_url('js/demo/datatables-demo.js') ?>"></script>
<?= $this->endSection() ?>
