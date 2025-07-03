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

    <!-- Filter Form -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filter Data Pilihan Mata Pelajaran</h6>
        </div>
        <div class="card-body">
            <form method="get" action="<?= current_url(); ?>">
                <div class="form-row">
                    <div class="form-group col-md-3">
                        <label for="academic_year">Tahun Ajaran</label>
                        <select class="form-control" id="academic_year" name="academic_year">
                            <option value="">-- Semua --</option>
                            <?php foreach ($academic_years as $ay) : ?>
                                <option value="<?= esc($ay['academic_year'], 'attr'); ?>" <?= ($selected_academic_year == $ay['academic_year']) ? 'selected' : ''; ?>>
                                    <?= esc($ay['academic_year']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="semester">Semester</label>
                        <select class="form-control" id="semester" name="semester">
                            <option value="">-- Semua --</option>
                            <?php foreach ($semesters as $sem) : ?>
                                <option value="<?= esc($sem['semester'], 'attr'); ?>" <?= ($selected_semester == $sem['semester']) ? 'selected' : ''; ?>>
                                    <?= ($sem['semester'] == 1) ? 'Ganjil' : (($sem['semester'] == 2) ? 'Genap' : esc($sem['semester'])); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="subject_id">Mata Pelajaran</label>
                        <select class="form-control" id="subject_id" name="subject_id">
                            <option value="">-- Semua Mapel --</option>
                            <?php foreach ($subjects as $subject) : ?>
                                <option value="<?= esc($subject['id'], 'attr'); ?>" <?= ($selected_subject_id == $subject['id']) ? 'selected' : ''; ?>>
                                    <?= esc($subject['subject_name']); ?> (<?= esc($subject['subject_code']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group col-md-3 d-flex align-items-end">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="1" id="include_student_names" name="include_student_names" <?= $include_student_names ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="include_student_names">
                                Sertakan Nama Siswa
                            </label>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Tampilkan Rekap</button>
                <a href="<?= current_url(); ?>" class="btn btn-secondary">Reset Filter</a>
            </form>
        </div>
    </div>

    <!-- Rekap Data Table -->
    <?php if (!empty($recap_data)) : ?>
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Hasil Rekapitulasi Pilihan Mata Pelajaran</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="recapSubjectChoiceTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Mata Pelajaran</th>
                                <th>Kode</th>
                                <th>Deskripsi Penawaran</th>
                                <th>Tahun Ajaran</th>
                                <th>Semester</th>
                                <th>Kuota Maks.</th>
                                <th>Jumlah Peminat</th>
                                <th>Sisa Kuota</th>
                                <?php if ($include_student_names) : ?>
                                    <th>Siswa Pemilih</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; ?>
                            <?php foreach ($recap_data as $row) : ?>
                                <tr>
                                    <td><?= $no++; ?></td>
                                    <td><?= esc($row['subject_name']); ?></td>
                                    <td><?= esc($row['subject_code']); ?></td>
                                    <td><?= esc($row['offering_description'] ?? '-'); ?></td>
                                    <td><?= esc($row['academic_year']); ?></td>
                                    <td><?= ($row['semester'] == 1) ? 'Ganjil' : (($row['semester'] == 2) ? 'Genap' : esc($row['semester'])); ?></td>
                                    <td><?= esc($row['max_quota'] ?? '-'); ?></td>
                                    <td><?= esc($row['number_of_choosers']); ?></td>
                                    <td><?= esc($row['remaining_quota'] ?? '-'); ?></td>
                                    <?php if ($include_student_names) : ?>
                                        <td>
                                            <?php if (!empty($row['students_list'])) : ?>
                                                <ul>
                                                    <?php foreach ($row['students_list'] as $student) : ?>
                                                        <li><?= esc($student['full_name']); ?> (NIS: <?= esc($student['nis'] ?? 'N/A'); ?>, Kelas: <?= esc($student['class_name'] ?? 'N/A'); ?>)</li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            <?php else : ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php elseif (isset($message)) : ?>
        <div class="alert alert-info"><?= esc($message); ?></div>
    <?php elseif (!empty($selected_academic_year) && !empty($selected_semester)) : ?>
         <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Hasil Rekapitulasi</h6>
            </div>
            <div class="card-body">
                 <p>Tidak ada data pilihan mata pelajaran yang ditemukan untuk filter yang dipilih.</p>
            </div>
        </div>
    <?php endif; ?>

</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<!-- Page level plugins -->
<script src="<?= base_url('vendor/datatables/jquery.dataTables.min.js') ?>"></script>
<script src="<?= base_url('vendor/datatables/dataTables.bootstrap4.min.js') ?>"></script>
<!-- Buttons extension -->
<script src="https://cdn.datatables.net/buttons/2.2.3/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.print.min.js"></script>

<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.2.3/css/buttons.dataTables.min.css">

<script>
    $(document).ready(function() {
        $('#recapSubjectChoiceTable').DataTable({
            dom: 'Bfrtip',
            buttons: [
                { extend: 'copy', className: 'btn btn-secondary btn-sm' },
                { extend: 'csv', className: 'btn btn-secondary btn-sm', title: 'Rekap Pilihan Mata Pelajaran' },
                { extend: 'excel', className: 'btn btn-secondary btn-sm', title: 'Rekap Pilihan Mata Pelajaran' },
                { extend: 'pdf', className: 'btn btn-secondary btn-sm', title: 'Rekap Pilihan Mata Pelajaran', orientation: 'landscape' },
                { extend: 'print', className: 'btn btn-secondary btn-sm', title: 'Rekap Pilihan Mata Pelajaran' }
            ],
            "order": [[1, "asc"], [4, "desc"], [5, "asc"]] // Default sort by Subject Name, then Academic Year, then Semester
        });
    });
</script>
<?= $this->endSection() ?>
