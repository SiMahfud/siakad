<?php

namespace App\Controllers\Guru;

use App\Controllers\BaseController;
use App\Models\AttendanceModel;
use App\Models\ScheduleModel;
use App\Models\StudentModel;
use App\Models\TeacherModel;
use App\Models\ClassStudentModel;
use App\Models\ClassModel;

class AttendanceController extends BaseController
{
    protected $attendanceModel;
    protected $scheduleModel;
    protected $studentModel;
    protected $teacherModel;
    protected $classStudentModel;
    protected $classModel;
    protected $loggedInTeacherId;

    public function __construct()
    {
        helper(['form', 'url', 'auth', 'date']); // Added 'date' helper for now()
        if (!hasRole(['Guru', 'Administrator Sistem'])) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Unauthorized access attempt.');
        }

        $this->attendanceModel = new AttendanceModel();
        $this->scheduleModel = new ScheduleModel();
        $this->studentModel = new StudentModel();
        $this->teacherModel = new TeacherModel();
        $this->classStudentModel = new ClassStudentModel();
        $this->classModel = new ClassModel();

        $loggedInUserId = current_user_id();
        if ($loggedInUserId) {
            $teacher = $this->teacherModel->where('user_id', $loggedInUserId)->first();
            $this->loggedInTeacherId = $teacher ? $teacher['id'] : null;
        }
    }

    // Step 1: Teacher selects a schedule slot for a specific date
    public function selectSchedule()
    {
        if (!$this->loggedInTeacherId && !isAdmin()) {
             return redirect()->to('/')->with('error', 'Access denied. Teacher information not found.');
        }

        $date = $this->request->getGet('date') ?? date('Y-m-d'); // Default to today
        $dayOfWeek = date('N', strtotime($date)); // 1 (Mon) to 7 (Sun)

        $filters = [
            'teacher_id'    => $this->loggedInTeacherId,
            'day_of_week'   => $dayOfWeek,
            // Potentially filter by active academic year/semester if available globally
            // 'academic_year' => $currentAcademicYear,
            // 'semester'      => $currentSemester,
        ];

        // If admin is accessing and not a teacher, they might need to specify teacher_id
        if (isAdmin() && !$this->loggedInTeacherId && $this->request->getGet('teacher_id_for_admin')) {
            $filters['teacher_id'] = $this->request->getGet('teacher_id_for_admin');
        } elseif (isAdmin() && !$this->loggedInTeacherId && !$this->request->getGet('teacher_id_for_admin')) {
            // Admin accessing without teacher context and without specifying a teacher
            session()->setFlashdata('info', 'Please specify a teacher ID to view their schedules or use your own teacher account.');
            $schedules = []; // Show no schedules
        } else if (!$this->loggedInTeacherId && !isAdmin()){ // Should not happen due to role check
             return redirect()->to('/')->with('error', 'Access denied.');
        }


        $schedules = $this->loggedInTeacherId || (isAdmin() && $filters['teacher_id']) ? $this->scheduleModel->getScheduleDetails($filters) : [];


        $data = [
            'pageTitle' => 'Select Schedule for Attendance',
            'schedules' => $schedules,
            'selectedDate' => $date,
            'dayMap' => [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'],
            'loggedInTeacherId' => $this->loggedInTeacherId, // For view logic if admin
        ];
        return view('guru/attendances/select_schedule', $data);
    }

    // Step 2: Display attendance form for the selected schedule and date
    public function showAttendanceForm()
    {
        $scheduleId = $this->request->getGet('schedule_id');
        $attendanceDate = $this->request->getGet('date'); // Date selected from previous step

        if (empty($scheduleId) || empty($attendanceDate)) {
            return redirect()->to(site_url('guru/attendance/select-schedule'))->with('error', 'Schedule ID or date not provided.');
        }

        $schedule = $this->scheduleModel->getScheduleDetails(['schedules.id' => $scheduleId]); // Use array key for specific ID
        if (empty($schedule)) {
            return redirect()->to(site_url('guru/attendance/select-schedule'))->with('error', 'Schedule not found.');
        }
        $schedule = $schedule[0]; // getScheduleDetails returns an array

        // Authorization: Ensure the logged-in teacher is assigned to this schedule or is an admin
        if (!isAdmin() && $this->loggedInTeacherId && $schedule['teacher_id'] != $this->loggedInTeacherId) {
            return redirect()->to(site_url('guru/attendance/select-schedule'))->with('error', 'You are not authorized to input attendance for this schedule.');
        }

        $students = $this->classStudentModel->getStudentsInClass($schedule['class_id']);
        $existingAttendance = $this->attendanceModel->getAttendanceByScheduleAndDate($scheduleId, $attendanceDate);

        $attendanceData = [];
        foreach ($existingAttendance as $att) {
            $attendanceData[$att['student_id']] = $att;
        }

        $data = [
            'pageTitle' => 'Input Attendance',
            'schedule' => $schedule,
            'students' => $students,
            'attendanceDate' => $attendanceDate,
            'attendanceData' => $attendanceData, // Pre-fill form with existing data
            'statusOptions' => AttendanceModel::getStatusMap(),
            'defaultStatus' => AttendanceModel::STATUS_HADIR,
        ];
        return view('guru/attendances/attendance_form', $data);
    }

    // Step 3: Save attendance data
    public function saveAttendance()
    {
        $scheduleId = $this->request->getPost('schedule_id');
        $attendanceDate = $this->request->getPost('attendance_date');
        $attendancesInput = $this->request->getPost('attendance'); // Array of [student_id => ['status' => val, 'remarks' => val]]

        if (empty($scheduleId) || empty($attendanceDate) || empty($attendancesInput)) {
            return redirect()->back()->withInput()->with('error', 'Required data is missing.');
        }

        $schedule = $this->scheduleModel->find($scheduleId);
        if (!$schedule) {
            return redirect()->back()->withInput()->with('error', 'Schedule not found.');
        }

        // Authorization check
        if (!isAdmin() && $this->loggedInTeacherId && $schedule['teacher_id'] != $this->loggedInTeacherId) {
            return redirect()->to(site_url('guru/attendance/select-schedule'))->with('error', 'You are not authorized to save attendance for this schedule.');
        }

        $recordedBy = $this->loggedInTeacherId ? $this->teacherModel->where('id', $this->loggedInTeacherId)->first()['user_id'] : current_user_id();

        $dataToBatchUpdate = [];
        $dataToBatchInsert = [];

        // Get existing records for comparison to decide insert/update
        $existingDbAttendance = $this->attendanceModel
            ->where('schedule_id', $scheduleId)
            ->where('attendance_date', $attendanceDate)
            ->findAll();
        $existingRecordsMap = [];
        foreach ($existingDbAttendance as $record) {
            $existingRecordsMap[$record['student_id']] = $record['id'];
        }

        foreach ($attendancesInput as $studentId => $data) {
            $status = $data['status'] ?? AttendanceModel::STATUS_HADIR; // Default to Hadir if not set
            $remarks = $data['remarks'] ?? null;

            $record = [
                'schedule_id'     => $scheduleId,
                'student_id'      => $studentId,
                'attendance_date' => $attendanceDate,
                'status'          => $status,
                'remarks'         => $remarks,
                'recorded_by_user_id' => $recordedBy,
            ];

            // Validate each record before adding to batch
            if (!$this->attendanceModel->validate($record)) {
                // Handle validation errors for individual records if necessary,
                // or rely on overall form success/failure.
                // For simplicity, we'll catch general failure.
                // A more granular approach would collect errors per student.
                log_message('error', 'Validation failed for student ' . $studentId . ': ' . json_encode($this->attendanceModel->errors()));
                // Continue to next student or fail fast:
                // return redirect()->back()->withInput()->with('error', 'Validation error for student. Please check data.');
            }

            if (isset($existingRecordsMap[$studentId])) {
                $record['id'] = $existingRecordsMap[$studentId]; // Add ID for update
                $dataToBatchUpdate[] = $record;
            } else {
                $dataToBatchInsert[] = $record;
            }
        }

        $success = true;
        $db = \Config\Database::connect();
        $db->transStart();

        if (!empty($dataToBatchInsert)) {
            if (!$this->attendanceModel->insertBatch($dataToBatchInsert)) {
                $success = false;
                log_message('error', 'Failed to insert batch attendance: ' . json_encode($this->attendanceModel->errors()));
            }
        }
        if ($success && !empty($dataToBatchUpdate)) {
            if (!$this->attendanceModel->updateBatch($dataToBatchUpdate, 'id')) {
                $success = false;
                log_message('error', 'Failed to update batch attendance: ' . json_encode($this->attendanceModel->errors()));
            }
        }

        $db->transComplete();

        if ($success && $db->transStatus() !== false) {
            return redirect()->to(site_url("guru/attendance/form?schedule_id={$scheduleId}&date={$attendanceDate}"))
                             ->with('success', 'Attendance saved successfully.');
        } else {
            $errorMsg = 'Failed to save attendance.';
            if ($db->transStatus() === false) {
                $errorMsg .= ' Database transaction failed.';
            }
            return redirect()->back()->withInput()->with('error', $errorMsg);
        }
    }
}
