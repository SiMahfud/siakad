<?= $this->extend('admin/layout/header') ?>

<?= $this->section('content') ?>

    <h1>Add New Class (Rombongan Belajar)</h1>

    <?php if (isset($validation)) : ?>
        <div class="form-errors">
            <?php
                if (is_object($validation) && method_exists($validation, 'listErrors')) {
                    echo $validation->listErrors();
                }
            ?>
        </div>
    <?php endif; ?>

    <form action="<?= site_url('admin/classes/create') ?>" method="post">
        <?= csrf_field() ?>

        <div class="form-group">
            <label for="class_name">Class Name (e.g., X-A, XI IPA 1):</label>
            <input type="text" name="class_name" id="class_name" value="<?= old('class_name') ?>" required>
        </div>

        <div class="form-group">
            <label for="academic_year">Academic Year (e.g., 2024/2025):</label>
            <input type="text" name="academic_year" id="academic_year" value="<?= old('academic_year') ?>" required>
        </div>

        <div class="form-group">
            <label for="fase">Fase (e.g., E, F) (Optional):</label>
            <input type="text" name="fase" id="fase" value="<?= old('fase') ?>" maxlength="1">
        </div>

        <div class="form-group">
            <label for="wali_kelas_id">Wali Kelas (Homeroom Teacher) (Optional):</label>
            <select name="wali_kelas_id" id="wali_kelas_id">
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

        <div class="button-group">
            <button type="submit">Save Class</button>
            <a href="<?= site_url('admin/classes') ?>">Cancel</a>
        </div>
    </form>

<?= $this->endSection() ?>
