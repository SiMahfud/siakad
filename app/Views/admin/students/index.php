<?= $this->extend('admin/layout/header') ?>

<?= $this->section('content') ?>

    <h1>Manage Students</h1>

    <a href="<?= site_url('admin/students/new') ?>" class="add-button">Add New Student</a>

    <?php if (session()->getFlashdata('success')) : ?>
        <div class="alert alert-success">
            <?= session()->getFlashdata('success') ?>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')) : ?>
        <div class="alert alert-danger">
            <?= session()->getFlashdata('error') ?>
        </div>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>NISN</th>
                <th>Full Name</th>
                <th>User ID (Login)</th>
                <th>Parent User ID</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($students) && is_array($students)) : ?>
                <?php foreach ($students as $student) : ?>
                    <tr>
                        <td><?= esc($student['id']) ?></td>
                        <td><?= esc($student['nisn']) ?></td>
                        <td><?= esc($student['full_name']) ?></td>
                        <td><?= esc($student['user_id']) ?></td>
                        <td><?= esc($student['parent_user_id']) ?></td>
                        <td class="action-links">
                            <a href="<?= site_url('admin/students/edit/' . $student['id']) ?>">Edit</a>
                            <a href="<?= site_url('admin/students/delete/' . $student['id']) ?>" onclick="return confirm('Are you sure you want to delete this student?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="6">No students found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

<?= $this->endSection() ?>
