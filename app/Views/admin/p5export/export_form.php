<?= $this->extend('layouts/admin_default') ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800"><?= esc($title) ?></h1>

    <?php if (session()->getFlashdata('error')) : ?>
        <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('errors')) : ?>
        <div class="alert alert-danger">
            <p><strong>Harap perbaiki kesalahan berikut:</strong></p>
            <ul>
                <?php foreach (session()->getFlashdata('errors') as $error) : ?>
                    <li><?= esc($error) ?></li>
                <?php endforeach ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Pilih Parameter Ekspor</h6>
        </div>
        <div class="card-body">
            <form action="<?= site_url('admin/p5export/process') ?>" method="post">
                <?= csrf_field() ?>

                <div class="form-group">
                    <label for="project_id">Projek P5</label>
                    <select name="project_id" id="project_id" class="form-control" required>
                        <option value="">-- Pilih Projek --</option>
                        <?php foreach ($projects as $project) : ?>
                            <option value="<?= $project['id'] ?>" <?= set_select('project_id', $project['id']) ?>><?= esc($project['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="class_id">Kelas/Kelompok</label>
                    <select name="class_id" id="class_id" class="form-control" required disabled>
                        <option value="">-- Pilih Projek Terlebih Dahulu --</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="dimension_id">Dimensi P3</label>
                    <select name="dimension_id" id="dimension_id" class="form-control" required disabled>
                        <option value="">-- Pilih Projek Terlebih Dahulu --</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="kd_semester">Kode Semester (Opsional, cth: 20241 atau 20242)</label>
                    <input type="text" name="kd_semester" id="kd_semester" class="form-control" value="<?= set_value('kd_semester', date('Y').(date('m') <= 6 ? '1' : '2')) ?>" placeholder="Default: <?= date('Y').(date('m') <= 6 ? '1' : '2') ?>">
                     <small class="form-text text-muted">Jika dikosongkan, akan menggunakan tahun dan semester saat ini.</small>
                </div>


                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-file-excel"></i> Ekspor ke Excel
                </button>
                <a href="<?= site_url('admin/dashboard') ?>" class="btn btn-secondary">Batal</a>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(document).ready(function() {
    $('#project_id').change(function() {
        var projectId = $(this).val();
        var classSelect = $('#class_id');
        var dimensionSelect = $('#dimension_id');

        classSelect.html('<option value="">Memuat kelas...</option>').prop('disabled', true);
        dimensionSelect.html('<option value="">Memuat dimensi...</option>').prop('disabled', true);

        if (projectId) {
            // Fetch Classes
            $.ajax({
                url: '<?= site_url('admin/p5export/ajax/classes/') ?>' + projectId,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    classSelect.html('<option value="">-- Pilih Kelas/Kelompok --</option>');
                    if (response && response.length > 0) {
                        $.each(response, function(key, value) {
                            classSelect.append('<option value="' + value.id + '">' + value.class_name + '</option>');
                        });
                        classSelect.prop('disabled', false);
                    } else {
                        classSelect.html('<option value="">Tidak ada kelas terkait projek ini</option>');
                    }
                },
                error: function() {
                    classSelect.html('<option value="">Gagal memuat kelas</option>');
                }
            });

            // Fetch Dimensions
            $.ajax({
                url: '<?= site_url('admin/p5export/ajax/dimensions/') ?>' + projectId,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    dimensionSelect.html('<option value="">-- Pilih Dimensi P3 --</option>');
                    if (response && response.length > 0) {
                        $.each(response, function(key, value) {
                            dimensionSelect.append('<option value="' + value.id + '">' + value.name + '</option>');
                        });
                        dimensionSelect.prop('disabled', false);
                    } else {
                        dimensionSelect.html('<option value="">Tidak ada dimensi target di projek ini</option>');
                    }
                },
                error: function() {
                    dimensionSelect.html('<option value="">Gagal memuat dimensi</option>');
                }
            });

        } else {
            classSelect.html('<option value="">-- Pilih Projek Terlebih Dahulu --</option>').prop('disabled', true);
            dimensionSelect.html('<option value="">-- Pilih Projek Terlebih Dahulu --</option>').prop('disabled', true);
        }
    });

    // Preserve selected values on page load if validation fails (form repopulation)
    var initialProjectId = $('#project_id').val();
    if (initialProjectId) {
        $('#project_id').trigger('change'); // Trigger change to load dependent dropdowns

        // Need to set selected values after AJAX calls complete if old values exist
        var oldClassId = '<?= old('class_id') ?>';
        var oldDimensionId = '<?= old('dimension_id') ?>';

        if(oldClassId){
            // Wait for classes to load then set
            $(document).ajaxComplete(function(event, xhr, settings) {
                if (settings.url.includes('ajax/classes/' + initialProjectId)) {
                    $('#class_id').val(oldClassId);
                }
            });
        }
        if(oldDimensionId){
             // Wait for dimensions to load then set
            $(document).ajaxComplete(function(event, xhr, settings) {
                 if (settings.url.includes('ajax/dimensions/' + initialProjectId)) {
                    $('#dimension_id').val(oldDimensionId);
                }
            });
        }
    }
});
</script>
<?= $this->endSection() ?>
