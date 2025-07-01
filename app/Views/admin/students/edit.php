<?= $this->extend('admin/layout/header') ?>

<?= $this->section('content') ?>

    <h1>Edit Student: <?= esc($student['full_name'] ?? 'N/A') ?></h1>

    <?php if (isset($validation)) : ?>
        <div class="form-errors">
            <?php
                if (is_object($validation) && method_exists($validation, 'listErrors')) {
                    echo $validation->listErrors();
                }
            ?>
        </div>
    <?php endif; ?>

    <form action="<?= site_url('admin/students/update/' . ($student['id'] ?? '')) ?>" method="post">
        <?= csrf_field() ?>

        <div class="form-group">
            <label for="full_name">Full Name:</label>
            <input type="text" name="full_name" id="full_name" value="<?= old('full_name', $student['full_name'] ?? '') ?>" required>
        </div>

        <div class="form-group">
            <label for="nisn">NISN:</label>
            <input type="text" name="nisn" id="nisn" value="<?= old('nisn', $student['nisn'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="user_id">User ID (for student login, optional):</label>
            <input type="number" name="user_id" id="user_id" value="<?= old('user_id', $student['user_id'] ?? '') ?>">
            <small>Leave blank if no login yet. Must be an existing User ID from the 'users' table if provided.</small>
        </div>

        <div class="form-group">
            <label for="parent_user_id">Parent User ID (for parent login, optional):</label>
            <input type="number" name="parent_user_id" id="parent_user_id" value="<?= old('parent_user_id', $student['parent_user_id'] ?? '') ?>">
            <small>Leave blank if no parent login. Must be an existing User ID from the 'users' table if provided.</small>
        </div>

        <!-- Add other fields as necessary -->

        <div class="button-group">
            <button type="submit">Update Student</button>
            <a href="<?= site_url('admin/students') ?>">Cancel</a>
        </div>
    </form>

<?= $this->endSection() ?>
