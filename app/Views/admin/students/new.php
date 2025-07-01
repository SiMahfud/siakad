<?= $this->extend('admin/layout/header') ?>

<?= $this->section('content') ?>

    <h1>Add New Student</h1>

    <?php if (isset($validation)) : ?>
        <div class="form-errors">
            <?php
                // Ensure validation errors are displayed if they exist
                if (is_object($validation) && method_exists($validation, 'listErrors')) {
                    echo $validation->listErrors();
                } elseif (is_string($validation) && !empty($validation)) { // Check if it's a non-empty string
                    // Fallback if $validation is just a string of errors (less likely with CI services this way)
                    // This was more for the case where $validation might be an error message string itself.
                    // For CI's validation object, listErrors() is the way.
                    // echo esc($validation); // If it were a simple string error message
                }
            ?>
        </div>
    <?php endif; ?>

    <form action="<?= site_url('admin/students/create') ?>" method="post">
        <?= csrf_field() ?>

        <div class="form-group">
            <label for="full_name">Full Name:</label>
            <input type="text" name="full_name" id="full_name" value="<?= old('full_name') ?>" required>
        </div>

        <div class="form-group">
            <label for="nisn">NISN:</label>
            <input type="text" name="nisn" id="nisn" value="<?= old('nisn') ?>">
        </div>

        <div class="form-group">
            <label for="user_id">User ID (for student login, optional):</label>
            <input type="number" name="user_id" id="user_id" value="<?= old('user_id') ?>">
            <small>Leave blank if no login yet. Must be an existing User ID from the 'users' table if provided.</small>
        </div>

        <div class="form-group">
            <label for="parent_user_id">Parent User ID (for parent login, optional):</label>
            <input type="number" name="parent_user_id" id="parent_user_id" value="<?= old('parent_user_id') ?>">
            <small>Leave blank if no parent login. Must be an existing User ID from the 'users' table if provided.</small>
        </div>

        <!-- Add other fields as necessary -->

        <div class="button-group">
            <button type="submit">Save Student</button>
            <a href="<?= site_url('admin/students') ?>">Cancel</a>
        </div>
    </form>

<?= $this->endSection() ?>
