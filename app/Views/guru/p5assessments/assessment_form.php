<?= $this->extend('layouts/admin_default') ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800">Input Penilaian P5: <?= esc($project['name']) ?></h1>
    <p class="mb-4">Periode Projek: <?= esc(date('d M Y', strtotime($project['start_date']))) ?> - <?= esc(date('d M Y', strtotime($project['end_date']))) ?></p>

    <?php if (session()->getFlashdata('success')) : ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= session()->getFlashdata('success') ?>
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
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <p><strong>Terjadi Kesalahan Validasi:</strong></p>
            <ul>
                <?php foreach (session('errors') as $error) : ?>
                    <li><?= esc($error) ?></li>
                <?php endforeach ?>
            </ul>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        </div>
    <?php endif; ?>


    <form action="<?= site_url('guru/p5assessments/save/' . $project['id']) ?>" method="post">
        <?= csrf_field() ?>
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Form Penilaian</h6>
            </div>
            <div class="card-body">
                <?php if (!empty($projectStudents) && is_array($projectStudents)) : ?>
                    <div class="table-responsive">
                        <table class="table table-bordered" id="assessmentTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th rowspan="2" class="align-middle text-center">No</th>
                                    <th rowspan="2" class="align-middle text-center">NIS</th>
                                    <th rowspan="2" class="align-middle text-center">Nama Siswa</th>
                                    <?php if (!empty($targetSubElements)) : ?>
                                        <?php
                                        $dimensionSpan = [];
                                        foreach ($targetSubElements as $subElement) {
                                            $dimensionSpan[$subElement['dimension_name']] = ($dimensionSpan[$subElement['dimension_name']] ?? 0) + 1;
                                        }
                                        ?>
                                        <?php foreach ($dimensionSpan as $dimName => $span) : ?>
                                            <th colspan="<?= $span * 2 ?>" class="text-center"><?= esc($dimName) ?></th>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tr>
                                <tr>
                                    <?php if (!empty($targetSubElements)) : ?>
                                        <?php foreach ($targetSubElements as $subElement) : ?>
                                            <th class="text-center" title="<?= esc($subElement['element_name']) ?>"><?= esc($subElement['name']) ?><br><small>(Nilai)</small></th>
                                            <th class="text-center" title="<?= esc($subElement['element_name']) ?>"><?= esc($subElement['name']) ?><br><small>(Catatan)</small></th>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1; ?>
                                <?php foreach ($projectStudents as $student) : ?>
                                    <tr>
                                        <td class="text-center"><?= $no++ ?></td>
                                        <td><?= esc($student['nis']) ?></td>
                                        <td><?= esc($student['student_name']) ?></td>
                                        <?php if (!empty($targetSubElements)) : ?>
                                            <?php foreach ($targetSubElements as $subElement) : ?>
                                                <?php
                                                $currentAssessmentValue = $existingAssessments[$student['p5_project_student_id']][$subElement['id']]['assessment_value'] ?? '';
                                                $currentNotes = $existingAssessments[$student['p5_project_student_id']][$subElement['id']]['notes'] ?? '';
                                                ?>
                                                <td>
                                                    <select name="assessments[<?= esc($student['p5_project_student_id']) ?>][<?= esc($subElement['id']) ?>][assessment_value]" class="form-control form-control-sm">
                                                        <option value="">- Pilih -</option>
                                                        <?php foreach ($assessment_options as $option) : ?>
                                                            <option value="<?= esc($option) ?>" <?= ($currentAssessmentValue == $option) ? 'selected' : '' ?>><?= esc($option) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </td>
                                                <td>
                                                    <textarea name="assessments[<?= esc($student['p5_project_student_id']) ?>][<?= esc($subElement['id']) ?>][notes]" class="form-control form-control-sm" rows="2"><?= esc($currentNotes) ?></textarea>
                                                </td>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else : ?>
                    <p>Tidak ada siswa yang dialokasikan untuk projek ini.</p>
                <?php endif; ?>
            </div>
            <div class="card-footer">
                <a href="<?= site_url('guru/p5assessments') ?>" class="btn btn-secondary">Kembali ke Pilih Projek</a>
                <?php if (!empty($projectStudents)) : ?>
                <button type="submit" class="btn btn-primary">Simpan Penilaian</button>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    // Optional: Add any JavaScript needed for the form, e.g., dynamic interactions.
    // For now, standard submit is handled.
</script>
<?= $this->endSection() ?>
