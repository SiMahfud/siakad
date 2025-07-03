<?= $this->extend('layouts/admin_default') ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Pilih Projek P5 untuk Penilaian</h1>

    <?php if (session()->getFlashdata('success')) : ?>
        <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')) : ?>
        <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Daftar Projek P5</h6>
        </div>
        <div class="card-body">
            <?php if (!empty($projects) && is_array($projects)) : ?>
                <div class="list-group">
                    <?php foreach ($projects as $project) : ?>
                        <a href="<?= site_url('guru/p5assessments/project/' . esc($project['id'])) ?>" class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between">
                                <h5 class="mb-1"><?= esc($project['name']) ?></h5>
                                <small>Periode: <?= esc(date('d M Y', strtotime($project['start_date']))) ?> - <?= esc(date('d M Y', strtotime($project['end_date']))) ?></small>
                            </div>
                            <p class="mb-1"><?= esc($project['description']) ?></p>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <p>Tidak ada projek P5 yang tersedia atau Anda belum ditugaskan sebagai fasilitator.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
