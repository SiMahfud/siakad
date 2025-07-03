<?= $this->extend('layouts/admin_default') ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= $title ?></h1>
        <a href="<?= site_url('admin/p5subelements/new') ?>" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm"><i class="fas fa-plus fa-sm text-white-50"></i> Add New P5 Sub-element</a>
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
            <h6 class="m-0 font-weight-bold text-primary">P5 Sub-element List</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Sub-element Name</th>
                            <th>Parent Element</th>
                            <th>Description</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($sub_elements)) : ?>
                            <?php foreach ($sub_elements as $sub_element) : ?>
                                <tr>
                                    <td><?= esc($sub_element['id']) ?></td>
                                    <td><?= esc($sub_element['name']) ?></td>
                                    <td><?= esc($sub_element['element_name']) ?></td>
                                    <td><?= esc($sub_element['description']) ?></td>
                                    <td><?= esc($sub_element['created_at']) ?></td>
                                    <td>
                                        <a href="<?= site_url('admin/p5subelements/edit/' . $sub_element['id']) ?>" class="btn btn-sm btn-warning">Edit</a>
                                        <button type="button" class="btn btn-sm btn-danger" onclick="confirmDelete('<?= site_url('admin/p5subelements/delete/' . $sub_element['id']) ?>')">Delete</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="6" class="text-center">No P5 Sub-elements found.</td>
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
    if (confirm("Are you sure you want to delete this P5 Sub-element? This may affect P5 Projects targeting it or related Assessments.")) {
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
