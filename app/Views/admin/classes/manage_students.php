<?= $this->extend('layouts/admin_default') ?>

<?= $this->section('content') ?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= esc($title) ?></h1>
        <a href="<?= site_url('admin/classes') ?>" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Back to Class List
        </a>
    </div>

    <p><strong>Kelas:</strong> <?= esc($class_item['class_name']) ?> (<?= esc($class_item['academic_year']) ?>)</p>

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
    <?php if (isset($validation) && $validation->getErrors()) : ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                <?php foreach ($validation->getErrors() as $error) : ?>
                    <li><?= esc($error) ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Section to Add Students -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Add Student to Class</h6>
        </div>
        <div class="card-body">
            <form action="<?= site_url('admin/classes/add-student/' . $class_item['id']) ?>" method="post">
                <?= csrf_field() ?>
                <div class="row">
                    <div class="col-md-8">
                        <select name="student_id" class="form-select" required>
                            <option value="">-- Select Student to Add --</option>
                            <?php if (!empty($available_students)) : ?>
                                <?php foreach ($available_students as $student) : ?>
                                    <option value="<?= esc($student['id']) ?>">
                                        <?= esc($student['full_name']) ?> (NISN: <?= esc($student['nisn'] ?? 'N/A') ?>)
                                    </option>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <option value="" disabled>No available students to add.</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-plus-circle-fill"></i> Add to Class
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Section to Display Students in Class -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Students Currently in Class</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="dataTableStudentsInClass" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>NISN</th>
                            <th>Full Name</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($students_in_class)) : ?>
                            <?php $i = 1; ?>
                            <?php foreach ($students_in_class as $student_in_class) : ?>
                                <tr>
                                    <td><?= $i++ ?></td>
                                    <td><?= esc($student_in_class['nisn'] ?? 'N/A') ?></td>
                                    <td><?= esc($student_in_class['full_name']) ?></td>
                                    <td>
                                        <a href="<?= site_url('admin/classes/remove-student/' . $class_item['id'] . '/' . $student_in_class['id']) ?>"
                                           class="btn btn-danger btn-sm"
                                           title="Remove from Class"
                                           onclick="return confirm('Are you sure you want to remove this student from the class?');">
                                            <i class="bi bi-person-dash-fill"></i> Remove
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="4" class="text-center">No students currently in this class.</td>
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
<!-- Initialize DataTables for students in class -->
<script>
    $(document).ready(function() {
        $('#dataTableStudentsInClass').DataTable({
            "order": [], // Disable initial sorting
            "pageLength": 10, // Default number of rows to display
             // You can add more DataTables options here if needed
        });
    });
</script>
<?= $this->endSection() ?>
