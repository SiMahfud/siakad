<?= $this->extend('admin/layout/header') ?>

<?= $this->section('content') ?>

    <h1>Edit User: <?= esc($user['full_name'] ?: $user['username']) ?></h1>

    <?php if (isset($validation)) : ?>
        <div class="form-errors">
            <?php
                if (is_object($validation) && method_exists($validation, 'listErrors')) {
                    echo $validation->listErrors();
                }
            ?>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')) : // General error from controller ?>
        <div class="alert alert-danger">
            <?= session()->getFlashdata('error') ?>
        </div>
    <?php endif; ?>

    <form action="<?= site_url('admin/users/update/' . $user['id']) ?>" method="post">
        <?= csrf_field() ?>

        <div class="form-group">
            <label for="full_name">Full Name:</label>
            <input type="text" name="full_name" id="full_name" value="<?= old('full_name', $user['full_name'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="username">Username:</label>
            <input type="text" name="username" id="username" value="<?= old('username', $user['username'] ?? '') ?>" required>
        </div>

        <div class="form-group">
            <label for="password">Password (leave blank to keep current password):</label>
            <input type="password" name="password" id="password">
        </div>

        <!-- <div class="form-group">
            <label for="password_confirm">Confirm New Password:</label>
            <input type="password" name="password_confirm" id="password_confirm">
        </div> -->

        <div class="form-group">
            <label for="role_id">Role:</label>
            <select name="role_id" id="role_id" required>
                <option value="">-- Select Role --</option>
                <?php if (!empty($roles) && is_array($roles)) : ?>
                    <?php foreach ($roles as $role) : ?>
                        <option value="<?= esc($role['id']) ?>" <?= old('role_id', $user['role_id'] ?? '') == $role['id'] ? 'selected' : '' ?>>
                            <?= esc($role['role_name']) ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Status:</label>
            <div>
                <input type="radio" name="is_active" id="is_active_true" value="1" <?= old('is_active', $user['is_active'] ?? '1') == '1' ? 'checked' : '' ?>>
                <label for="is_active_true" style="display:inline; font-weight:normal;">Active</label>
            </div>
            <div>
                <input type="radio" name="is_active" id="is_active_false" value="0" <?= old('is_active', $user['is_active'] ?? '1') == '0' ? 'checked' : '' ?>>
                <label for="is_active_false" style="display:inline; font-weight:normal;">Inactive</label>
            </div>
        </div>

        <div class="button-group">
            <button type="submit">Update User</button>
            <a href="<?= site_url('admin/users') ?>">Cancel</a>
        </div>
    </form>

<?= $this->endSection() ?>
