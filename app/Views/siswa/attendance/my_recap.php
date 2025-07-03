<?= $this->extend('layouts/admin_default') // Or a specific student layout if created ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800"><?= esc($title) ?></h1>
    <p class="mb-4">
        Menampilkan rekap absensi untuk: <strong><?= esc($student['full_name']) ?></strong> (NIS: <?= esc($student['nis'] ?? 'N/A') ?>)
    </p>

    <?php if (session()->getFlashdata('error')) : ?>
        <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
    <?php endif; ?>

    <!-- Filter Form -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filter Periode</h6>
        </div>
        <div class="card-body">
            <form method="get" action="<?= site_url('siswa/absensi') ?>">
                <div class="row">
                    <div class="col-md-5 mb-3">
                        <label for="date_from" class="form-label">Dari Tanggal</label>
                        <input type="date" class="form-control" id="date_from" name="date_from" value="<?= esc($date_from ?? '', 'attr') ?>" required>
                    </div>
                    <div class="col-md-5 mb-3">
                        <label for="date_to" class="form-label">Sampai Tanggal</label>
                        <input type="date" class="form-control" id="date_to" name="date_to" value="<?= esc($date_to ?? '', 'attr') ?>" required>
                    </div>
                    <div class="col-md-2 mb-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Visual Calendars -->
    <div class="row">
        <div class="col-md-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Kalender Absensi Harian Umum</h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($dailyStatusDataGeneral)) : ?>
                        <div id="attendanceCalendarGeneral"></div>
                        <small class="form-text text-muted mt-2">Kalender ini menampilkan status absensi harian umum Anda (dicatat oleh Admin/Staf TU).</small>
                    <?php else : ?>
                        <p class="text-center">Tidak ada data absensi harian umum untuk ditampilkan di kalender pada periode ini.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Kalender Absensi per Jam Pelajaran</h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($dailyStatusDataSchedule)) : ?>
                        <div id="attendanceCalendarSchedule"></div>
                        <small class="form-text text-muted mt-2">Kalender ini menampilkan ringkasan status absensi Anda per jam pelajaran (dicatat oleh Guru Mapel).</small>
                    <?php else : ?>
                        <p class="text-center">Tidak ada data absensi per jam pelajaran untuk ditampilkan di kalender pada periode ini.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>


    <!-- Attendance Table for Per-Hour/Schedule Based -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Detail Absensi per Jam Pelajaran</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="studentAttendanceTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Tanggal</th>
                            <th>Hari</th>
                            <th>Mata Pelajaran</th>
                            <th>Jam</th>
                            <th>Status</th>
                            <th>Keterangan</th>
                            <th>Dicatat Oleh</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($attendanceData)) : ?>
                            <?php $no = 1; ?>
                            <?php foreach ($attendanceData as $att) : ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><?= esc(date('d M Y', strtotime($att['attendance_date']))) ?></td>
                                    <td><?= esc(day_name_id(date('w', strtotime($att['attendance_date'])))) // Helper for day name ?></td>
                                    <td><?= esc($att['subject_name']) ?></td>
                                    <td><?= esc(date('H:i', strtotime($att['start_time']))) ?> - <?= esc(date('H:i', strtotime($att['end_time']))) ?></td>
                                    <td>
                                        <?php
                                        $statusClass = '';
                                        switch ($att['status']) {
                                            case \App\Models\AttendanceModel::STATUS_HADIR: $statusClass = 'badge bg-success'; break;
                                            case \App\Models\AttendanceModel::STATUS_SAKIT: $statusClass = 'badge bg-warning text-dark'; break;
                                            case \App\Models\AttendanceModel::STATUS_IZIN:  $statusClass = 'badge bg-info text-dark'; break;
                                            case \App\Models\AttendanceModel::STATUS_ALFA:  $statusClass = 'badge bg-danger'; break;
                                            default: $statusClass = 'badge bg-secondary'; break;
                                        }
                                        ?>
                                        <span class="<?= $statusClass ?>"><?= esc($att['status_text']) ?></span>
                                    </td>
                                    <td><?= esc($att['remarks'] ?: '-') ?></td>
                                    <td><?= esc($att['recorded_by_name'] ?: 'Sistem') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="8" class="text-center">Tidak ada data absensi pada periode ini.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<!-- DataTables -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

