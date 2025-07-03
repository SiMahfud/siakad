<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class AttendanceAlertsCheckCommand extends BaseCommand
{
    /**
     * The Command's Group
     *
     * @var string
     */
    protected $group = 'Attendance'; // Changed group for better organization

    /**
     * The Command's Name
     *
     * @var string
     */
    protected $name = 'attendance:checkalerts'; // Corrected command name

    /**
     * The Command's Description
     *
     * @var string
     */
    protected $description = 'Checks student attendance records and generates alerts for high or consecutive absences.';

    /**
     * The Command's Usage
     *
     * @var string
     */
    protected $usage = 'attendance:checkalerts [options]'; // Corrected usage

    /**
     * The Command's Arguments
     *
     * @var array
     */
    protected $arguments = [];

    /**
     * The Command's Options
     *
     * @var array
     */
    protected $options = [ // Options for the command
        '-d', '--date' => 'Optional. Check for alerts up to this date (YYYY-MM-DD). Defaults to today.',
    ];

    // Define thresholds (hardcoded for now, can be moved to config or settings later)
    const THRESHOLD_CONSECUTIVE_ALFA = 3;
    const THRESHOLD_TOTAL_ALFA_DAYS_PERIOD = 30; // Check over the last 30 days
    const THRESHOLD_TOTAL_ALFA_COUNT = 5;
    const THRESHOLD_TOTAL_SICK_LEAVE_DAYS_PERIOD = 30; // Check over the last 30 days
    const THRESHOLD_TOTAL_SICK_LEAVE_COUNT = 7; // Total for 'S' (Sakit) and 'I' (Izin)


    /**
     * Actually execute a command.
     *
     * @param array $params
     */
    public function run(array $params)
    {
        CLI::write('Starting Attendance Alerts Check...', 'green');

        $targetDateStr = CLI::getOption('d') ?? CLI::getOption('date') ?? date('Y-m-d');
        try {
            $targetDate = new \DateTime($targetDateStr);
        } catch (\Exception $e) {
            CLI::error("Invalid date format for --date option. Please use YYYY-MM-DD.");
            return;
        }
        CLI::write("Checking alerts up to: " . $targetDate->format('Y-m-d'));

        $studentModel = new \App\Models\StudentModel();
        $attendanceModel = new \App\Models\AttendanceModel();
        $notificationModel = new \App\Models\NotificationModel(); // Will be created later
        $classModel = new \App\Models\ClassModel(); // To get wali_kelas_id
        $userModel = new \App\Models\UserModel(); // To get user_id for wali_kelas

        $students = $studentModel->select('students.id, students.full_name, students.parent_user_id, cs.class_id')
                                 ->join('class_student cs', 'cs.student_id = students.id', 'left') // Get class_id
                                 ->findAll();

        if (empty($students)) {
            CLI::write('No students found to check.', 'yellow');
            return;
        }

        foreach ($students as $student) {
            CLI::write("Checking student: {$student['full_name']} (ID: {$student['id']})");

            // 1. Check for consecutive 'A' (Alfa)
            // Fetches last N+1 records to ensure we can detect N consecutive 'A's
            $recentAttendances = $attendanceModel
                ->where('student_id', $student['id'])
                ->where('date <=', $targetDate->format('Y-m-d'))
                ->orderBy('date', 'DESC')
                ->limit(self::THRESHOLD_CONSECUTIVE_ALFA)
                ->findAll();

            $consecutiveAlfaCount = 0;
            $lastAlfaDate = null;
            if (count($recentAttendances) >= self::THRESHOLD_CONSECUTIVE_ALFA) {
                $allMatch = true;
                for ($i = 0; $i < self::THRESHOLD_CONSECUTIVE_ALFA; $i++) {
                    if ($recentAttendances[$i]['status'] !== 'A') {
                        $allMatch = false;
                        break;
                    }
                    if($i === 0) $lastAlfaDate = $recentAttendances[$i]['date'];
                }
                if ($allMatch) {
                    $consecutiveAlfaCount = self::THRESHOLD_CONSECUTIVE_ALFA;
                }
            }

            if ($consecutiveAlfaCount >= self::THRESHOLD_CONSECUTIVE_ALFA) {
                $message = "Siswa {$student['full_name']} telah absen (Alfa) selama {$consecutiveAlfaCount} hari berturut-turut (terakhir pada {$lastAlfaDate}).";
                $this->createNotification(
                    $notificationModel, $student,
                    'alfa_beruntun', $message,
                    $classModel, $userModel
                );
            }

            // 2. Check for total 'A' (Alfa) in the last X days
            $startDateTotalAlfa = (clone $targetDate)->modify('-' . (self::THRESHOLD_TOTAL_ALFA_DAYS_PERIOD - 1) . ' days')->format('Y-m-d');
            $totalAlfa = $attendanceModel
                ->where('student_id', $student['id'])
                ->where('status', 'A')
                ->where('date >=', $startDateTotalAlfa)
                ->where('date <=', $targetDate->format('Y-m-d'))
                ->countAllResults();

            if ($totalAlfa >= self::THRESHOLD_TOTAL_ALFA_COUNT) {
                 $message = "Siswa {$student['full_name']} memiliki total {$totalAlfa} hari absen (Alfa) dalam ".self::THRESHOLD_TOTAL_ALFA_DAYS_PERIOD." hari terakhir.";
                 $this->createNotification(
                    $notificationModel, $student,
                    'total_alfa', $message,
                    $classModel, $userModel
                );
            }

            // 3. Check for total 'S' (Sakit) + 'I' (Izin) in the last X days
            $startDateSickLeave = (clone $targetDate)->modify('-' . (self::THRESHOLD_TOTAL_SICK_LEAVE_DAYS_PERIOD - 1) . ' days')->format('Y-m-d');
            $totalSickLeave = $attendanceModel
                ->where('student_id', $student['id'])
                ->whereIn('status', ['S', 'I'])
                ->where('date >=', $startDateSickLeave)
                ->where('date <=', $targetDate->format('Y-m-d'))
                ->countAllResults();

            if ($totalSickLeave >= self::THRESHOLD_TOTAL_SICK_LEAVE_COUNT) {
                $message = "Siswa {$student['full_name']} memiliki total {$totalSickLeave} hari Sakit/Izin dalam ".self::THRESHOLD_TOTAL_SICK_LEAVE_DAYS_PERIOD." hari terakhir.";
                $this->createNotification(
                    $notificationModel, $student,
                    'total_sakit_izin', $message,
                    $classModel, $userModel
                );
            }
        }

        CLI::write('Attendance Alerts Check finished.', 'green');
    }

    private function createNotification(
        \App\Models\NotificationModel $notificationModel,
        array $student, string $type, string $message,
        \App\Models\ClassModel $classModel, \App\Models\UserModel $userModel
    ) {
        // Check if a similar unread notification already exists for this student and type to avoid spam
        // This simple check might need to be more sophisticated (e.g., check for notifications within last X days for same alert)
        $existing = $notificationModel->where('student_id', $student['id'])
                                     ->where('type', $type)
                                     //->where('is_read', false) // Or check based on creation date
                                     ->where('created_at >=', date('Y-m-d H:i:s', strtotime('-'.self::THRESHOLD_CONSECUTIVE_ALFA.' days'))) // Avoid re-notifying for the same consecutive period too soon
                                     ->first();
        if ($existing && ($type === 'alfa_beruntun' || $type === 'total_alfa' || $type === 'total_sakit_izin')) {
             CLI::write("Skipping notification for student {$student['id']}, type {$type}: similar recent notification exists.", 'yellow');
            return;
        }

        $recipients = [];
        // a. Wali Kelas
        if (!empty($student['class_id'])) {
            $classInfo = $classModel->find($student['class_id']);
            if ($classInfo && !empty($classInfo['wali_kelas_id'])) {
                $teacherModel = new \App\Models\TeacherModel();
                $waliKelasTeacher = $teacherModel->find($classInfo['wali_kelas_id']);
                if ($waliKelasTeacher && !empty($waliKelasTeacher['user_id'])) {
                    $recipients[] = $waliKelasTeacher['user_id'];
                }
            }
        }
        // b. Orang Tua
        if (!empty($student['parent_user_id'])) {
            $recipients[] = $student['parent_user_id'];
        }
        // c. Admin Sistem (Role ID 1 - assuming from RoleSeeder)
        //    Alternatively, query users by role name 'Administrator Sistem'
        $adminUsers = $userModel->where('role_id', 1)->findAll(); // Assuming role_id 1 is Admin
        foreach($adminUsers as $admin){
            $recipients[] = $admin['id'];
        }

        $uniqueRecipients = array_unique($recipients);

        foreach ($uniqueRecipients as $userId) {
            $data = [
                'user_id'    => $userId,
                'student_id' => $student['id'],
                'type'       => $type,
                'message'    => $message,
                'link'       => site_url('admin/students/show/' . $student['id']) // Example link, adjust as needed
            ];
            if ($notificationModel->insert($data)) {
                CLI::write("Notification created for user ID {$userId}: {$message}", 'cyan');
            } else {
                CLI::error("Failed to create notification for user ID {$userId}: " . json_encode($notificationModel->errors()));
            }
        }
    }
}
