<?php

namespace App\Controllers\Ortu;

use App\Controllers\BaseController;
use App\Models\AttendanceModel;
use App\Models\StudentModel;
use App\Models\UserModel;

class AttendanceController extends BaseController
{
    protected $helpers = ['auth', 'url', 'form', 'notification', 'setting', 'text'];

    public function selectChild()
    {
        $currentUser = auth()->user();
        if (!$currentUser || !has_role('Orang Tua')) {
            return redirect()->to('/unauthorized-access');
        }

        $studentModel = new StudentModel();
        $children = $studentModel->where('parent_user_id', $currentUser->id)->findAll();

        if (empty($children)) {
            session()->setFlashdata('error', 'Tidak ada data anak yang terhubung dengan akun Anda.');
            return redirect()->to('/'); // Or ortu dashboard if exists
        }

        if (count($children) === 1) {
            return redirect()->to('ortu/absensi/anak/' . $children[0]['id']);
        }

        $data = [
            'title' => 'Pilih Anak untuk Melihat Rekap Absensi',
            'children' => $children,
        ];
        return view('ortu/attendance/select_child', $data);
    }

    public function viewChildAttendance($studentId = null)
    {
        $currentUser = auth()->user();
        if (!$currentUser || !has_role('Orang Tua')) {
            return redirect()->to('/unauthorized-access');
        }

        if ($studentId === null) {
            // If no studentId is provided, and they only have one child, redirect to that child's recap.
            // Otherwise, redirect to child selection.
            $studentModel = new StudentModel();
            $children = $studentModel->where('parent_user_id', $currentUser->id)->findAll();
            if (count($children) === 1) {
                return redirect()->to('ortu/absensi/anak/' . $children[0]['id']);
            }
            return redirect()->to('ortu/absensi');
        }


        $studentModel = new StudentModel();
        $student = $studentModel->find($studentId);

        // Validate if the student belongs to the logged-in parent
        if (!$student || $student['parent_user_id'] != $currentUser->id) {
            session()->setFlashdata('error', 'Anda tidak memiliki akses ke data absensi siswa ini.');
            return redirect()->to('ortu/absensi');
        }

        $attendanceModel = new AttendanceModel();

        $dateFrom = $this->request->getGet('date_from') ?? date('Y-m-01');
        $dateTo = $this->request->getGet('date_to') ?? date('Y-m-t');
        $statusFilter = []; // Show all by default for parent view

        $attendanceData = $attendanceModel->getStudentAttendanceSummary($student['id'], $dateFrom, $dateTo, $statusFilter);
        // This $dailyStatusData is for per-hour/schedule based attendance calendar
        $dailyStatusDataSchedule = $attendanceModel->getDailyAttendanceStatusForStudent($student['id'], $dateFrom, $dateTo);

        // Get general daily attendance for the student
        $dailyAttendanceModel = new \App\Models\DailyAttendanceModel(); // Instantiate DailyAttendanceModel
        $generalDailyAttendanceRaw = $dailyAttendanceModel->getStudentDailyAttendanceRange($student['id'], $dateFrom, $dateTo);
        $dailyStatusDataGeneral = [];
        $statusCharsMap = \App\Models\DailyAttendanceModel::getStatusCharMap();
        foreach($generalDailyAttendanceRaw as $row){
            $dailyStatusDataGeneral[$row['attendance_date']] = $statusCharsMap[$row['status']] ?? '?';
        }

        $statusMap = AttendanceModel::getStatusMap();
        foreach($attendanceData as &$row){ // For per-hour data
            if(isset($statusMap[$row['status']])){
                $row['status_text'] = $statusMap[$row['status']];
            } else {
                $row['status_text'] = 'Tidak Diketahui';
            }
        }
        unset($row);

        $data = [
            'title' => 'Rekap Absensi Anak: ' . esc($student['full_name']),
            'student' => $student,
            'attendanceData' => $attendanceData, // Per-hour/schedule details
            'dailyStatusDataSchedule' => $dailyStatusDataSchedule, // For per-hour/schedule FullCalendar
            'dailyStatusDataGeneral' => $dailyStatusDataGeneral, // For general daily FullCalendar
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'statusMap' => $statusMap,
             'selected_student_id_for_url' => $studentId, // For form action if needed
        ];

        return view('ortu/attendance/child_recap', $data);
    }
}
