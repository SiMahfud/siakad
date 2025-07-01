<?= $this->extend('admin/layout/header') ?>

<?= $this->section('content') ?>

    <h1>Manage Users</h1>

    <a href="<?= site_url('admin/users/new') ?>" class="add-button">Add New User</a>

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
                <th>Full Name</th>
                <th>Username</th>
                <th>Role</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($users) && is_array($users)) : ?>
                <?php foreach ($users as $user) : ?>
                    <tr>
                        <td><?= esc($user['id']) ?></td>
                        <td><?= esc($user['full_name']) ?></td>
                        <td><?= esc($user['username']) ?></td>
                        <td><?= esc($user['role_name'] ?? 'N/A') ?></td>
                        <td><?= $user['is_active'] ? 'Active' : 'Inactive' ?></td>
                        <td class="action-links">
                            <a href="<?= site_url('admin/users/edit/' . $user['id']) ?>">Edit</a>
                            <?php if (session()->get('user_id') != $user['id']) : // Prevent deleting self from this interface ?>
                                <a href="<?= site_url('admin/users/delete/' . $user['id']) ?>" class="delete" onclick="return confirm('Are you sure you want to delete this user?');">Delete</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="6">No users found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

<?= $this->endSection() ?>
