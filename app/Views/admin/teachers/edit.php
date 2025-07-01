<?= $this->extend('layouts/admin_default') ?>

<?= $this->section('content') ?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= esc($title ?? 'Edit Teacher') ?></h1>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Teacher Details: <?= esc($teacher['full_name'] ?? ($teacher['nip'] ?? 'N/A')) ?></h6>
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

            <form action="<?= site_url('admin/teachers/update/' . $teacher['id']) ?>" method="post">
                <?= csrf_field() ?>

                <div class="mb-3">
                    <label for="full_name" class="form-label">Full Name: <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?= (isset($validation) && $validation->hasError('full_name')) ? 'is-invalid' : '' ?>"
                           name="full_name" id="full_name" value="<?= old('full_name', $teacher['full_name'] ?? '') ?>" required>
                </div>

                <div class="mb-3">
                    <label for="nip" class="form-label">NIP:</label>
                    <input type="text" class="form-control <?= (isset($validation) && $validation->hasError('nip')) ? 'is-invalid' : '' ?>"
                           name="nip" id="nip" value="<?= old('nip', $teacher['nip'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label for="user_id" class="form-label">User ID (for teacher login, optional):</label>
                    <input type="number" class="form-control <?= (isset($validation) && $validation->hasError('user_id')) ? 'is-invalid' : '' ?>"
                           name="user_id" id="user_id" value="<?= old('user_id', $teacher['user_id'] ?? '') ?>">
                    <div class="form-text">Leave blank if no login yet. Must be an existing User ID from the 'users' table if provided.</div>
                </div>

                <!-- Add other teacher-specific fields as necessary -->

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">Update Teacher</button>
                    <a href="<?= site_url('admin/teachers') ?>" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
