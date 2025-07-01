<?= $this->extend('admin/layout/header') ?>

<?= $this->section('content') ?>

    <h1>Manage Teachers</h1>

    <a href="<?= site_url('admin/teachers/new') ?>" class="add-button">Add New Teacher</a>

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
                <th>NIP</th>
                <th>Full Name</th>
                <th>User ID (Login)</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($teachers) && is_array($teachers)) : ?>
                <?php foreach ($teachers as $teacher) : ?>
                    <tr>
                        <td><?= esc($teacher['id']) ?></td>
                        <td><?= esc($teacher['nip']) ?></td>
                        <td><?= esc($teacher['full_name']) ?></td>
                        <td><?= esc($teacher['user_id']) ?></td>
                        <td class="action-links">
                            <a href="<?= site_url('admin/teachers/edit/' . $teacher['id']) ?>">Edit</a>
                            <a href="<?= site_url('admin/teachers/delete/' . $teacher['id']) ?>" onclick="return confirm('Are you sure you want to delete this teacher?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="5">No teachers found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

<?= $this->endSection() ?>
