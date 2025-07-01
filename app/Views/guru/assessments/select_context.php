<?= $this->extend('layouts/admin_default') // Menggunakan layout admin default untuk konsistensi ?>

<?= $this->section('content') ?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= esc($pageTitle ?? 'Select Context for Assessment') ?></h1>
    </div>

    <?php if (session()->getFlashdata('error')) : ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= session()->getFlashdata('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Choose Class and Subject</h6>
        </div>
        <div class="card-body">
            <form action="<?= site_url('guru/assessments/input') ?>" method="get">
                <?php // csrf_field() removed for GET form ?>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="class_id" class="form-label">Class: <span class="text-danger">*</span></label>
                        <select name="class_id" id="class_id" class="form-select" required>
                            <option value="">-- Select Class --</option>
                            <?php if (!empty($classes) && is_array($classes)) : ?>
                                <?php foreach ($classes as $class_item) : ?>
                                    <option value="<?= esc($class_item['id']) ?>" <?= (isset($selected_class_id) && $selected_class_id == $class_item['id']) ? 'selected' : '' ?>>
                                        <?= esc($class_item['class_name']) ?> (<?= esc($class_item['academic_year']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="subject_id" class="form-label">Subject: <span class="text-danger">*</span></label>
                        <select name="subject_id" id="subject_id" class="form-select" required>
                            <option value="">-- Select Subject --</option>
                            <?php if (!empty($subjects) && is_array($subjects)) : ?>
                                <?php foreach ($subjects as $subject_item) : ?>
                                    <option value="<?= esc($subject_item['id']) ?>" <?= (isset($selected_subject_id) && $selected_subject_id == $subject_item['id']) ? 'selected' : '' ?>>
                                        <?= esc($subject_item['subject_name']) ?> <?= $subject_item['is_pilihan'] ? '(Pilihan)' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>

                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-arrow-right-circle"></i> Proceed to Input Scores
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
