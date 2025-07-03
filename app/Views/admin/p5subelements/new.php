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
            <h6 class="m-0 font-weight-bold text-primary">P5 Sub-element Details</h6>
        </div>
        <div class="card-body">
            <?= form_open('admin/p5subelements/create') ?>
                <div class="form-group">
                    <label for="p5_element_id">Parent Element</label>
                    <select class="form-control <?= ($validation->hasError('p5_element_id')) ? 'is-invalid' : '' ?>" id="p5_element_id" name="p5_element_id" required>
                        <option value="">Select Element</option>
                        <?php foreach ($elements as $element) : ?>
                            <option value="<?= esc($element['id']) ?>" <?= (old('p5_element_id') == $element['id']) ? 'selected' : '' ?>>
                                <?= esc($element['name']) ?> (Dim: <?= esc($element['dimension_name']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($validation->hasError('p5_element_id')) : ?>
                        <div class="invalid-feedback">
                            <?= $validation->getError('p5_element_id') ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="name">Sub-element Name</label>
                    <input type="text" class="form-control <?= ($validation->hasError('name')) ? 'is-invalid' : '' ?>" id="name" name="name" value="<?= old('name') ?>" required>
                    <?php if ($validation->hasError('name')) : ?>
                        <div class="invalid-feedback">
                            <?= $validation->getError('name') ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea class="form-control <?= ($validation->hasError('description')) ? 'is-invalid' : '' ?>" id="description" name="description" rows="3"><?= old('description') ?></textarea>
                    <?php if ($validation->hasError('description')) : ?>
                        <div class="invalid-feedback">
                            <?= $validation->getError('description') ?>
                        </div>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn btn-primary">Save Sub-element</button>
                <a href="<?= site_url('admin/p5subelements') ?>" class="btn btn-secondary">Cancel</a>
            <?= form_close() ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
