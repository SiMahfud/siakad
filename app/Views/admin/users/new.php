<?= $this->extend('layouts/admin_default') ?>

<?= $this->section('content') ?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= esc($title ?? 'Add New User') ?></h1>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">User Details</h6>
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

            <form action="<?= site_url('admin/users/create') ?>" method="post">
                <?= csrf_field() ?>

                <div class="mb-3">
                    <label for="full_name" class="form-label">Full Name:</label>
                    <input type="text" class="form-control <?= (isset($validation) && $validation->hasError('full_name')) ? 'is-invalid' : '' ?>"
                           name="full_name" id="full_name" value="<?= old('full_name') ?>">
                </div>

                <div class="mb-3">
                    <label for="username" class="form-label">Username: <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?= (isset($validation) && $validation->hasError('username')) ? 'is-invalid' : '' ?>"
                           name="username" id="username" value="<?= old('username') ?>" required>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password: <span class="text-danger">*</span></label>
                    <input type="password" class="form-control <?= (isset($validation) && $validation->hasError('password')) ? 'is-invalid' : '' ?>"
                           name="password" id="password" required>
                </div>

                <!-- <div class="mb-3">
                    <label for="password_confirm" class="form-label">Confirm Password: <span class="text-danger">*</span></label>
                    <input type="password" class="form-control <?= (isset($validation) && $validation->hasError('password_confirm')) ? 'is-invalid' : '' ?>"
                           name="password_confirm" id="password_confirm" required>
                </div> -->

                <div class="mb-3">
                    <label for="role_id" class="form-label">Role: <span class="text-danger">*</span></label>
                    <select name="role_id" id="role_id" class="form-select <?= (isset($validation) && $validation->hasError('role_id')) ? 'is-invalid' : '' ?>" required>
                        <option value="">-- Select Role --</option>
                        <?php if (!empty($roles) && is_array($roles)) : ?>
                            <?php foreach ($roles as $role) : ?>
                                <option value="<?= esc($role['id']) ?>" <?= old('role_id') == $role['id'] ? 'selected' : '' ?>>
                                    <?= esc($role['role_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Status: <span class="text-danger">*</span></label>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="is_active" id="is_active_true" value="1" <?= old('is_active', '1') == '1' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_active_true">Active</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="is_active" id="is_active_false" value="0" <?= old('is_active') == '0' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_active_false">Inactive</label>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">Save User</button>
                    <a href="<?= site_url('admin/users') ?>" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
