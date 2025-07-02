<?= $this->extend('layouts/admin_default') // Or a specific parent layout if created ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= esc($pageTitle ?? 'Pilih Siswa') ?></h1>
    </div>

    <?php if (session()->getFlashdata('info')) : ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <?= session()->getFlashdata('info') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')) : ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= session()->getFlashdata('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Daftar Anak Terhubung</h6>
        </div>
        <div class="card-body">
            <?php if (!empty($children) && is_array($children)) : ?>
                <p>Silakan pilih nama anak untuk melihat detail nilai mereka:</p>
                <div class="list-group">
                    <?php foreach ($children as $child) : ?>
                        <a href="<?= route_to('ortu_nilai_recap_siswa', $child['id']) ?>" class="list-group-item list-group-item-action">
                            <i class="bi bi-person-check-fill"></i> <?= esc($child['full_name']) ?>
                            (NISN: <?= esc($child['nisn'] ?? 'N/A') ?>)
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <p class="text-muted">Tidak ada data siswa (anak) yang terhubung dengan akun orang tua Anda. Silakan hubungi pihak sekolah jika ini adalah kesalahan.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<style>
.list-group-item .bi {
    margin-right: 0.5rem;
    color: #0d6efd; /* Bootstrap primary color */
}
</style>
<?= $this->endSection() ?>
