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

        // Default date range: current month
        $dateFrom = $this->request->getGet('date_from') ?? date('Y-m-01');
        $dateTo = $this->request->getGet('date_to') ?? date('Y-m-t');

        // Status filter (optional, if you want to allow siswa to filter their view)
        // For now, let's show all. If a filter is added, it should be an array of status codes.
        // $statusFilter = $this->request->getGet('status') ? [$this->request->getGet('status')] : [];
        $statusFilter = []; // Show all by default

        $attendanceData = $attendanceModel->getStudentAttendanceSummary($student['id'], $dateFrom, $dateTo, $statusFilter);
        $dailyStatusData = $attendanceModel->getDailyAttendanceStatusForStudent($student['id'], $dateFrom, $dateTo);

        $statusMap = AttendanceModel::getStatusMap(); // Get [1 => 'Hadir', 2 => 'Sakit', ...]

        // Map numeric status in attendanceData to text status
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
            'attendanceData' => $attendanceData,
            'dailyStatusData' => $dailyStatusData, // For FullCalendar
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'statusMap' => $statusMap, // For filter dropdown if added
            'selected_status' => $this->request->getGet('status') ?? 'ALL', // If filter added
        ];

        return view('siswa/attendance/my_recap', $data);
    }
}
