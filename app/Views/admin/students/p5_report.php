<?= $this->extend('layouts/admin_default') ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800"><?= esc($title) ?></h1>
    <p class="mb-4">
        <strong>Siswa:</strong> <?= esc($student['full_name']) ?> (NISN: <?= esc($student['nisn'] ?? 'N/A') ?>, NIS: <?= esc($student['nis'] ?? 'N/A') ?>)<br>
        <a href="<?= site_url('admin/students/edit/' . $student['id']) ?>">&laquo; Kembali ke Detail Siswa</a>
    </p>

    <?php if (empty($reportData)) : ?>
        <div class="alert alert-info">Siswa ini belum mengikuti atau belum memiliki penilaian pada projek P5 manapun.</div>
    <?php else : ?>
        <?php foreach ($reportData as $projectReport) : ?>
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h5 class="m-0 font-weight-bold text-primary"><?= esc($projectReport['project_info']['project_name']) ?></h5>
                    <small>Periode: <?= esc(date('d M Y', strtotime($projectReport['project_info']['start_date']))) ?> - <?= esc(date('d M Y', strtotime($projectReport['project_info']['end_date']))) ?></small><br>
                    <small>Deskripsi Projek: <?= esc($projectReport['project_info']['project_description']) ?></small>
                </div>
                <div class="card-body">
                    <?php if (empty($projectReport['target_sub_elements'])) : ?>
                        <p>Projek ini tidak memiliki target sub-elemen yang ditentukan.</p>
                    <?php else : ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm" width="100%" cellspacing="0">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Dimensi</th>
                                        <th>Elemen</th>
                                        <th>Sub-elemen</th>
                                        <th>Penilaian</th>
                                        <th>Catatan Deskriptif</th>
                                        <th>Tanggal Nilai</th>
                                        <!-- <th>Penilai</th> -->
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $currentDimension = '';
                                    $currentElement = '';
                                    ?>
                                    <?php foreach ($projectReport['target_sub_elements'] as $subElement) : ?>
                                        <?php
                                        $assessment = $projectReport['assessments'][$subElement['id']] ?? null;
                                        ?>
                                        <tr>
                                            <td>
                                                <?php if ($currentDimension != $subElement['dimension_name']) : ?>
                                                    <?= esc($subElement['dimension_name']) ?>
                                                    <?php $currentDimension = $subElement['dimension_name']; ?>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($currentElement != $subElement['element_name']) : ?>
                                                    <?= esc($subElement['element_name']) ?>
                                                    <?php $currentElement = $subElement['element_name']; ?>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= esc($subElement['name']) ?></td>
                                            <td class="text-center"><strong><?= esc($assessment['assessment_value'] ?? '-') ?></strong></td>
                                            <td><?= nl2br(esc($assessment['notes'] ?? '-')) ?></td>
                                            <td><?= $assessment ? esc(date('d M Y', strtotime($assessment['assessment_date']))) : '-' ?></td>
                                            <!-- <td><?= '' // Logic to get assessor name if needed ?></td> -->
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    // Any specific scripts for this page can go here
    // For example, if using DataTables for each project table (might be overkill if tables are small)
</script>
<?= $this->endSection() ?>
