<?= $this->extend('layouts/admin_default') ?>

<?= $this->section('styles') ?>
<style>
    .select-students {
        height: 250px; /* Adjust as needed */
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= $title ?></h1>
        <a href="<?= site_url('admin/p5projects/edit/' . $project['id']) ?>" class="btn btn-sm btn-outline-secondary shadow-sm"><i class="fas fa-edit fa-sm"></i> Edit Project Details</a>
    </div>
    <a href="<?= site_url('admin/p5projects') ?>" class="btn btn-sm btn-secondary shadow-sm mb-3"><i class="fas fa-arrow-left fa-sm"></i> Back to Project List</a>

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
    <?php if ($validation && $validation->getErrors()) : ?>
        <div class="alert alert-danger">
            <strong>Please correct the following errors:</strong>
            <ul>
                <?php foreach ($validation->getErrors() as $error) : ?>
                    <li><?= esc($error) ?></li>
                <?php endforeach ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Assigned Students Card -->
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Assigned Students (<?= count($assigned_students) ?>)</h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($assigned_students)) : ?>
                        <ul class="list-group">
                            <?php foreach ($assigned_students as $student) : ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <?= esc($student['full_name']) ?> (<?= esc($student['nis']) ?>)
                                    <button type="button" class="btn btn-sm btn-danger" onclick="confirmRemoveStudent('<?= site_url('admin/p5projects/remove-student/' . $project['id'] . '/' . $student['p5_project_student_id']) ?>')">
                                        <i class="fas fa-trash"></i> Remove
                                    </button>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else : ?>
                        <p class="text-center">No students currently assigned to this project.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Add Students Card -->
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Add Students to Project</h6>
                </div>
                <div class="card-body">
                    <?= form_open('admin/p5projects/add-student/' . $project['id']) ?>
                        <div class="form-group">
                            <label for="students_to_add">Available Students</label>
                            <small class="text-muted d-block mb-2">Select one or more students to add. (Hold Ctrl/Cmd to select multiple)</small>
                            <select multiple class="form-control select-students <?= ($validation && $validation->hasError('students_to_add')) ? 'is-invalid' : '' ?>" id="students_to_add" name="students_to_add[]">
                                <?php if (!empty($available_students)) : ?>
                                    <?php foreach ($available_students as $student) : ?>
                                        <option value="<?= esc($student['id']) ?>">
                                            <?= esc($student['full_name']) ?> (<?= esc($student['nis']) ?>)
                                            <?php // You could add class info here if available in $student array ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <option value="" disabled>No more students available to add or all students are already assigned.</option>
                                <?php endif; ?>
                            </select>
                            <?php if ($validation && $validation->hasError('students_to_add')) : ?>
                                <div class="invalid-feedback d-block">
                                    <?= $validation->getError('students_to_add') ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Add Selected Students</button>
                    <?= form_close() ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function confirmRemoveStudent(removeUrl) {
    if (confirm("Are you sure you want to remove this student from the project?")) {
        window.location.href = removeUrl;
    }
}
</script>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<!-- Optional: Select2/Choices.js for better multi-select UX -->
<!-- <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" /> -->
<!-- <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script> -->
<!-- <script>
// $(document).ready(function() {
//     $('#students_to_add').select2({
//         placeholder: "Select students to add",
//         allowClear: true
//     });
// });
// </script> -->
<?= $this->endSection() ?>
