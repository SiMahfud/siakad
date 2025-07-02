<?= $this->extend('layouts/admin_default') ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800"><?= esc($pageTitle ?? 'Add New Subject Offering') ?></h1>

    <?php if (session()->getFlashdata('error')) : ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= session()->getFlashdata('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($validation) && $validation->getErrors()) : ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <h4 class="alert-heading">Validation Errors!</h4>
            <hr>
            <?= $validation->listErrors() ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Offering Details</h6>
        </div>
        <div class="card-body">
            <form action="<?= site_url('admin/subject-offerings/create') ?>" method="post">
                <?= csrf_field() ?>

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
                            <label for="academic_year" class="form-label">Academic Year <span class="text-danger">*</span></label>
                            <input type="text" class="form-control <?= (isset($validation) && $validation->hasError('academic_year')) ? 'is-invalid' : '' ?>"
                                   id="academic_year" name="academic_year" value="<?= old('academic_year', '') ?>" placeholder="e.g., 2023/2024" required>
                            <?php if (isset($validation) && $validation->hasError('academic_year')) : ?>
                                <div class="invalid-feedback"><?= $validation->getError('academic_year') ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="row">
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
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="max_quota" class="form-label">Max Quota</label>
                            <input type="number" class="form-control <?= (isset($validation) && $validation->hasError('max_quota')) ? 'is-invalid' : '' ?>"
                                   id="max_quota" name="max_quota" value="<?= old('max_quota', '') ?>" placeholder="Leave empty for no limit" min="0">
                            <?php if (isset($validation) && $validation->hasError('max_quota')) : ?>
                                <div class="invalid-feedback"><?= $validation->getError('max_quota') ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control <?= (isset($validation) && $validation->hasError('description')) ? 'is-invalid' : '' ?>"
                              id="description" name="description" rows="3"><?= old('description', '') ?></textarea>
                    <?php if (isset($validation) && $validation->hasError('description')) : ?>
                        <div class="invalid-feedback"><?= $validation->getError('description') ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" <?= old('is_active', true) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="is_active">Active (can be chosen by students)</label>
                </div>

                <hr>
                <button type="submit" class="btn btn-primary">Save Offering</button>
                <a href="<?= site_url('admin/subject-offerings') ?>" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        $('#subject_id').select2({ theme: "bootstrap-5", dropdownParent: $('#subject_id').parent() });
        $('#semester').select2({ theme: "bootstrap-5", minimumResultsForSearch: Infinity, dropdownParent: $('#semester').parent() });
    });
</script>
<?= $this->endSection() ?>
