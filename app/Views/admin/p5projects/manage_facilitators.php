<?= $this->extend('layouts/admin_default') ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800">Kelola Fasilitator: <?= esc($project['name']) ?></h1>
    <p class="mb-4">
        <a href="<?= site_url('admin/p5projects') ?>">&laquo; Kembali ke Daftar Projek P5</a>
    </p>

    <?php if (session()->getFlashdata('message')) : ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= session()->getFlashdata('message') ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')) : ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= session()->getFlashdata('error') ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        </div>
    <?php endif; ?>
    <?php if (session()->has('errors')) : ?>
        <div class="alert alert-danger">
            <strong>Error Validasi:</strong>
            <ul>
                <?php foreach (session('errors') as $error) : ?>
                    <li><?= esc($error) ?></li>
                <?php endforeach ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Assigned Facilitators Column -->
        <div class="col-md-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Fasilitator Tertugas (<?= count($assigned_facilitators) ?>)</h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($assigned_facilitators)) : ?>
                        <ul class="list-group">
                            <?php foreach ($assigned_facilitators as $facilitator) : ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <?= esc($facilitator['teacher_name']) ?> (NIP: <?= esc($facilitator['teacher_nip'] ?? 'N/A') ?>)
                                    <a href="<?= site_url('admin/p5projects/' . $project['id'] . '/remove-facilitator/' . $facilitator['teacher_id']) ?>"
                                       class="btn btn-danger btn-sm"
                                       onclick="return confirm('Anda yakin ingin menghapus fasilitator ini dari projek?');">
                                       <i class="fas fa-trash"></i> Hapus
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else : ?>
                        <p>Belum ada fasilitator yang ditugaskan untuk projek ini.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Available Teachers Column -->
        <div class="col-md-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Tambah Fasilitator Baru</h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($available_teachers)) : ?>
                        <form action="<?= site_url('admin/p5projects/' . $project['id'] . '/add-facilitator') ?>" method="post">
                            <?= csrf_field() ?>
                            <div class="form-group">
                                <label for="teachers_to_add">Pilih Guru untuk Dijadikan Fasilitator:</label>
                                <select multiple class="form-control" id="teachers_to_add" name="teachers_to_add[]" size="10">
                                    <?php foreach ($available_teachers as $teacher) : ?>
                                        <option value="<?= esc($teacher['id']) ?>">
                                            <?= esc($teacher['full_name']) ?> (NIP: <?= esc($teacher['nip'] ?? 'N/A') ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="form-text text-muted">Tahan Ctrl (atau Cmd di Mac) untuk memilih lebih dari satu guru.</small>
                            </div>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Tambah Fasilitator Terpilih</button>
                        </form>
                    <?php else : ?>
                        <p>Semua guru sudah ditugaskan sebagai fasilitator atau tidak ada data guru yang tersedia.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    // Additional JS for enhancing multi-select or other interactions can go here.
</script>
<?= $this->endSection() ?>
