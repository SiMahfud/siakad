<?= $this->extend('layouts/admin_default') ?>

<?= $this->section('content') ?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= esc($title ?? 'Add New Class (Rombel)') ?></h1>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Class Details</h6>
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

            <form action="<?= site_url('admin/classes/create') ?>" method="post">
                <?= csrf_field() ?>

                <div class="mb-3">
                    <label for="class_name" class="form-label">Class Name (e.g., X-A, XI IPA 1): <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?= (isset($validation) && $validation->hasError('class_name')) ? 'is-invalid' : '' ?>"
                           name="class_name" id="class_name" value="<?= old('class_name') ?>" required>
                </div>

                <div class="mb-3">
                    <label for="academic_year" class="form-label">Academic Year (e.g., 2024/2025): <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?= (isset($validation) && $validation->hasError('academic_year')) ? 'is-invalid' : '' ?>"
                           name="academic_year" id="academic_year" value="<?= old('academic_year') ?>" required placeholder="YYYY/YYYY">
                </div>

                <div class="mb-3">
                    <label for="fase" class="form-label">Fase (e.g., E, F) (Optional):</label>
                    <input type="text" class="form-control <?= (isset($validation) && $validation->hasError('fase')) ? 'is-invalid' : '' ?>"
                           name="fase" id="fase" value="<?= old('fase') ?>" maxlength="1">
                </div>

                <div class="mb-3">
                    <label for="wali_kelas_id" class="form-label">Wali Kelas (Homeroom Teacher) (Optional):</label>
                    <select name="wali_kelas_id" id="wali_kelas_id" class="form-select <?= (isset($validation) && $validation->hasError('wali_kelas_id')) ? 'is-invalid' : '' ?>">
                        <option value="">-- Select Wali Kelas --</option>
                        <?php if (!empty($teachers) && is_array($teachers)) : ?>
                            <?php foreach ($teachers as $teacher) : ?>
                                <option value="<?= esc($teacher['id']) ?>" <?= old('wali_kelas_id') == $teacher['id'] ? 'selected' : '' ?>>
                                    <?= esc($teacher['full_name']) ?> (NIP: <?= esc($teacher['nip'] ?? 'N/A') ?>)
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">Save Class</button>
                    <a href="<?= site_url('admin/classes') ?>" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
