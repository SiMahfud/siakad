<?= $this->extend('layouts/admin_default') ?>

<?= $this->section('content') ?>

<div class="container-fluid">

    <!-- Page Heading -->
    <h1 class="h3 mb-4 text-gray-800"><?= esc($title); ?></h1>

    <?php if (session()->getFlashdata('success')) : ?>
        <div class="alert alert-success" role="alert">
            <?= session()->getFlashdata('success'); ?>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')) : ?>
        <div class="alert alert-danger" role="alert">
            <?= session()->getFlashdata('error'); ?>
        </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Pilih Parameter Ekspor</h6>
        </div>
        <div class="card-body">
            <?php if (empty($classes)) : ?>
                <div class="alert alert-warning" role="alert">
                    Anda tidak terdaftar sebagai wali kelas untuk kelas manapun, atau tidak ada kelas yang ditugaskan kepada Anda. Fitur ekspor tidak tersedia.
                </div>
            <?php else : ?>
                <form method="post" action="<?= route_to('wali_kelas_erapor_process'); ?>">
                    <?= csrf_field(); ?>
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label for="class_id">Kelas Perwalian</label>
                            <select class="form-control" id="class_id" name="class_id" required>
                                <option value="">-- Pilih Kelas --</option>
                                <?php foreach ($classes as $class) : ?>
                                    <option value="<?= esc($class['id'], 'attr'); ?>" <?= ($current_class_id == $class['id']) ? 'selected' : ''; ?>>
                                        <?= esc($class['class_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="academic_year">Tahun Ajaran</label>
                            <select class="form-control" id="academic_year" name="academic_year" required>
                                <option value="">-- Pilih Tahun Ajaran --</option>
                                <?php if (!empty($academic_years)) : ?>
                                    <?php foreach ($academic_years as $ay) : ?>
                                        <option value="<?= esc($ay['academic_year'], 'attr'); ?>" <?= ($current_academic_year == $ay['academic_year']) ? 'selected' : ''; ?>>
                                            <?= esc($ay['academic_year']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="" disabled>Data tahun ajaran tidak tersedia</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="semester">Semester</label>
                            <select class="form-control" id="semester" name="semester" required>
                                <option value="">-- Pilih Semester --</option>
                                <?php if (!empty($semesters)) :?>
                                    <?php foreach ($semesters as $sem) : ?>
                                        <option value="<?= esc($sem['semester'], 'attr'); ?>" <?= ($current_semester == $sem['semester']) ? 'selected' : ''; ?>>
                                            <?= ($sem['semester'] == 1) ? 'Ganjil' : (($sem['semester'] == 2) ? 'Genap' : esc($sem['semester'])); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                     <option value="1" <?= ($current_semester == '1') ? 'selected' : ''; ?>>Ganjil</option>
                                     <option value="2" <?= ($current_semester == '2') ? 'selected' : ''; ?>>Genap</option>
                                     <option value="" disabled>Data semester lain tidak tersedia</option>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>

                    <p class="mt-3 small text-muted">
                        Catatan:
                        <ul>
                            <li>Pastikan semua nilai sumatif telah diinput dengan benar sebelum melakukan ekspor.</li>
                            <li>Proses ekspor akan menghasilkan file Excel (.xlsx) yang berisi rata-rata nilai sumatif per mata pelajaran untuk setiap siswa di kelas yang dipilih pada tahun ajaran dan semester terkait.</li>
                            <li>Format file disesuaikan untuk impor ke aplikasi e-Rapor Kemdikbud (asumsi format umum).</li>
                        </ul>
                    </p>

                    <button type="submit" class="btn btn-primary"><i class="fas fa-file-excel"></i> Ekspor ke e-Rapor</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    // Script tambahan jika diperlukan, misalnya untuk validasi sisi klien atau AJAX.
    $(document).ready(function() {
        // Contoh: Jika ingin mengisi tahun ajaran/semester default berdasarkan tanggal saat ini (perlu logika backend/JS tambahan)
    });
</script>
<?= $this->endSection() ?>
