<?= $this->extend('layouts/admin_default') // Or a specific parent layout ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800"><?= esc($title) ?></h1>

    <?php if (session()->getFlashdata('error')) : ?>
        <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-body">
            <p>Anda memiliki lebih dari satu anak yang terdaftar. Silakan pilih anak untuk melihat rekap absensi:</p>
            <div class="list-group">
                <?php foreach ($children as $child) : ?>
                    <a href="<?= site_url('ortu/absensi/anak/' . $child['id']) ?>" class="list-group-item list-group-item-action">
                        <?= esc($child['full_name']) ?> (NIS: <?= esc($child['nis'] ?? 'N/A') ?>)
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