<!-- FullCalendar -->
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js'></script>

<script>
    // Helper function for day name (if not available globally)
    function day_name_id(dayIndex) {
        const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        return days[dayIndex] || '';
    }

    $(document).ready(function() {
        $('#studentAttendanceTable').DataTable({
            responsive: true,
            dom: 'Bfrtip',
            buttons: [
                { extend: 'copy', className: 'btn-sm' },
                { extend: 'csv', className: 'btn-sm' },
                { extend: 'excel', className: 'btn-sm', title: 'Rekap Absensi <?= esc($student['full_name']) ?>' },
                { extend: 'pdf', className: 'btn-sm', title: 'Rekap Absensi <?= esc($student['full_name']) ?>', orientation: 'landscape' },
                { extend: 'print', className: 'btn-sm', title: 'Rekap Absensi <?= esc($student['full_name']) ?>' }
            ],
            order: [[1, 'desc']] // Sort by date descending by default
        });

        // Function to render FullCalendar
        function renderStudentCalendar(elementId, eventData, defaultEmptyText = 'Tidak ada data absensi untuk kalender ini.') {
            var calendarEl = document.getElementById(elementId);
            if (calendarEl && typeof FullCalendar !== 'undefined') {
                if (Object.keys(eventData).length === 0) {
                    // calendarEl.innerHTML = `<p class="text-center text-muted fst-italic">${defaultEmptyText}</p>`;
                    // The PHP part already handles empty message, so this JS part might not be strictly needed if PHP handles it before div creation.
                    // However, this can be a fallback.
                    return;
                }

                let events = [];
                for (const date in eventData) {
                    let statusChar = eventData[date];
                    let eventColor = '';
                    let titleText = '';

                    switch(statusChar) {
                        case 'H': eventColor = 'rgba(40, 167, 69, 0.7)'; titleText = 'Hadir'; break;
                        case 'S': eventColor = 'rgba(255, 193, 7, 0.7)'; titleText = 'Sakit'; break;
                        case 'I': eventColor = 'rgba(23, 162, 184, 0.7)'; titleText = 'Izin'; break;
                        case 'A': eventColor = 'rgba(220, 53, 69, 0.7)'; titleText = 'Alfa'; break;
                        default: eventColor = 'rgba(108, 117, 125, 0.5)'; titleText = 'Tidak Diketahui'; break;
                    }
                    events.push({
                        title: titleText,
                        start: date,
                        allDay: true,
                        backgroundColor: eventColor,
                        borderColor: eventColor.replace(/0\.\d+\)/, '1)'), // Darker border
                        display: 'background'
                    });
                }

                var calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'dayGridMonth',
                    locale: 'id',
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth'
                    },
                    events: events,
                    dayCellDidMount: function(info) {
                        // You can add custom rendering per day cell if needed
                    },
                });
                calendar.render();
            }
        }

        // Render General Daily Attendance Calendar
        const dailyStatusDataGeneralSiswa = <?= json_encode($dailyStatusDataGeneral ?? []) ?>;
        renderStudentCalendar('attendanceCalendarGeneral', dailyStatusDataGeneralSiswa, 'Tidak ada data absensi harian umum untuk ditampilkan di kalender pada periode ini.');

        // Render Per-Hour/Schedule Attendance Calendar
        const dailyStatusDataScheduleSiswa = <?= json_encode($dailyStatusDataSchedule ?? []) ?>;
        renderStudentCalendar('attendanceCalendarSchedule', dailyStatusDataScheduleSiswa, 'Tidak ada data absensi per jam pelajaran untuk ditampilkan di kalender pada periode ini.');

    });
</script>
<?= $this->endSection() ?>
