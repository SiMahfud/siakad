<?= $this->extend('layouts/admin_default') ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800"><?= esc($title) ?></h1>

    <?php if (session()->getFlashdata('message')) : ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= session()->getFlashdata('message') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')) : ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= session()->getFlashdata('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if ($validation->getErrors()) : ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <p><strong>Harap perbaiki kesalahan berikut:</strong></p>
            <ul>
                <?php foreach ($validation->getErrors() as $error) : ?>
                    <li><?= esc($error) ?></li>
                <?php endforeach ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Formulir Pengaturan</h6>
        </div>
        <div class="card-body">
            <form action="<?= site_url('admin/settings/save') ?>" method="post">
                <?= csrf_field() ?>

                <?php foreach ($settings as $key => $setting) : ?>
                    <div class="mb-3">
                        <label for="<?= esc($key) ?>" class="form-label"><?= esc($setting['label']) ?></label>
                        <?php if ($key === 'current_semester') : ?>
                            <select name="<?= esc($key) ?>" id="<?= esc($key) ?>" class="form-select <?= $validation->hasError($key) ? 'is-invalid' : '' ?>">
                                <option value="1" <?= ($setting['value'] == '1') ? 'selected' : '' ?>>1 (Ganjil)</option>
                                <option value="2" <?= ($setting['value'] == '2') ? 'selected' : '' ?>>2 (Genap)</option>
                            </select>
                        <?php elseif ($key === 'school_address') : ?>
                            <textarea name="<?= esc($key) ?>" id="<?= esc($key) ?>" class="form-control <?= $validation->hasError($key) ? 'is-invalid' : '' ?>" rows="3"><?= esc($setting['value'] ?? '', 'html') ?></textarea>
                        <?php else : ?>
                            <input type="text" name="<?= esc($key) ?>" id="<?= esc($key) ?>"
                                   class="form-control <?= $validation->hasError($key) ? 'is-invalid' : '' ?>"
                                   value="<?= esc($setting['value'] ?? '', 'attr') ?>"
                                   <?= ($key === 'current_academic_year') ? 'placeholder="Contoh: 2023/2024"' : '' ?>
                                   >
                        <?php endif; ?>
                        <?php if ($validation->hasError($key)) : ?>
                            <div class="invalid-feedback">
                                <?= $validation->getError($key) ?>
                            </div>
                        <?php endif; ?>
                         <small class="form-text text-muted">Aturan: <?= esc($setting['rules']) ?></small>
                    </div>
                <?php endforeach; ?>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Simpan Pengaturan
                </button>
                <a href="<?= site_url('admin/dashboard') ?>" class="btn btn-secondary">Batal</a>
                <?php // Ganti admin/dashboard dengan rute dashboard admin yang sesuai jika ada ?>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
