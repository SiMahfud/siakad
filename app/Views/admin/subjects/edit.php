<?= $this->extend('layouts/admin_default') ?>

<?= $this->section('content') ?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= esc($title ?? 'Edit Subject') ?></h1>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Subject Details: <?= esc($subject['subject_name'] ?? ($subject['subject_code'] ?? 'N/A')) ?></h6>
        </div>
        <div class="card-body">
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
            <?php if (session()->getFlashdata('error')) : ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= session()->getFlashdata('error') ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form action="<?= site_url('admin/subjects/update/' . $subject['id']) ?>" method="post">
                <?= csrf_field() ?>

                <div class="mb-3">
                    <label for="subject_name" class="form-label">Subject Name: <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?= (isset($validation) && $validation->hasError('subject_name')) ? 'is-invalid' : '' ?>"
                           name="subject_name" id="subject_name" value="<?= old('subject_name', $subject['subject_name'] ?? '') ?>" required>
                </div>

                <div class="mb-3">
                    <label for="subject_code" class="form-label">Subject Code (Optional):</label>
                    <input type="text" class="form-control <?= (isset($validation) && $validation->hasError('subject_code')) ? 'is-invalid' : '' ?>"
                           name="subject_code" id="subject_code" value="<?= old('subject_code', $subject['subject_code'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label for="is_pilihan" class="form-label">Subject Type: <span class="text-danger">*</span></label>
                    <select name="is_pilihan" id="is_pilihan" class="form-select <?= (isset($validation) && $validation->hasError('is_pilihan')) ? 'is-invalid' : '' ?>" required>
                        <option value="0" <?= old('is_pilihan', (isset($subject['is_pilihan']) && $subject['is_pilihan'] == 0) ? '0' : '') == '0' ? 'selected' : '' ?>>Wajib (Core)</option>
                        <option value="1" <?= old('is_pilihan', (isset($subject['is_pilihan']) && $subject['is_pilihan'] == 1) ? '1' : '') == '1' ? 'selected' : '' ?>>Pilihan (Elective)</option>
                    </select>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">Update Subject</button>
                    <a href="<?= site_url('admin/subjects') ?>" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
