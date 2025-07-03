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
                    <div class="form-group col-md-4">
                        <label for="status">Filter Status (untuk tabel)</label>
                        <select class="form-control" id="status" name="status">
                            <option value="ALL" <?= ($selected_status == 'ALL') ? 'selected' : ''; ?>>Semua Status</option>
                            <?php
                            // Assuming $status_map is passed from controller: ['1' => 'Hadir', '2' => 'Sakit', ...]
                            // Or use the static method directly if not passed (but passing is cleaner)
                            $statuses = $status_map ?? \App\Models\AttendanceModel::getStatusMap();
                            $statusChars = ['H' => \App\Models\AttendanceModel::STATUS_HADIR,
                                            'S' => \App\Models\AttendanceModel::STATUS_SAKIT,
                                            'I' => \App\Models\AttendanceModel::STATUS_IZIN,
                                            'A' => \App\Models\AttendanceModel::STATUS_ALFA];
                            ?>
                            <?php foreach ($statuses as $code => $name) : ?>
                            <?php
                                // Find the character key for the numeric code for selection check
                                $charKeyForSelect = array_search($code, $statusChars);
                            ?>
                                <option value="<?= esc($code, 'attr'); ?>" <?= ($selected_status == $code) ? 'selected' : ''; ?>>
                                    <?= esc($name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-text text-muted">Filter status ini hanya berlaku untuk data tabel, tidak untuk visualisasi kalender/grafik.</small>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Tampilkan Rekap</button>
                <a href="<?= current_url(); ?>" class="btn btn-secondary">Reset Filter</a>
            </form>
        </div>
    </div>

    <!-- Visualizations: Calendar and Chart -->
    <?php if ($selected_class_id && !empty($daily_summary_for_visuals)) : ?>
        <div class="row">
            <div class="col-lg-7 mb-4">
                <div class="card shadow">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Kalender Presensi Kelas</h6>
                    </div>
                    <div class="card-body">
                        <div id="attendanceCalendar"></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-5 mb-4">
                <div class="card shadow">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Grafik Tren Kehadiran Harian (%)</h6>
                    </div>
                    <div class="card-body">
                        <canvas id="attendanceTrendChart" style="height: 300px;"></canvas>
                    </div>
                </div>
            </div>
        </div>
    <?php elseif ($selected_class_id && empty($this->request->getGet('date_from'))): ?>
        <!-- No action, initial load for a class, visuals will load after filter application -->
    <?php elseif ($selected_class_id) : ?>
         <div class="alert alert-info">Tidak ada data presensi (per jam pelajaran) untuk visualisasi pada rentang tanggal dan kelas yang dipilih.</div>
    <?php endif; ?>

    <!-- Display General Daily Attendance Summary if available -->
    <?php if ($selected_class_id && !empty($daily_general_attendance_summary)) : ?>
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Ringkasan Absensi Harian Umum (Kelas: <?= esc($available_classes[array_search($selected_class_id, array_column($available_classes, 'id'))]['class_name'] ?? 'N/A') ?>)</h6>
        </div>
        <div class="card-body">
            <p>Berikut adalah ringkasan absensi harian umum untuk kelas yang dipilih pada rentang tanggal <?= esc(date('d M Y', strtotime($date_from))) ?> s/d <?= esc(date('d M Y', strtotime($date_to))) ?>. Data ini dicatat oleh Admin/Staf TU.</p>
            <div id="generalAttendanceCalendar"></div>
            <?php /*
            <table class="table table-sm table-bordered">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Hadir</th>
                        <th>Sakit</th>
                        <th>Izin</th>
                        <th>Alfa</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $sorted_general_dates = array_keys($daily_general_attendance_summary);
                sort($sorted_general_dates);
                ?>
                <?php foreach ($sorted_general_dates as $date) :
                    $summary = $daily_general_attendance_summary[$date]; ?>
                    <tr>
                        <td><?= esc(date('d M Y', strtotime($date))) ?></td>
                        <td><?= esc($summary['H'] ?? 0) ?></td>
                        <td><?= esc($summary['S'] ?? 0) ?></td>
                        <td><?= esc($summary['I'] ?? 0) ?></td>
                        <td><?= esc($summary['A'] ?? 0) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            */ ?>
        </div>
    </div>
    <?php elseif ($selected_class_id) : ?>
        <div class="alert alert-info">Tidak ada data absensi harian umum yang tercatat untuk kelas dan rentang tanggal ini.</div>
    <?php endif; ?>


    <!-- Rekap Data Table -->
    <?php if (!empty($recap_data)) : ?>
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Tabel Detail Rekapitulasi Presensi</h6>
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

    <?php if ($selected_class_id && !empty($daily_summary_for_visuals)) : ?>
    // Load FullCalendar and Chart.js
    // Ensure these are loaded. If they are in admin_default.php globally, this is not needed.
    // Otherwise, load them here. For simplicity, assuming they might not be global yet.
    // Chart.js is often loaded globally for other charts. FullCalendar might be specific.
    </script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js'></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // FullCalendar
        var calendarEl = document.getElementById('attendanceCalendar');
        if (calendarEl) {
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek'
                },
                events: function(fetchInfo, successCallback, failureCallback) {
                    // Convert daily_summary_for_visuals to FullCalendar events
                    // Example: highlight days with low attendance or show counts
                    let events = [];
                    const dailyData = <?= json_encode($daily_summary_for_visuals) ?>;
                    for (const date in dailyData) {
                        const dayInfo = dailyData[date];
                        let title = `H: ${dayInfo.H}, A: ${dayInfo.A}, I: ${dayInfo.I}, S: ${dayInfo.S}`;
                        let eventColor = '#28a745'; // Green for good attendance by default
                        if (dayInfo.A > 0) eventColor = '#dc3545'; // Red if any Alfa
                        else if (dayInfo.I > 0 || dayInfo.S > 0) eventColor = '#ffc107'; // Yellow for Izin/Sakit

                        events.push({
                            title: title,
                            start: date,
                            allDay: true,
                            backgroundColor: eventColor,
                            borderColor: eventColor,
                            // extendedProps: dayInfo // Optional: pass full day data
                        });
                    }
                    successCallback(events);
                },
                eventDidMount: function(info) {
                    // Optional: Add tooltip using Bootstrap's tooltip or Tippy.js
                    // Example with Bootstrap tooltip (requires Popper.js)
                    if (info.event.title) {
                        var tooltip = new bootstrap.Tooltip(info.el, {
                            title: info.event.title,
                            placement: 'top',
                            trigger: 'hover',
                            container: 'body'
                        });
                    }
                },
                // dateClick: function(info) {
                //    alert('Clicked on: ' + info.dateStr);
                //    // Potentially load details for this day
                // }
            });
            calendar.render();
        }

        // Chart.js - Line Chart for Attendance Trend
        var trendChartEl = document.getElementById('attendanceTrendChart');
        if (trendChartEl) {
            const dailyDataForChart = <?= json_encode($daily_summary_for_visuals) ?>;
            let labels = [];
            let percentageData = [];

            // Sort keys (dates) to ensure chart is in chronological order
            let sortedDates = Object.keys(dailyDataForChart).sort();

            sortedDates.forEach(date => {
                labels.push(new Date(date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' })); // Format date
                percentageData.push(dailyDataForChart[date].percentage_hadir_on_day || 0);
            });

            new Chart(trendChartEl, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: '% Kehadiran Harian',
                        data: percentageData,
                        fill: false,
                        borderColor: 'rgb(75, 192, 192)',
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            title: { display: true, text: 'Persentase Kehadiran (%)' }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ' + context.parsed.y + '%';
                                }
                            }
                        }
                    }
                }
            });
        }
    });

    // FullCalendar for General Daily Attendance
    var generalCalendarEl = document.getElementById('generalAttendanceCalendar');
    if (generalCalendarEl && typeof FullCalendar !== 'undefined' && <?= !empty($daily_general_attendance_summary) ? 'true' : 'false' ?>) {
        const generalDailyData = <?= json_encode($daily_general_attendance_summary) ?>;
        let generalEvents = [];
        for (const date in generalDailyData) {
            const dayInfo = generalDailyData[date];
            let title = `H: ${dayInfo.H}, A: ${dayInfo.A}, I: ${dayInfo.I}, S: ${dayInfo.S}`;
            let eventColor = '#0275d8'; // Blue for general daily by default
            if (dayInfo.A > 0) eventColor = '#d9534f'; // Red if any Alfa
            else if (dayInfo.I > 0 || dayInfo.S > 0) eventColor = '#f0ad4e'; // Yellow for Izin/Sakit
            else if (dayInfo.H > 0) eventColor = '#5cb85c'; // Green if all Hadir

            generalEvents.push({
                title: title,
                start: date,
                allDay: true,
                backgroundColor: eventColor,
                borderColor: eventColor,
            });
        }

        var generalCalendar = new FullCalendar.Calendar(generalCalendarEl, {
            initialView: 'dayGridMonth',
            locale: 'id',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth'
            },
            events: generalEvents,
            eventDidMount: function(info) {
                if (info.event.title) {
                    var tooltip = new bootstrap.Tooltip(info.el, {
                        title: info.event.title,
                        placement: 'top',
                        trigger: 'hover',
                        container: 'body'
                    });
                }
            },
        });
        generalCalendar.render();
    }
    <?php endif; ?>
</script>
<?= $this->endSection() ?>
