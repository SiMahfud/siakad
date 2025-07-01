<?= $this->extend('admin/layout/header') ?>

<?= $this->section('content') ?>

    <h1>Manage Classes (Rombongan Belajar)</h1>

    <a href="<?= site_url('admin/classes/new') ?>" class="add-button">Add New Class</a>

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
                <th>Class Name</th>
                <th>Academic Year</th>
                <th>Fase</th>
                <th>Wali Kelas</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($classes) && is_array($classes)) : ?>
                <?php foreach ($classes as $class_item) : ?>
                    <tr>
                        <td><?= esc($class_item['id']) ?></td>
                        <td><?= esc($class_item['class_name']) ?></td>
                        <td><?= esc($class_item['academic_year']) ?></td>
                        <td><?= esc($class_item['fase']) ?></td>
                        <td><?= esc($class_item['wali_kelas_name'] ?? 'N/A') ?></td>
                        <td class="action-links">
                            <a href="<?= site_url('admin/classes/edit/' . $class_item['id']) ?>">Edit</a>
                            <a href="<?= site_url('admin/classes/delete/' . $class_item['id']) ?>" onclick="return confirm('Are you sure you want to delete this class? This might affect related records.');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="6">No classes found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

<?= $this->endSection() ?>
