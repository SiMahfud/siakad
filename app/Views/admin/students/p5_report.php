<?= $this->extend('layouts/admin_default') ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800"><?= esc($title) ?></h1>
    <p class="mb-4">
        <strong>Siswa:</strong> <?= esc($student['full_name']) ?> (NISN: <?= esc($student['nisn'] ?? 'N/A') ?>, NIS: <?= esc($student['nis'] ?? 'N/A') ?>)<br>
        <a href="<?= site_url('admin/students') ?>">&laquo; Kembali ke Daftar Siswa</a> |
        <a href="<?= site_url('admin/students/edit/' . $student['id']) ?>">Edit Detail Siswa</a>
    </p>

    <!-- Radar Chart Section -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Ringkasan Profil Dimensi P5 Siswa</h6>
        </div>
        <div class="card-body">
            <div id="radarChartContainer" style="position: relative; height:50vh; width:100%;">
                <canvas id="studentDimensionRadarChart"></canvas>
            </div>
        </div>
    </div>

    <?php if (empty($reportData)) : ?>
        <div class="alert alert-info mt-4">Siswa ini belum mengikuti atau belum memiliki penilaian pada projek P5 manapun untuk ditampilkan detailnya.</div>
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
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        <?php if (isset($radarChartData) && !empty($radarChartData['labels']) && !empty($radarChartData['scores'])): ?>
        const radarCtx = document.getElementById('studentDimensionRadarChart').getContext('2d');
        new Chart(radarCtx, {
            type: 'radar',
            data: {
                labels: <?= json_encode($radarChartData['labels']) ?>,
                datasets: [{
                    label: 'Rata-rata Skor Dimensi P5',
                    data: <?= json_encode($radarChartData['scores']) ?>,
                    fill: true,
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgb(54, 162, 235)',
                    pointBackgroundColor: 'rgb(54, 162, 235)',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: 'rgb(54, 162, 235)'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                elements: {
                    line: {
                        borderWidth: 3
                    }
                },
                scales: {
                    r: {
                        angleLines: {
                            display: true
                        },
                        suggestedMin: 0,
                        suggestedMax: 4, // Assuming BB=1, MB=2, BSH=3, SB=4
                        ticks: {
                           stepSize: 1,
                           backdropColor: 'rgba(255, 255, 255, 0.75)', // Make ticks more readable
                           font: {
                               size: 10
                           },
                           callback: function(value, index, values) {
                                // Custom labels for radar ticks
                                switch(value) {
                                    case 1: return 'BB';
                                    case 2: return 'MB';
                                    case 3: return 'BSH';
                                    case 4: return 'SB';
                                    default: return ''; // For 0 or other values
                                }
                            }
                        },
                        pointLabels: {
                           font: {
                               size: 11 // Adjust size of dimension labels
                           }
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Ringkasan Profil Dimensi P5 Siswa',
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
                                if (context.parsed.r !== null) {
                                    // Map numeric value back to descriptive for tooltip if desired, or show score
                                    // For now, show the score directly.
                                    label += context.parsed.r.toFixed(2);
                                }
                                return label;
                            }
                        }
                    }
                }
            }
        });
        <?php else: ?>
        const radarContainer = document.getElementById('radarChartContainer');
        if(radarContainer){
            radarContainer.innerHTML = '<p class="text-center"><em>Belum ada data penilaian P5 yang cukup untuk menampilkan ringkasan profil dimensi siswa.</em></p>';
        }
        <?php endif; ?>
    });
</script>
<?= $this->endSection() ?>
