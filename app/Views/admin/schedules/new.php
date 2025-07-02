<?= $this->extend('layouts/admin_default') ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800"><?= esc($pageTitle ?? 'Add New Schedule') ?></h1>

    <?php if (session()->getFlashdata('error')) : ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= session()->getFlashdata('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($validation)) : ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <h4 class="alert-heading">Validation Errors!</h4>
            <hr>
            <?= $validation->listErrors() ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>


    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Schedule Details</h6>
        </div>
        <div class="card-body">
            <form action="<?= site_url('admin/schedules/create') ?>" method="post">
                <?= csrf_field() ?>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="academic_year" class="form-label">Academic Year <span class="text-danger">*</span></label>
                            <input type="text" class="form-control <?= (isset($validation) && $validation->hasError('academic_year')) ? 'is-invalid' : '' ?>"
                                   id="academic_year" name="academic_year" value="<?= old('academic_year', '') ?>" placeholder="e.g., 2023/2024" required>
                            <?php if (isset($validation) && $validation->hasError('academic_year')) : ?>
                                <div class="invalid-feedback"><?= $validation->getError('academic_year') ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="semester" class="form-label">Semester <span class="text-danger">*</span></label>
                            <select class="form-select <?= (isset($validation) && $validation->hasError('semester')) ? 'is-invalid' : '' ?>"
                                    id="semester" name="semester" required>
                                <option value="">Select Semester</option>
                                <?php foreach ($semesters as $key => $value) : ?>
                                    <option value="<?= $key ?>" <?= old('semester') == $key ? 'selected' : '' ?>><?= esc($value) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($validation) && $validation->hasError('semester')) : ?>
                                <div class="invalid-feedback"><?= $validation->getError('semester') ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="class_id" class="form-label">Class <span class="text-danger">*</span></label>
                            <select class="form-select <?= (isset($validation) && $validation->hasError('class_id')) ? 'is-invalid' : '' ?>"
                                    id="class_id" name="class_id" required>
                                <option value="">Select Class</option>
                                <?php foreach ($classes as $class) : ?>
                                    <option value="<?= $class['id'] ?>" <?= old('class_id') == $class['id'] ? 'selected' : '' ?>>
                                        <?= esc($class['class_name']) ?> (<?= esc($class['academic_year']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($validation) && $validation->hasError('class_id')) : ?>
                                <div class="invalid-feedback"><?= $validation->getError('class_id') ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                     <div class="col-md-6">
                        <div class="mb-3">
                            <label for="day_of_week" class="form-label">Day of Week <span class="text-danger">*</span></label>
                            <select class="form-select <?= (isset($validation) && $validation->hasError('day_of_week')) ? 'is-invalid' : '' ?>"
                                    id="day_of_week" name="day_of_week" required>
                                <option value="">Select Day</option>
                                <?php foreach ($days as $key => $value) : ?>
                                    <option value="<?= $key ?>" <?= old('day_of_week') == $key ? 'selected' : '' ?>><?= esc($value) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($validation) && $validation->hasError('day_of_week')) : ?>
                                <div class="invalid-feedback"><?= $validation->getError('day_of_week') ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="start_time" class="form-label">Start Time <span class="text-danger">*</span></label>
                            <input type="time" class="form-control <?= (isset($validation) && $validation->hasError('start_time')) ? 'is-invalid' : '' ?>"
                                   id="start_time" name="start_time" value="<?= old('start_time', '') ?>" required>
                            <?php if (isset($validation) && $validation->hasError('start_time')) : ?>
                                <div class="invalid-feedback"><?= $validation->getError('start_time') ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="end_time" class="form-label">End Time <span class="text-danger">*</span></label>
                            <input type="time" class="form-control <?= (isset($validation) && $validation->hasError('end_time')) ? 'is-invalid' : '' ?>"
                                   id="end_time" name="end_time" value="<?= old('end_time', '') ?>" required>
                            <?php if (isset($validation) && $validation->hasError('end_time')) : ?>
                                <div class="invalid-feedback"><?= $validation->getError('end_time') ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="row">
                     <div class="col-md-6">
                        <div class="mb-3">
                            <label for="subject_id" class="form-label">Subject <span class="text-danger">*</span></label>
                            <select class="form-select <?= (isset($validation) && $validation->hasError('subject_id')) ? 'is-invalid' : '' ?>"
                                    id="subject_id" name="subject_id" required>
                                <option value="">Select Subject</option>
                                <?php foreach ($subjects as $subject) : ?>
                                    <option value="<?= $subject['id'] ?>" <?= old('subject_id') == $subject['id'] ? 'selected' : '' ?>>
                                        <?= esc($subject['subject_name']) ?> <?= esc($subject['subject_code'] ? '(' . $subject['subject_code'] . ')' : '') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($validation) && $validation->hasError('subject_id')) : ?>
                                <div class="invalid-feedback"><?= $validation->getError('subject_id') ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="teacher_id" class="form-label">Teacher <span class="text-danger">*</span></label>
                            <select class="form-select <?= (isset($validation) && $validation->hasError('teacher_id')) ? 'is-invalid' : '' ?>"
                                    id="teacher_id" name="teacher_id" required>
                                <option value="">Select Teacher</option>
                                <?php foreach ($teachers as $teacher) : ?>
                                    <option value="<?= $teacher['id'] ?>" <?= old('teacher_id') == $teacher['id'] ? 'selected' : '' ?>>
                                        <?= esc($teacher['full_name']) ?> <?= esc($teacher['nip'] ? '(' . $teacher['nip'] . ')' : '') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($validation) && $validation->hasError('teacher_id')) : ?>
                                <div class="invalid-feedback"><?= $validation->getError('teacher_id') ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="notes" class="form-label">Notes</label>
                    <textarea class="form-control <?= (isset($validation) && $validation->hasError('notes')) ? 'is-invalid' : '' ?>"
                              id="notes" name="notes" rows="3"><?= old('notes', '') ?></textarea>
                    <?php if (isset($validation) && $validation->hasError('notes')) : ?>
                        <div class="invalid-feedback"><?= $validation->getError('notes') ?></div>
                    <?php endif; ?>
                </div>

                <hr>
                <button type="submit" class="btn btn-primary">Save Schedule</button>
                <a href="<?= site_url('admin/schedules') ?>" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<!-- Add any specific JS for this page if needed, e.g., Select2 for dropdowns -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        $('#class_id').select2({ theme: "bootstrap-5" });
        $('#subject_id').select2({ theme: "bootstrap-5" });
        $('#teacher_id').select2({ theme: "bootstrap-5" });
        $('#day_of_week').select2({ theme: "bootstrap-5", minimumResultsForSearch: Infinity }); // No search for days
        $('#semester').select2({ theme: "bootstrap-5", minimumResultsForSearch: Infinity }); // No search for semester
    });
</script>
<?= $this->endSection() ?>
