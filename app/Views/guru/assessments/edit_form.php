<?= $this->extend('layouts/admin_default') ?>

<?= $this->section('content') ?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= esc($pageTitle ?? 'Edit Assessment') ?></h1>
        <a href="<?= site_url('guru/assessments/input?class_id=' . esc($assessment['class_id']) . '&subject_id=' . esc($assessment['subject_id'])) ?>" class="btn btn-sm btn-secondary shadow-sm">
            <i class="bi bi-arrow-left"></i> Back to Input Form
        </a>
    </div>

    <?php if (session()->getFlashdata('error')) : ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= session()->getFlashdata('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php $validation_errors = session()->getFlashdata('validation_errors'); ?>
    <?php if (!empty($validation_errors)) : ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <h5 class="alert-heading">Please correct the errors below:</h5>
            <ul>
                <?php foreach ($validation_errors as $field => $error) : ?>
                    <li><?= esc($error) ?></li>
                <?php endforeach ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>


    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                Edit Assessment for <?= esc($student['full_name'] ?? 'Student') ?> -
                <?= esc($classInfo['class_name'] ?? 'Class') ?> (<?= esc($subjectInfo['subject_name'] ?? 'Subject') ?>)
            </h6>
        </div>
        <div class="card-body">
            <form action="<?= site_url('guru/assessments/update/' . esc($assessment['id'])) ?>" method="post">
                <?= csrf_field() ?>

                <input type="hidden" name="student_id" value="<?= esc($assessment['student_id']) ?>">
                <input type="hidden" name="class_id" value="<?= esc($assessment['class_id']) ?>">
                <input type="hidden" name="subject_id" value="<?= esc($assessment['subject_id']) ?>">

                <div class="row mb-3">
                    <label for="assessment_type" class="col-sm-3 col-form-label">Assessment Type <span class="text-danger">*</span></label>
                    <div class="col-sm-9">
                        <select name="assessment_type" id="assessment_type" class="form-select <?= isset($validation_errors['assessment_type']) ? 'is-invalid' : '' ?>" required>
                            <option value="FORMATIF" <?= old('assessment_type', $assessment['assessment_type']) === 'FORMATIF' ? 'selected' : '' ?>>FORMATIF</option>
                            <option value="SUMATIF" <?= old('assessment_type', $assessment['assessment_type']) === 'SUMATIF' ? 'selected' : '' ?>>SUMATIF</option>
                        </select>
                        <?php if (isset($validation_errors['assessment_type'])) : ?>
                            <div class="invalid-feedback"><?= esc($validation_errors['assessment_type']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="row mb-3">
                    <label for="assessment_date" class="col-sm-3 col-form-label">Assessment Date <span class="text-danger">*</span></label>
                    <div class="col-sm-9">
                        <input type="date" name="assessment_date" id="assessment_date" class="form-control <?= isset($validation_errors['assessment_date']) ? 'is-invalid' : '' ?>" value="<?= old('assessment_date', $assessment['assessment_date']) ?>" required>
                        <?php if (isset($validation_errors['assessment_date'])) : ?>
                            <div class="invalid-feedback"><?= esc($validation_errors['assessment_date']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="row mb-3">
                    <label for="assessment_title" class="col-sm-3 col-form-label">Assessment Title</label>
                    <div class="col-sm-9">
                        <input type="text" name="assessment_title" id="assessment_title" class="form-control <?= isset($validation_errors['assessment_title']) ? 'is-invalid' : '' ?>" value="<?= old('assessment_title', $assessment['assessment_title']) ?>">
                         <small class="form-text text-muted">Required if score or description is provided.</small>
                        <?php if (isset($validation_errors['assessment_title'])) : ?>
                            <div class="invalid-feedback"><?= esc($validation_errors['assessment_title']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="row mb-3" id="score_field" style="<?= old('assessment_type', $assessment['assessment_type']) === 'SUMATIF' ? '' : 'display:none;' ?>">
                    <label for="score" class="col-sm-3 col-form-label">Score</label>
                    <div class="col-sm-9">
                        <input type="number" step="0.01" name="score" id="score" class="form-control <?= isset($validation_errors['score']) ? 'is-invalid' : '' ?>" value="<?= old('score', $assessment['score']) ?>">
                        <small class="form-text text-muted">Required for Summative (0-100).</small>
                        <?php if (isset($validation_errors['score'])) : ?>
                            <div class="invalid-feedback"><?= esc($validation_errors['score']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="row mb-3">
                    <label for="description" class="col-sm-3 col-form-label">Description/Notes</label>
                    <div class="col-sm-9">
                        <textarea name="description" id="description" rows="3" class="form-control <?= isset($validation_errors['description']) ? 'is-invalid' : '' ?>"><?= old('description', $assessment['description']) ?></textarea>
                        <?php if (isset($validation_errors['description'])) : ?>
                            <div class="invalid-feedback"><?= esc($validation_errors['description']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Update Assessment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const assessmentTypeSelect = document.getElementById('assessment_type');
    const scoreField = document.getElementById('score_field');
    const scoreInput = document.getElementById('score');

    function toggleScoreField() {
        if (assessmentTypeSelect.value === 'SUMATIF') {
            scoreField.style.display = '';
        } else {
            scoreField.style.display = 'none';
            // scoreInput.value = ''; // Clear score if not summative - commented out to preserve value if user toggles back and forth
        }
    }

    assessmentTypeSelect.addEventListener('change', toggleScoreField);
    // Initial check
    toggleScoreField();
});
</script>

<?= $this->endSection() ?>
