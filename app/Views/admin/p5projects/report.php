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

    <?php if (!empty($subElementChartData) && is_array($subElementChartData)): ?>
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Visualisasi Pencapaian Sub-Elemen</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <?php foreach ($subElementChartData as $index => $chartItem): ?>
                    <?php if (array_sum($chartItem['counts']) > 0) : // Only display chart if there's data ?>
                    <div class="col-lg-6 mb-4">
                        <div class="chart-container" style="position: relative; height:40vh; width:100%" id="chartContainer<?= $index ?>">
                            <canvas id="chartSubElement<?= $index ?>"></canvas>
                        </div>
                        <p class="text-center small mt-2">
                            Dimensi: <?= $chartItem['dimension_name'] ?><br>
                            Elemen: <?= $chartItem['element_name'] ?>
                        </p>
                    </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
             <?php
                $hasCharts = false;
                foreach ($subElementChartData as $chartItem) {
                    if (array_sum($chartItem['counts']) > 0) {
                        $hasCharts = true;
                        break;
                    }
                }
                if (!$hasCharts):
            ?>
                <p class="text-center">Belum ada data penilaian yang cukup untuk ditampilkan dalam bentuk visualisasi.</p>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

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

<?php if (!empty($subElementChartData) && is_array($subElementChartData)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const chartData = <?= json_encode(array_values($subElementChartData)) ?>; // Use array_values for easier iteration
        const assessmentLevels = ['BB', 'MB', 'BSH', 'SB', '-'];
        const backgroundColors = {
            'BB': 'rgba(255, 99, 132, 0.7)',  // Red
            'MB': 'rgba(255, 205, 86, 0.7)', // Yellow
            'BSH': 'rgba(75, 192, 192, 0.7)', // Green
            'SB': 'rgba(54, 162, 235, 0.7)',  // Blue
            '-': 'rgba(201, 203, 207, 0.7)'   // Grey for Not Assessed
        };
        const borderColors = {
            'BB': 'rgb(255, 99, 132)',
            'MB': 'rgb(255, 205, 86)',
            'BSH': 'rgb(75, 192, 192)',
            'SB': 'rgb(54, 162, 235)',
            '-': 'rgb(201, 203, 207)'
        };

        chartData.forEach((subElement, index) => {
            const canvasId = `chartSubElement${index}`;
            const chartContainer = document.getElementById(`chartContainer${index}`);
            if (chartContainer) { // Ensure the container exists
                const ctx = document.getElementById(canvasId).getContext('2d');
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: assessmentLevels,
                        datasets: [{
                            label: `Pencapaian: ${subElement.name}`,
                            data: assessmentLevels.map(level => subElement.counts[level] || 0),
                            backgroundColor: assessmentLevels.map(level => backgroundColors[level]),
                            borderColor: assessmentLevels.map(level => borderColors[level]),
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Jumlah Siswa'
                                },
                                ticks: {
                                    stepSize: 1 // Ensure y-axis shows whole numbers for student counts
                                }
                            },
                            x: {
                                title: {
                                    display: true,
                                    text: 'Level Pencapaian'
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top',
                            },
                            title: {
                                display: true,
                                text: `Distribusi Pencapaian Sub-Elemen: ${subElement.name}`,
                                font: {
                                    size: 16
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        let label = context.dataset.label || '';
                                        if (label) {
                                            label += ': ';
                                        }
                                        if (context.parsed.y !== null) {
                                            label += context.parsed.y + ' siswa';
                                        }
                                        return label;
                                    }
                                }
                            }
                        }
                    }
                });
            }
        });
    });
</script>
<?php endif; ?>
<?= $this->endSection() ?>
