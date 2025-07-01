<?= $this->extend('admin/layout/header') ?>

<?= $this->section('content') ?>

    <h1>Add New Subject</h1>

    <?php if (isset($validation)) : ?>
        <div class="form-errors">
            <?php
                if (is_object($validation) && method_exists($validation, 'listErrors')) {
                    echo $validation->listErrors();
                }
            ?>
        </div>
    <?php endif; ?>

    <form action="<?= site_url('admin/subjects/create') ?>" method="post">
        <?= csrf_field() ?>

        <div class="form-group">
            <label for="subject_name">Subject Name:</label>
            <input type="text" name="subject_name" id="subject_name" value="<?= old('subject_name') ?>" required>
        </div>

        <div class="form-group">
            <label for="subject_code">Subject Code (Optional):</label>
            <input type="text" name="subject_code" id="subject_code" value="<?= old('subject_code') ?>">
        </div>

        <div class="form-group">
            <label for="is_pilihan">Subject Type:</label>
            <select name="is_pilihan" id="is_pilihan">
                <option value="0" <?= old('is_pilihan') == '0' ? 'selected' : '' ?>>Wajib (Core)</option>
                <option value="1" <?= old('is_pilihan') == '1' ? 'selected' : '' ?>>Pilihan (Elective)</option>
            </select>
        </div>

        <div class="button-group">
            <button type="submit">Save Subject</button>
            <a href="<?= site_url('admin/subjects') ?>">Cancel</a>
        </div>
    </form>

<?= $this->endSection() ?>
