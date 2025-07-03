<?= $this->extend('layouts/admin_default') ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= $title ?></h1>
    </div>

    <?php if (session()->getFlashdata('errors')) : ?>
        <div class="alert alert-danger">
            <ul>
                <?php foreach (session()->getFlashdata('errors') as $error) : ?>
                    <li><?= esc($error) ?></li>
                <?php endforeach ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">P5 Theme Details</h6>
        </div>
        <div class="card-body">
            <?= form_open('admin/p5themes/update/' . $theme['id']) ?>
                <div class="form-group">
                    <label for="name">Theme Name</label>
                    <input type="text" class="form-control <?= ($validation->hasError('name')) ? 'is-invalid' : '' ?>" id="name" name="name" value="<?= old('name', $theme['name']) ?>" required>
                    <?php if ($validation->hasError('name')) : ?>
                        <div class="invalid-feedback">
                            <?= $validation->getError('name') ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea class="form-control <?= ($validation->hasError('description')) ? 'is-invalid' : '' ?>" id="description" name="description" rows="3"><?= old('description', $theme['description']) ?></textarea>
                    <?php if ($validation->hasError('description')) : ?>
                        <div class="invalid-feedback">
                            <?= $validation->getError('description') ?>
                        </div>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn btn-primary">Update Theme</button>
                <a href="<?= site_url('admin/p5themes') ?>" class="btn btn-secondary">Cancel</a>
            <?= form_close() ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
