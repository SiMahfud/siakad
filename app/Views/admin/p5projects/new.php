<?= $this->extend('layouts/admin_default') ?>

<?= $this->section('styles') ?>
<style>
    .select-sub-elements {
        height: 200px; /* Adjust as needed */
    }
    .form-group small {
        display: block;
        margin-top: 0.25rem;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= $title ?></h1>
    </div>

    <?php if (session()->getFlashdata('errors')) : ?>
        <div class="alert alert-danger">
            <strong>Please correct the following errors:</strong>
            <ul>
                <?php foreach (session()->getFlashdata('errors') as $error) : ?>
                    <li><?= esc($error) ?></li>
                <?php endforeach ?>
            </ul>
        </div>
    <?php endif; ?>
     <?php if (session()->getFlashdata('error')) : ?>
        <div class="alert alert-danger">
            <?= session()->getFlashdata('error') ?>
        </div>
    <?php endif; ?>


    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">P5 Project Details</h6>
        </div>
        <div class="card-body">
            <?= form_open('admin/p5projects/create') ?>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="name">Project Name</label>
                            <input type="text" class="form-control <?= ($validation->hasError('name')) ? 'is-invalid' : '' ?>" id="name" name="name" value="<?= old('name') ?>" required>
                            <?php if ($validation->hasError('name')) : ?>
                                <div class="invalid-feedback">
                                    <?= $validation->getError('name') ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="p5_theme_id">P5 Theme</label>
                            <select class="form-control <?= ($validation->hasError('p5_theme_id')) ? 'is-invalid' : '' ?>" id="p5_theme_id" name="p5_theme_id" required>
                                <option value="">Select Theme</option>
                                <?php foreach ($themes as $theme) : ?>
                                    <option value="<?= esc($theme['id']) ?>" <?= (old('p5_theme_id') == $theme['id']) ? 'selected' : '' ?>>
                                        <?= esc($theme['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ($validation->hasError('p5_theme_id')) : ?>
                                <div class="invalid-feedback">
                                    <?= $validation->getError('p5_theme_id') ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
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

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="start_date">Start Date</label>
                            <input type="date" class="form-control <?= ($validation->hasError('start_date')) ? 'is-invalid' : '' ?>" id="start_date" name="start_date" value="<?= old('start_date') ?>">
                            <?php if ($validation->hasError('start_date')) : ?>
                                <div class="invalid-feedback">
                                    <?= $validation->getError('start_date') ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="end_date">End Date</label>
                            <input type="date" class="form-control <?= ($validation->hasError('end_date')) ? 'is-invalid' : '' ?>" id="end_date" name="end_date" value="<?= old('end_date') ?>">
                            <?php if ($validation->hasError('end_date')) : ?>
                                <div class="invalid-feedback">
                                    <?= $validation->getError('end_date') ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="target_sub_elements">Target P5 Sub-elements</label>
                    <small class="text-muted">Select one or more sub-elements that this project aims to achieve. (Hold Ctrl/Cmd to select multiple)</small>
                    <select multiple class="form-control select-sub-elements <?= ($validation->hasError('target_sub_elements')) ? 'is-invalid' : '' ?>" id="target_sub_elements" name="target_sub_elements[]">
                        <?php if (!empty($sub_elements)) : ?>
                            <?php
                            $grouped_sub_elements = [];
                            foreach ($sub_elements as $sub_el) {
                                $grouped_sub_elements[$sub_el['dimension_name']][$sub_el['element_name']][] = $sub_el;
                            }
                            ?>
                            <?php foreach ($grouped_sub_elements as $dim_name => $elements_in_dim) : ?>
                                <optgroup label="Dimensi: <?= esc($dim_name) ?>">
                                    <?php foreach ($elements_in_dim as $el_name => $sub_elements_in_el) : ?>
                                        <optgroup label="&nbsp;&nbsp;&nbsp;&nbsp;Elemen: <?= esc($el_name) ?>">
                                            <?php foreach ($sub_elements_in_el as $sub_element) : ?>
                                                <option value="<?= esc($sub_element['id']) ?>" <?= (is_array(old('target_sub_elements')) && in_array($sub_element['id'], old('target_sub_elements'))) ? 'selected' : '' ?>>
                                                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?= esc($sub_element['name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <option value="" disabled>No sub-elements available. Please create sub-elements first.</option>
                        <?php endif; ?>
                    </select>
                    <?php if ($validation->hasError('target_sub_elements')) : ?>
                        <div class="invalid-feedback d-block">
                             <?= $validation->getError('target_sub_elements') ?>
                        </div>
                    <?php endif; ?>
                </div>


                <button type="submit" class="btn btn-primary">Save Project</button>
                <a href="<?= site_url('admin/p5projects') ?>" class="btn btn-secondary">Cancel</a>
            <?= form_close() ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<!-- You could add Select2/Choices.js initialization here if you were to use them -->
<!-- <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" /> -->
<!-- <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script> -->
<!-- <script>
// $(document).ready(function() {
//     $('#target_sub_elements').select2({
//         placeholder: "Select target sub-elements",
//         allowClear: true
//     });
// });
// </script> -->
<?= $this->endSection() ?>
