<?= $this->extend('layouts/admin_default') ?>

<?= $this->section('content') ?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= esc($title ?? 'Add New Student') ?></h1>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Student Details</h6>
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

            <form action="<?= site_url('admin/students/create') ?>" method="post">
                <?= csrf_field() ?>

                <div class="mb-3">
                    <label for="full_name" class="form-label">Full Name: <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?= (isset($validation) && $validation->hasError('full_name')) ? 'is-invalid' : '' ?>"
                           name="full_name" id="full_name" value="<?= old('full_name') ?>" required>
                </div>

                <div class="mb-3">
                    <label for="nisn" class="form-label">NISN:</label>
                    <input type="text" class="form-control <?= (isset($validation) && $validation->hasError('nisn')) ? 'is-invalid' : '' ?>"
                           name="nisn" id="nisn" value="<?= old('nisn') ?>">
                </div>

                <div class="mb-3">
                    <label for="user_id" class="form-label">User ID (for student login, optional):</label>
                    <input type="number" class="form-control <?= (isset($validation) && $validation->hasError('user_id')) ? 'is-invalid' : '' ?>"
                           name="user_id" id="user_id" value="<?= old('user_id') ?>">
                    <div class="form-text">Leave blank if no login yet. Must be an existing User ID from the 'users' table if provided.</div>
                </div>

                <div class="mb-3">
                    <label for="parent_user_id" class="form-label">Parent User ID (for parent login, optional):</label>
                    <input type="number" class="form-control <?= (isset($validation) && $validation->hasError('parent_user_id')) ? 'is-invalid' : '' ?>"
                           name="parent_user_id" id="parent_user_id" value="<?= old('parent_user_id') ?>">
                    <div class="form-text">Leave blank if no parent login. Must be an existing User ID from the 'users' table if provided.</div>
                </div>

                <!-- Add other student-specific fields as necessary using Bootstrap classes -->
                <!-- Example:
                <div class="mb-3">
                    <label for="address" class="form-label">Address:</label>
                    <textarea class="form-control" name="address" id="address"><?= old('address') ?></textarea>
                </div>
                -->

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">Save Student</button>
                    <a href="<?= site_url('admin/students') ?>" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
