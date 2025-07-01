<?= $this->extend('admin/layout/header') ?>

<?= $this->section('content') ?>

    <h1>Add New Teacher</h1>

    <?php if (isset($validation)) : ?>
        <div class="form-errors">
            <?php
                if (is_object($validation) && method_exists($validation, 'listErrors')) {
                    echo $validation->listErrors();
                }
            ?>
        </div>
    <?php endif; ?>

    <form action="<?= site_url('admin/teachers/create') ?>" method="post">
        <?= csrf_field() ?>

        <div class="form-group">
            <label for="full_name">Full Name:</label>
            <input type="text" name="full_name" id="full_name" value="<?= old('full_name') ?>" required>
        </div>

        <div class="form-group">
            <label for="nip">NIP:</label>
            <input type="text" name="nip" id="nip" value="<?= old('nip') ?>">
        </div>

        <div class="form-group">
            <label for="user_id">User ID (for teacher login, optional):</label>
            <input type="number" name="user_id" id="user_id" value="<?= old('user_id') ?>">
            <small>Leave blank if no login yet. Must be an existing User ID from the 'users' table if provided.</small>
        </div>

        <!-- Add other fields as necessary -->

        <div class="button-group">
            <button type="submit">Save Teacher</button>
            <a href="<?= site_url('admin/teachers') ?>">Cancel</a>
        </div>
    </form>

<?= $this->endSection() ?>
