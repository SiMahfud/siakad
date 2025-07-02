<?= $this->extend('layouts/admin_default') ?>

<?= $this->section('content') ?>
<div class="container-fluid">

    <!-- Page Heading -->
    <h1 class="h3 mb-2 text-gray-800"><?= esc($pageTitle ?? 'View Students') ?></h1>
    <?php if (isset($classInfo)) : ?>
        <p class="mb-4">
            Displaying students for class: <strong><?= esc($classInfo['class_name']) ?></strong>
            (Academic Year: <?= esc($classInfo['academic_year']) ?>).
        </p>
    <?php endif; ?>


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

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Student List</h6>
        </div>
        <div class="card-body">
            <div class="pb-3">
                 <a href="<?= site_url('guru/my-classes') ?>" class="btn btn-sm btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to My Classes
                </a>
            </div>
            <?php if (!empty($students)) : ?>
                <div class="table-responsive">
                    <table class="table table-bordered" id="dataTableStudents" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>NISN</th>
                                <th>Full Name</th>
                                <!-- Add more student details if needed -->
                            </tr>
                        </thead>
                        <tbody>
                            <?php $i = 1; ?>
                            <?php foreach ($students as $student) : ?>
                                <tr>
                                    <td><?= $i++ ?></td>
                                    <td><?= esc($student['nisn'] ?? 'N/A') ?></td>
                                    <td><?= esc($student['full_name']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else : ?>
                <p>No students found in this class.</p>
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
        $('#dataTableStudents').DataTable();
    });
</script>
<?= $this->endSection() ?>
