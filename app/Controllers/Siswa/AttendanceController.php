<?php

namespace App\Controllers\Siswa;

use App\Controllers\BaseController;
use App\Models\AttendanceModel;
use App\Models\StudentModel;

class AttendanceController extends BaseController
{
    protected $helpers = ['auth', 'url', 'form', 'notification', 'setting', 'text']; // Added text for character_limiter

    public function myAttendance()
    {
        $currentUser = auth()->user();
        if (!$currentUser || !has_role('Siswa')) {
            return redirect()->to('/unauthorized-access');
        }

        $studentModel = new StudentModel();
        $student = $studentModel->where('user_id', $currentUser->id)->first();

        if (!$student) {
            session()->setFlashdata('error', 'Data siswa tidak ditemukan untuk pengguna ini.');
            return redirect()->to('/'); // Redirect to home or student dashboard if exists
        }

        $attendanceModel = new AttendanceModel();
        $dailyAttendanceModel = new \App\Models\DailyAttendanceModel(); // Added

        // Default date range: current month
        $dateFrom = $this->request->getGet('date_from') ?? date('Y-m-01');
        $dateTo = $this->request->getGet('date_to') ?? date('Y-m-t');

        // Status filter (optional, if you want to allow siswa to filter their view)
        // For now, let's show all. If a filter is added, it should be an array of status codes.
        // $statusFilter = $this->request->getGet('status') ? [$this->request->getGet('status')] : [];
        $statusFilter = []; // Show all by default

        $attendanceData = $attendanceModel->getStudentAttendanceSummary($student['id'], $dateFrom, $dateTo, $statusFilter);
        // This $dailyStatusData is for per-hour/schedule based attendance calendar
        $dailyStatusDataSchedule = $attendanceModel->getDailyAttendanceStatusForStudent($student['id'], $dateFrom, $dateTo);

        // Get general daily attendance for the student
        $generalDailyAttendanceRaw = $dailyAttendanceModel->getStudentDailyAttendanceRange($student['id'], $dateFrom, $dateTo);
        $dailyStatusDataGeneral = [];
        $statusCharsMap = \App\Models\DailyAttendanceModel::getStatusCharMap();
        foreach($generalDailyAttendanceRaw as $row){
            $dailyStatusDataGeneral[$row['attendance_date']] = $statusCharsMap[$row['status']] ?? '?';
        }

        $statusMap = AttendanceModel::getStatusMap(); // Get [1 => 'Hadir', 2 => 'Sakit', ...]

        // Map numeric status in attendanceData (per-hour) to text status
        foreach($attendanceData as &$row){
            if(isset($statusMap[$row['status']])){
                $row['status_text'] = $statusMap[$row['status']];
            } else {
                $row['status_text'] = 'Tidak Diketahui';
            }
        }
        unset($row);


        $data = [
            'title' => 'Rekap Absensi Saya',
            'student' => $student,
            'attendanceData' => $attendanceData, // Per-hour/schedule details
            'dailyStatusDataSchedule' => $dailyStatusDataSchedule, // For per-hour/schedule FullCalendar
            'dailyStatusDataGeneral' => $dailyStatusDataGeneral, // For general daily FullCalendar
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'statusMap' => $statusMap,
            'selected_status' => $this->request->getGet('status') ?? 'ALL',
        ];

        return view('siswa/attendance/my_recap', $data);
    }
}
