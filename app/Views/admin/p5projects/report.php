<?= $this->extend('layouts/admin_default') ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800"><?= esc($title) ?></h1>
    <p class="mb-4">
        <strong>Tema:</strong> <?= esc($project['theme_name'] ?? 'Belum Ditentukan') ?><br>
        <strong>Periode Projek:</strong> <?= esc(date('d M Y', strtotime($project['start_date']))) ?> - <?= esc(date('d M Y', strtotime($project['end_date']))) ?><br>
        <strong>Deskripsi:</strong> <?= esc($project['description']) ?>
    </p>

    <?php if (session()->getFlashdata('message')) : ?>
        <div class="alert alert-success"><?= session()->getFlashdata('message') ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')) : ?>
        <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Detail Penilaian Projek</h6>
            <a href="<?= site_url('admin/p5projects') ?>" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Kembali ke Daftar Projek
            </a>
        </div>
        <div class="card-body">
            <?php if (!empty($projectStudents) && is_array($projectStudents)) : ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="p5ReportTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th rowspan="2" class="align-middle text-center">No</th>
                                <th rowspan="2" class="align-middle text-center">NIS</th>
                                <th rowspan="2" class="align-middle text-center">Nama Siswa</th>
                                <?php
                                if (!empty($targetSubElements)) {
                                    $dimensionSpans = [];
                                    $elementSpans = [];
                                    $headerRows = ['dimensions' => [], 'elements' => [], 'sub_elements' => []];

                                    foreach ($targetSubElements as $sub) {
                                        $dimName = esc($sub['dimension_name']);
                                        $elName = esc($sub['element_name']);
                                        $subName = esc($sub['name']);

                                        if (!isset($dimensionSpans[$dimName])) {
                                            $dimensionSpans[$dimName] = 0;
                                            $headerRows['dimensions'][$dimName] = ['name' => $dimName, 'span' => 0];
                                        }
                                        if (!isset($elementSpans[$dimName][$elName])) {
                                            $elementSpans[$dimName][$elName] = 0;
                                            $headerRows['elements'][$dimName][$elName] = ['name' => $elName, 'span' => 0];
                                        }

                                        $dimensionSpans[$dimName]++;
                                        $elementSpans[$dimName][$elName]++;
                                        $headerRows['sub_elements'][] = ['name' => $subName, 'dim' => $dimName, 'el' => $elName ];
                                    }

                                    foreach($headerRows['dimensions'] as $dimKey => &$dimVal){
                                        $dimVal['span'] = $dimensionSpans[$dimKey] * 2; // *2 for value and notes
                                    }
                                    foreach($headerRows['elements'] as $dimKey => &$elGroup){
                                        foreach($elGroup as $elKey => &$elVal){
                                            $elVal['span'] = $elementSpans[$dimKey][$elKey] * 2; // *2 for value and notes
                                        }
                                    }
                                }
                                ?>
                                <?php if (!empty($headerRows['dimensions'])) : ?>
                                    <?php foreach ($headerRows['dimensions'] as $dim) : ?>
                                        <th colspan="<?= $dim['span'] ?>" class="text-center"><?= $dim['name'] ?></th>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tr>
                            <tr>
                                <?php if (!empty($headerRows['elements'])) : ?>
                                    <?php foreach ($headerRows['elements'] as $dimKey => $elGroup) : ?>
                                        <?php foreach ($elGroup as $el) : ?>
                                            <th colspan="<?= $el['span'] ?>" class="text-center"><?= $el['name'] ?></th>
                                        <?php endforeach; ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tr>
                            <tr>
                                <?php if (!empty($headerRows['sub_elements'])) : ?>
                                    <?php foreach ($headerRows['sub_elements'] as $sub) : ?>
                                        <th class="text-center" title="Sub Elemen: <?= $sub['name'] ?>"><small><?= $sub['name'] ?><br>(Nilai)</small></th>
                                        <th class="text-center" title="Sub Elemen: <?= $sub['name'] ?>"><small><?= $sub['name'] ?><br>(Catatan)</small></th>
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
                                            $assessment = $assessmentsData[$student['p5_project_student_id']][$subElement['id']] ?? null;
                                            $assessmentValue = $assessment ? esc($assessment['assessment_value']) : '-';
                                            $assessmentNotes = $assessment ? esc($assessment['notes']) : '-';
                                            $assessor = $assessment && $assessment['assessor_name'] ? esc($assessment['assessor_name']) : 'N/A';
                                            $date = $assessment && $assessment['assessment_date'] ? esc(date('d/m/y', strtotime($assessment['assessment_date']))) : 'N/A';
                                            $title = "Penilai: {$assessor}\nTanggal: {$date}\nCatatan: {$assessmentNotes}";
                                            ?>
                                            <td class="text-center" title="<?= $title ?>">
                                                <?= $assessmentValue ?>
                                            </td>
                                             <td title="<?= $title ?>">
                                                <?= nl2br($assessmentNotes) ?>
                                            </td>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else : ?>
                <p>Tidak ada siswa yang dialokasikan untuk projek ini atau tidak ada sub-elemen target yang ditentukan.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<!-- DataTables -->
<script>
    $(document).ready(function() {
        $('#p5ReportTable').DataTable({
            responsive: true,
            dom: 'Bfrtip', // Add B for Buttons
            buttons: [
                { extend: 'copy', className: 'btn-sm' },
                { extend: 'csv', className: 'btn-sm' },
                { extend: 'excel', className: 'btn-sm', title: '<?= esc($title) ?>' },
                { extend: 'pdf', className: 'btn-sm', title: '<?= esc($title) ?>', orientation: 'landscape', pageSize: 'LEGAL' },
                { extend: 'print', className: 'btn-sm', title: '<?= esc($title) ?>' }
            ],
            pageLength: 25, // Default number of rows to display
            order: [], // Disable initial sorting
        });
    });
</script>
<?= $this->endSection() ?>
