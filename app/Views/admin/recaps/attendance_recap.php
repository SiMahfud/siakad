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
            <h6 class="m-0 font-weight-bold text-primary">Filter Data Presensi</h6>
        </div>
        <div class="card-body">
            <form method="get" action="<?= current_url(); ?>">
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label for="date_from">Dari Tanggal</label>
                        <input type="date" class="form-control" id="date_from" name="date_from" value="<?= esc($date_from ?? '', 'attr'); ?>" required>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="date_to">Sampai Tanggal</label>
                        <input type="date" class="form-control" id="date_to" name="date_to" value="<?= esc($date_to ?? '', 'attr'); ?>" required>
                    </div>
                    <?php if ($user_is_admin_or_staff || has_role('Kepala Sekolah')) : ?>
                        <div class="form-group col-md-4">
                            <label for="class_id">Kelas</label>
                            <select class="form-control" id="class_id" name="class_id">
                                <option value="">-- Semua Kelas --</option>
                                <?php foreach ($available_classes as $class) : ?>
                                    <option value="<?= esc($class['id'], 'attr'); ?>" <?= ($selected_class_id == $class['id']) ? 'selected' : ''; ?>>
                                        <?= esc($class['class_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php elseif (has_role('Guru')) : // Wali Kelas View ?>
                        <input type="hidden" name="class_id" value="<?= esc($selected_class_id ?? ($available_classes[0]['id'] ?? ''), 'attr'); ?>">
                         <div class="form-group col-md-4">
                            <label for="class_id_display">Kelas</label>
                            <select class="form-control" id="class_id_display" name="class_id_display" onchange="document.querySelector('input[name=class_id]').value = this.value; this.form.submit();">
                                <?php if (count($available_classes) > 1) : ?>
                                <option value="">-- Pilih Kelas --</option>
                                <?php endif; ?>
                                <?php foreach ($available_classes as $class) : ?>
                                    <option value="<?= esc($class['id'], 'attr'); ?>" <?= ($selected_class_id == $class['id']) ? 'selected' : ''; ?>>
                                        <?= esc($class['class_name']); ?> Wali: <?= esc($class['wali_kelas_name'] ?? 'N/A'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                             <script>
                                // Ensure the hidden class_id is set on load if a display class is selected
                                document.addEventListener('DOMContentLoaded', function() {
                                    var displayedClassSelect = document.getElementById('class_id_display');
                                    if (displayedClassSelect && displayedClassSelect.value) {
                                        document.querySelector('input[name=class_id]').value = displayedClassSelect.value;
                                    }
                                });
                            </script>
                        </div>
                    <?php endif; ?>
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
                <h6 class="m-0 font-weight-bold text-primary">Hasil Rekapitulasi</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="recapTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>NIS</th>
                                <th>Nama Siswa</th>
                                <th>Kelas</th>
                                <th>Hadir (H)</th>
                                <th>Izin (I)</th>
                                <th>Sakit (S)</th>
                                <th>Alfa (A)</th>
                                <th>Hari Efektif Tercatat</th>
                                <th>% Hadir</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; ?>
                            <?php foreach ($recap_data as $row) : ?>
                                <tr>
                                    <td><?= $no++; ?></td>
                                    <td><?= esc($row['nis'] ?? 'N/A'); ?></td>
                                    <td><?= esc($row['full_name']); ?></td>
                                    <td><?= esc($row['class_name']); ?></td>
                                    <td><?= esc($row['total_hadir']); ?></td>
                                    <td><?= esc($row['total_izin']); ?></td>
                                    <td><?= esc($row['total_sakit']); ?></td>
                                    <td><?= esc($row['total_alfa']); ?></td>
                                    <td><?= esc($row['total_days_for_percentage']); ?></td>
                                    <td><?= number_format(esc($row['percentage_hadir']), 2); ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php elseif (isset($message)) : ?>
        <div class="alert alert-info"><?= esc($message); ?></div>
    <?php elseif (!empty($this->request->getGet('date_from'))) : ?>
         <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Hasil Rekapitulasi</h6>
            </div>
            <div class="card-body">
                 <p>Tidak ada data presensi yang ditemukan untuk filter yang dipilih.</p>
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
        $('#recapTable').DataTable({
            dom: 'Bfrtip', // Add 'B' for buttons
            buttons: [
                { extend: 'copy', className: 'btn btn-secondary btn-sm' },
                { extend: 'csv', className: 'btn btn-secondary btn-sm' },
                { extend: 'excel', className: 'btn btn-secondary btn-sm' },
                { extend: 'pdf', className: 'btn btn-secondary btn-sm' },
                { extend: 'print', className: 'btn btn-secondary btn-sm' }
            ],
            "order": [[3, "asc"], [2, "asc"]] // Default sort by Class, then Name
        });

        <?php if (has_role('Guru') && !(has_role('Administrator Sistem') || has_role('Staf Tata Usaha') || has_role('Kepala Sekolah'))) : ?>
        // Logic for Wali Kelas: if they change the displayed class, submit the form.
        // The hidden input 'class_id' is already updated by the onchange event in the select.
        // This script part might be redundant if onchange="this.form.submit()" is used,
        // but kept for clarity or future complex interactions.
        var classSelect = document.getElementById('class_id_display');
        if(classSelect) {
            // Ensure the hidden input is populated on load based on the select
            var hiddenClassIdInput = document.querySelector('input[name="class_id"]');
            if(hiddenClassIdInput && classSelect.value) {
                 hiddenClassIdInput.value = classSelect.value;
            }

            classSelect.addEventListener('change', function() {
                var hiddenInput = document.querySelector('input[name="class_id"]');
                if (hiddenInput) {
                    hiddenInput.value = this.value;
                }
                // Optional: auto-submit form if desired, or let user click "Tampilkan Rekap"
                // this.form.submit();
            });
        }
        <?php endif; ?>
    });
</script>
<?= $this->endSection() ?>
