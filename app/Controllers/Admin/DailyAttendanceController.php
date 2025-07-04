<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\DailyAttendanceModel;
use App\Models\ClassModel;
use App\Models\StudentModel;

class DailyAttendanceController extends BaseController
{
    protected $dailyAttendanceModel;
    protected $classModel;
    protected $studentModel;
    protected $helpers = ['form', 'url', 'auth', 'setting'];

    public function __construct()
    {
        helper('auth'); // Ensure auth helper is loaded here
        $this->dailyAttendanceModel = new DailyAttendanceModel();
        $this->classModel = new ClassModel();
        $this->studentModel = new StudentModel();
    }

    public function index()
    {
        // helper('auth'); // Moved to constructor
        // Check permission - e.g., 'manage_daily_attendance' or specific roles
        // if (!has_role(['Administrator Sistem', 'Staf Tata Usaha'])) { // Example roles
        //     return redirect()->to('/unauthorized');
        // }

        $selectedClassId = $this->request->getGet('class_id');
        $selectedDate = $this->request->getGet('date') ?? date('Y-m-d');

        $data = [
            'title' => 'Input Absensi Harian Umum',
            'classes' => $this->classModel->orderBy('class_name', 'ASC')->findAll(),
            'selected_class_id' => $selectedClassId,
            'selected_date' => $selectedDate,
            'students' => [],
            'existing_attendance' => [],
            'status_map' => DailyAttendanceModel::getStatusMap()
        ];

        if ($selectedClassId && $selectedDate) {
            $data['students'] = $this->studentModel
                ->select('students.id, students.nis, students.full_name')
                ->join('class_student cs', 'cs.student_id = students.id')
                ->where('cs.class_id', $selectedClassId)
                ->orderBy('students.full_name', 'ASC')
                ->findAll();

            $rawAttendance = $this->dailyAttendanceModel->getDailyAttendance($selectedClassId, $selectedDate);
            foreach($rawAttendance as $att) {
                $data['existing_attendance'][$att['student_id']] = $att;
            }
        }

        return view('admin/daily_attendance/manage', $data);
    }

    public function save()
    {
        // helper('auth'); // Moved to constructor
        // if (!has_role(['Administrator Sistem', 'Staf Tata Usaha'])) {
        //     return redirect()->to('/unauthorized');
        // }

        $classId = $this->request->getPost('class_id');
        $attendanceDate = $this->request->getPost('attendance_date');
        $attendances = $this->request->getPost('attendance'); // Expects array: [student_id => ['status' => val, 'remarks' => val]]

        if (empty($classId) || empty($attendanceDate) || empty($attendances)) {
            session()->setFlashdata('error', 'Data tidak lengkap. Harap pilih kelas, tanggal, dan isi absensi.');
            return redirect()->back()->withInput();
        }

        // Validate that the class exists
        $class = $this->classModel->find($classId);
        if (!$class) {
            session()->setFlashdata('error', 'Kelas tidak valid.');
            return redirect()->back()->withInput();
        }
        // Basic date validation
        if (!preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $attendanceDate)) {
             session()->setFlashdata('error', 'Format tanggal tidak valid.');
            return redirect()->back()->withInput();
        }

        // $currentUser = auth()->user(); // Assuming this was for Shield/MythAuth like service
        // $recordedByUserId = $currentUser->id;

        // Use current_user_id() from auth_helper.php
        $recordedByUserId = current_user_id();
        if (!$recordedByUserId) {
            session()->setFlashdata('error', 'Sesi pengguna tidak valid atau tidak ditemukan. Silakan login ulang.');
            return redirect()->back()->withInput();
        }


        if ($this->dailyAttendanceModel->saveBulkDailyAttendance($classId, $attendanceDate, $attendances, $recordedByUserId)) {
            session()->setFlashdata('message', 'Absensi harian berhasil disimpan.');
        } else {
            session()->setFlashdata('error', 'Gagal menyimpan absensi harian.');
        }

        // Redirect back to the form with the selected class and date
        return redirect()->to(site_url("admin/daily-attendance?class_id={$classId}&date={$attendanceDate}"));
    }
}
