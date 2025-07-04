<?= $this->extend('layouts/admin_default') // Or a specific parent layout if created ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= esc($pageTitle ?? 'Transkrip Nilai Sementara Anak') ?></h1>
        <?php if(isset($childrenCount) && $childrenCount > 1): // Show back button if parent has more than one child ?>
             <a href="<?= route_to('ortu_nilai_index') ?>" class="btn btn-sm btn-secondary shadow-sm">
                <i class="bi bi-arrow-left"></i> Kembali ke Pilih Siswa
            </a>
        <?php endif; ?>
    </div>

    <?php if (session()->getFlashdata('info')) : ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <?= session()->getFlashdata('info') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')) : ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= session()->getFlashdata('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if ($student && $currentClass) : ?>
        <div class="mb-3">
            <p><strong>Nama Anak:</strong> <?= esc($student['full_name']) ?></p>
            <p><strong>NISN:</strong> <?= esc($student['nisn'] ?? 'N/A') ?></p>
            <p><strong>Kelas:</strong> <?= esc($currentClass['class_name']) ?> (T.A. <?= esc($currentClass['academic_year']) ?>)</p>
        </div>
        <hr>

        <?php if (!empty($mapelDenganNilai)) : ?>
            <?php foreach ($mapelDenganNilai as $item) : ?>
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="bi bi-book-fill"></i> <?= esc($item['subject_info']['subject_name']) ?>
                            (<?= esc($item['subject_info']['subject_code']) ?>)
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($item['assessments'])) : ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped table-sm datatable-recap-ortu" id="recapTableOrtu_<?= esc($student['id']) ?>_<?= esc($item['subject_info']['subject_id']) ?>" width="100%" cellspacing="0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Tanggal</th>
                                            <th>Judul Asesmen</th>
                                            <th>Tipe</th>
                                            <th>Skor</th>
                                            <th>Deskripsi/Catatan</th>
                                        </tr>
                                    </thead>
                                    <tfoot>
                                        <tr>
                                            <th>Tanggal</th>
                                            <th>Judul Asesmen</th>
                                            <th>Tipe</th>
                                            <th>Skor</th>
                                            <th>Deskripsi/Catatan</th>
                                        </tr>
                                    </tfoot>
                                    <tbody>
                                        <?php foreach ($item['assessments'] as $assessment) : ?>
                                            <tr>
                                                <td><?= esc(date('d M Y', strtotime($assessment['assessment_date']))) ?></td>
                                                <td><?= esc($assessment['assessment_title']) ?></td>
                                                <td>
                                                    <span class="badge bg-<?= $assessment['assessment_type'] === 'SUMATIF' ? 'success' : 'info' ?>">
                                                        <?= esc(ucfirst(strtolower($assessment['assessment_type']))) ?>
                                                    </span>
                                                </td>
                                                <td><?= $assessment['assessment_type'] === 'SUMATIF' ? esc($assessment['score'] ?? '-') : '-' ?></td>
                                                <td><?= nl2br(esc($assessment['description'] ?? '-')) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else : ?>
                            <p class="text-muted">Belum ada data penilaian untuk mata pelajaran ini.</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else : ?>
            <div class="alert alert-info">
                Belum ada mata pelajaran yang terdaftar atau belum ada penilaian untuk anak Anda di kelas ini.
            </div>
        <?php endif; ?>

    <?php elseif ($student && !$currentClass) : ?>
         <!-- Handled by flashdata 'info' from controller if student not in class -->
         <p>Silakan hubungi administrator sekolah jika anak Anda seharusnya sudah terdaftar di sebuah kelas.</p>
    <?php endif; ?>

</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<style>
    .card-header h6 {
        display: flex;
        align-items: center;
    }
    .card-header .bi {
        margin-right: 0.5rem;
    }
    .table-sm td, .table-sm th {
        padding: 0.4rem;
        vertical-align: middle;
    }
    .dataTables_filter {
        margin-bottom: 0.5rem;
    }
</style>
<script>
    $(document).ready(function() {
        $('.datatable-recap-ortu').each(function() {
            $(this).DataTable({
                "responsive": true,
                "lengthChange": true,
                "autoWidth": false,
                "pageLength": 5,
                "lengthMenu": [ [5, 10, 25, -1], [5, 10, 25, "All"] ],
                "dom": 'Bfrtip', // Add Buttons
                "buttons": [
                    'copy', 'csv', 'excel', 'pdf', 'print'
                ],
                initComplete: function () {
                    this.api().columns().every(function (colIdx) {
                        var column = this;
                        var title = $(column.header()).text();
                        var footerCell = $(column.footer()).empty();

                        // For "Tipe" column (index 2), use a select filter
                        if (colIdx === 2) {
                            var select = $('<select class="form-select form-select-sm"><option value="">All</option></select>')
                                .appendTo(footerCell)
                                .on('change', function () {
                                    var val = $.fn.dataTable.util.escapeRegex($(this).val());
                                    column.search(val ? '^' + val + '$' : '', true, false).draw();
                                });
                            select.append('<option value="Formatif">Formatif</option>');
                            select.append('<option value="Sumatif">Sumatif</option>');
                        } else { // For other columns
                            var input = $('<input type="text" class="form-control form-control-sm" placeholder="Filter ' + title + '" />')
                                .appendTo(footerCell)
                                .on('keyup change clear', function () {
                                    if (column.search() !== this.value) {
                                        column.search(this.value).draw();
                                    }
                                });
                        }
                    });
                },
                "language": {
                    "search": "Filter:",
                    "lengthMenu": "Show _MENU_ entries",
                    "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                    "infoEmpty": "Showing 0 to 0 of 0 entries",
                    "infoFiltered": "(filtered from _MAX_ total entries)",
                    "paginate": {
                        "first": "First",
                        "last": "Last",
                        "next": "Next",
                        "previous": "Previous"
                    }
                }
            });
        });
    });
</script>
<?= $this->endSection() ?>
