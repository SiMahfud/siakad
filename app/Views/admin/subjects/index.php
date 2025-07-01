<?= $this->extend('admin/layout/header') ?>

<?= $this->section('content') ?>

    <h1>Manage Subjects</h1>

    <a href="<?= site_url('admin/subjects/new') ?>" class="add-button">Add New Subject</a>

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
                <th>Subject Code</th>
                <th>Subject Name</th>
                <th>Type</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($subjects) && is_array($subjects)) : ?>
                <?php foreach ($subjects as $subject) : ?>
                    <tr>
                        <td><?= esc($subject['id']) ?></td>
                        <td><?= esc($subject['subject_code']) ?></td>
                        <td><?= esc($subject['subject_name']) ?></td>
                        <td><?= $subject['is_pilihan'] ? 'Pilihan (Elective)' : 'Wajib (Core)' ?></td>
                        <td class="action-links">
                            <a href="<?= site_url('admin/subjects/edit/' . $subject['id']) ?>">Edit</a>
                            <a href="<?= site_url('admin/subjects/delete/' . $subject['id']) ?>" onclick="return confirm('Are you sure you want to delete this subject?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="5">No subjects found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

<?= $this->endSection() ?>
