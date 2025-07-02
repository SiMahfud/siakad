<?= $this->extend('layouts/admin_default') ?>

<?= $this->section('content') ?>

<div class="container-fluid">

    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= esc($pageTitle ?? 'Add New Teacher Assignment') ?></h1>
        <a href="<?= route_to('admin_assignments.index') ?>" class="btn btn-secondary shadow-sm">
            <i class="bi bi-arrow-left"></i> Back to List
        </a>
    </div>

    <!-- Flash Messages -->
    <?php if (session()->getFlashdata('error')) : ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= session()->getFlashdata('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('errors')) : // For validation errors ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <h5 class="alert-heading">Please correct the following errors:</h5>
            <ul>
                <?php foreach (session()->getFlashdata('errors') as $error) : ?>
                    <li><?= esc($error) ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>


    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Assignment Details</h6>
        </div>
        <div class="card-body">
            <form action="<?= route_to('admin_assignments.create') ?>" method="post">
                <?= csrf_field() ?>

                <div class="mb-3 row">
                    <label for="teacher_id" class="col-sm-3 col-form-label">Teacher <span class="text-danger">*</span></label>
                    <div class="col-sm-9">
                        <select class="form-select <?= (isset($validation) && $validation->hasError('teacher_id')) ? 'is-invalid' : '' ?>"
                                id="teacher_id" name="teacher_id" required>
                            <option value="">-- Select Teacher --</option>
                            <?php foreach ($teachers as $teacher) : ?>
                                <option value="<?= esc($teacher['id']) ?>" <?= set_select('teacher_id', $teacher['id']) ?>>
                                    <?= esc($teacher['full_name']) ?> (NIP: <?= esc($teacher['nip'] ?? 'N/A') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($validation) && $validation->hasError('teacher_id')) : ?>
                            <div class="invalid-feedback"><?= $validation->getError('teacher_id') ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="mb-3 row">
                    <label for="class_id" class="col-sm-3 col-form-label">Class <span class="text-danger">*</span></label>
                    <div class="col-sm-9">
                        <select class="form-select <?= (isset($validation) && $validation->hasError('class_id')) ? 'is-invalid' : '' ?>"
                                id="class_id" name="class_id" required>
                            <option value="">-- Select Class --</option>
                            <?php foreach ($classes as $class_item) : ?>
                                <option value="<?= esc($class_item['id']) ?>" <?= set_select('class_id', $class_item['id']) ?>>
                                    <?= esc($class_item['class_name']) ?> (<?= esc($class_item['academic_year']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($validation) && $validation->hasError('class_id')) : ?>
                            <div class="invalid-feedback"><?= $validation->getError('class_id') ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="mb-3 row">
                    <label for="subject_id" class="col-sm-3 col-form-label">Subject <span class="text-danger">*</span></label>
                    <div class="col-sm-9">
                        <select class="form-select <?= (isset($validation) && $validation->hasError('subject_id')) ? 'is-invalid' : '' ?>"
                                id="subject_id" name="subject_id" required>
                            <option value="">-- Select Subject --</option>
                            <?php foreach ($subjects as $subject) : ?>
                                <option value="<?= esc($subject['id']) ?>" <?= set_select('subject_id', $subject['id']) ?>>
                                    <?= esc($subject['subject_name']) ?> <?= $subject['is_pilihan'] ? '(Pilihan)' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($validation) && $validation->hasError('subject_id')) : ?>
                            <div class="invalid-feedback"><?= $validation->getError('subject_id') ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-sm-9 offset-sm-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Save Assignment
                        </button>
                        <a href="<?= route_to('admin_assignments.index') ?>" class="btn btn-outline-secondary">
                            Cancel
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
