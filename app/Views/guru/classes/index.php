<?= $this->extend('layouts/admin_default') ?>

<?= $this->section('content') ?>
<div class="container-fluid">

    <!-- Page Heading -->
    <h1 class="h3 mb-4 text-gray-800"><?= esc($pageTitle ?? 'My Classes') ?></h1>

    <?php if (session()->getFlashdata('success')) : ?>
        <div class="alert alert-success">
            <?= session()->getFlashdata('success') ?>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')) : ?>
        <div class="alert alert-danger">
            <?= session()->getFlashdata('error') ?>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('info')) : ?>
        <div class="alert alert-info">
            <?= session()->getFlashdata('info') ?>
        </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Classes List</h6>
        </div>
        <div class="card-body">
            <?php if (!empty($classes)) : ?>
                <div class="table-responsive">
                    <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Class Name</th>
                                <th>Academic Year</th>
                                <th>Fase</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $i = 1; ?>
                            <?php foreach ($classes as $classItem) : ?>
                                <tr>
                                    <td><?= $i++ ?></td>
                                    <td><?= esc($classItem['class_name']) ?></td>
                                    <td><?= esc($classItem['academic_year']) ?></td>
                                    <td><?= esc($classItem['fase']) ?></td>
                                    <td>
                                        <a href="<?= site_url('guru/my-classes/view-students/' . $classItem['id']) ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-users"></i> View Students
                                        </a>
                                        <!-- Add other actions if needed in future, e.g., view subjects taught by this teacher in this class -->
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else : ?>
                <p>No classes found assigned to you or you are not designated as a teacher.</p>
                <!-- Info message for admin without teacher context is handled by flashdata from controller -->
            <?php endif; ?>
        </div>
    </div>

</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<!-- Include DataTables if you want pagination, search, etc. -->
<link href="https://cdn.datatables.net/1.10.22/css/dataTables.bootstrap4.min.css" rel="stylesheet">
<script src="https://cdn.datatables.net/1.10.22/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.10.22/js/dataTables.bootstrap4.min.js"></script>
<script>
    $(document).ready(function() {
        $('#dataTable').DataTable();
    });
</script>
<?= $this->endSection() ?>
