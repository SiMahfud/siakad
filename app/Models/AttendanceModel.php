<?php

namespace App\Models;

use CodeIgniter\Model;

class AttendanceModel extends Model
{
    protected $table            = 'attendances';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields    = [
        'schedule_id',
        'student_id',
        'attendance_date',
        'status',
        'remarks',
        'recorded_by_user_id',
    ];

    // Dates
    protected $useTimestamps = true; // Menggunakan created_at dan updated_at
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    // protected $deletedField  = 'deleted_at';

    // Status constants for easier reference
    public const STATUS_HADIR = 1;
    public const STATUS_SAKIT = 2;
    public const STATUS_IZIN  = 3;
    public const STATUS_ALFA  = 4;

    public static function getStatusMap(): array
    {
        return [
            self::STATUS_HADIR => 'Hadir',
            self::STATUS_SAKIT => 'Sakit',
            self::STATUS_IZIN  => 'Izin',
            self::STATUS_ALFA  => 'Alfa',
        ];
    }

    public static function getStatusKeys(): array
    {
        return array_keys(self::getStatusMap());
    }


    // Validation
    protected $validationRules      = [
        'schedule_id'     => 'required|integer|is_not_unique[schedules.id]',
        'student_id'      => 'required|integer|is_not_unique[students.id]',
        'attendance_date' => 'required|valid_date',
        'status'          => 'required|integer|in_list[1,2,3,4]', // 1:Hadir, 2:Sakit, 3:Izin, 4:Alfa
        'remarks'         => 'permit_empty|string',
        'recorded_by_user_id' => 'permit_empty|integer|is_not_unique[users.id]',
    ];
    protected $validationMessages   = [
        'schedule_id' => [
            'is_not_unique' => 'Selected schedule slot does not exist.'
        ],
        'student_id' => [
            'is_not_unique' => 'Selected student does not exist.'
        ],
        'recorded_by_user_id' => [
            'is_not_unique' => 'Recorder user ID does not exist.'
        ],
        'status' => [
            'in_list' => 'Invalid attendance status.'
        ]
    ];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = [];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];

    /**
     * Get attendance records for a specific schedule slot and date,
     * joined with student names.
     *
     * @param int    $scheduleId
     * @param string $attendanceDate (Y-m-d format)
     * @return array
     */
    public function getAttendanceByScheduleAndDate(int $scheduleId, string $attendanceDate): array
    {
        return $this->select('attendances.*, students.full_name as student_name, students.nisn')
                    ->join('students', 'students.id = attendances.student_id')
                    ->where('attendances.schedule_id', $scheduleId)
                    ->where('attendances.attendance_date', $attendanceDate)
                    ->orderBy('students.full_name', 'ASC')
                    ->findAll();
    }

    /**
     * Get attendance records for a student within a date range, optionally filtered by status.
     *
     * @param int    $studentId
     * @param string $startDate (Y-m-d format)
     * @param string $endDate (Y-m-d format)
     * @param array  $statusFilter (optional, array of status codes)
     * @return array
     */
    public function getStudentAttendanceSummary(int $studentId, string $startDate, string $endDate, array $statusFilter = []): array
    {
        $builder = $this->select('attendances.*, schedules.start_time, schedules.end_time, subjects.subject_name')
                        ->join('schedules', 'schedules.id = attendances.schedule_id')
                        ->join('subjects', 'subjects.id = schedules.subject_id')
                        ->where('attendances.student_id', $studentId)
                        ->where('attendances.attendance_date >=', $startDate)
                        ->where('attendances.attendance_date <=', $endDate);

        if (!empty($statusFilter)) {
            $builder->whereIn('attendances.status', $statusFilter);
        }

        return $builder->orderBy('attendances.attendance_date', 'ASC')
                       ->orderBy('schedules.start_time', 'ASC')
                       ->findAll();
    }

    public function getAttendanceRecap(array $filters = [])
    {
        $builder = $this->db->table($this->table . ' a'); // Alias table to 'a'
        $builder->select([
            's.id as student_id',
            's.nis', // Assuming 'nis' is the correct column name in 'students' table
            's.full_name',
            'c.class_name',
            'c.id as class_id_for_recap', // To ensure we get class_id in results
            'COALESCE(SUM(CASE WHEN a.status = ' . self::STATUS_HADIR . ' THEN 1 ELSE 0 END), 0) as total_hadir',
            'COALESCE(SUM(CASE WHEN a.status = ' . self::STATUS_IZIN . ' THEN 1 ELSE 0 END), 0) as total_izin',
            'COALESCE(SUM(CASE WHEN a.status = ' . self::STATUS_SAKIT . ' THEN 1 ELSE 0 END), 0) as total_sakit',
            'COALESCE(SUM(CASE WHEN a.status = ' . self::STATUS_ALFA . ' THEN 1 ELSE 0 END), 0) as total_alfa',
        ]);
        // Join with students table first
        $builder->join('students s', 's.id = a.student_id');
        // Then join students with class_student to get their class_id
        $builder->join('class_student cs', 'cs.student_id = s.id', 'left');
        // Then join class_student with classes table
        $builder->join('classes c', 'c.id = cs.class_id', 'left');


        if (!empty($filters['class_id'])) {
            $builder->where('cs.class_id', $filters['class_id']);
        }
        // If filtering by student, it's usually for a specific student's recap,
        // but the general recap is per class or all students.
        // For this general recap, we typically don't filter by a single student_id here,
        // but rather group by student_id.
        // if (!empty($filters['student_id'])) {
        //     $builder->where('a.student_id', $filters['student_id']);
        // }
        if (!empty($filters['date_from'])) {
            $builder->where('a.attendance_date >=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $builder->where('a.attendance_date <=', $filters['date_to']);
        }

        $builder->groupBy('s.id, s.nis, s.full_name, c.class_name, c.id');
        $builder->orderBy('c.class_name', 'ASC');
        $builder->orderBy('s.full_name', 'ASC');

        $resultsWithAttendance = $builder->get()->getResultArray();

        // Now, get all students from the selected class (or all students if no class filter for admin)
        // to ensure all students are listed, even with 0 attendance.
        $studentQuery = $this->db->table('students s_main');
        $studentQuery->select('s_main.id as student_id, s_main.nis, s_main.full_name, c_main.class_name, c_main.id as class_id');
        $studentQuery->join('class_student cs_main', 'cs_main.student_id = s_main.id');
        $studentQuery->join('classes c_main', 'c_main.id = cs_main.class_id');

        if (!empty($filters['class_id'])) {
            $studentQuery->where('cs_main.class_id', $filters['class_id']);
        }
        // If admin and no class selected, this could be very large.
        // Consider adding a warning or limiting this if no class is selected for admin.
        // For now, let's assume if no class_id, it's for all students (potentially large).

        $studentQuery->orderBy('c_main.class_name', 'ASC');
        $studentQuery->orderBy('s_main.full_name', 'ASC');
        $allStudentsInScope = $studentQuery->get()->getResultArray();

        $finalRecap = [];
        $attendanceMap = [];
        foreach ($resultsWithAttendance as $att) {
            $attendanceMap[$att['student_id']] = $att;
        }

        foreach ($allStudentsInScope as $student) {
            if (isset($attendanceMap[$student['student_id']])) {
                $studentData = $attendanceMap[$student['student_id']];
            } else {
                // Student has no attendance records in the period, initialize with zeros
                $studentData = [
                    'student_id' => $student['student_id'],
                    'nis' => $student['nis'],
                    'full_name' => $student['full_name'],
                    'class_name' => $student['class_name'],
                    'class_id_for_recap' => $student['class_id'],
                    'total_hadir' => 0,
                    'total_izin' => 0,
                    'total_sakit' => 0,
                    'total_alfa' => 0,
                ];
            }

            // Calculate total_days_for_percentage
            // This is the number of distinct days there were schedules for the student's class in the date range.
            $distinctScheduledDays = 0;
            $classIdForSchedule = !empty($filters['class_id']) ? $filters['class_id'] : $studentData['class_id_for_recap'];

            if ($classIdForSchedule) {
                // Count distinct attendance_date from attendances table,
                // but ensure these attendances belong to the specific class_id.
                $activityDaysDb = $this->db->table($this->table . ' adays'); // alias attendances to adays
                $activityDaysDb->distinct()->select('adays.attendance_date');
                $activityDaysDb->join('schedules sch_eff', 'sch_eff.id = adays.schedule_id'); // Join to schedules to filter by class
                $activityDaysDb->where('sch_eff.class_id', $classIdForSchedule);

                if (!empty($filters['date_from'])) {
                    $activityDaysDb->where('adays.attendance_date >=', $filters['date_from']);
                }
                if (!empty($filters['date_to'])) {
                    $activityDaysDb->where('adays.attendance_date <=', $filters['date_to']);
                }
                $distinctScheduledDays = $activityDaysDb->countAllResults();

            } else if (has_role('Administrator Sistem') || has_role('Staf Tata Usaha') || has_role('Kepala Sekolah')) {
                // Admin/Staff/Kepsek without class filter - count distinct attendance dates globally for the period
                $globalActivityDaysDb = $this->db->table($this->table . ' att_global'); // alias attendances
                $globalActivityDaysDb->distinct()->select('att_global.attendance_date');
                if (!empty($filters['date_from'])) {
                    $globalActivityDaysDb->where('att_global.attendance_date >=', $filters['date_from']);
                }
                if (!empty($filters['date_to'])) {
                    $globalActivityDaysDb->where('att_global.attendance_date <=', $filters['date_to']);
                }
                $distinctScheduledDays = $globalActivityDaysDb->countAllResults();
            }


            $studentData['total_days_for_percentage'] = $distinctScheduledDays;
            if ($distinctScheduledDays > 0) {
                $studentData['percentage_hadir'] = round(($studentData['total_hadir'] / $distinctScheduledDays) * 100, 2);
            } else {
                $studentData['percentage_hadir'] = 0;
            }
            $finalRecap[] = $studentData;
        }

        // If no class filter was applied (e.g. Admin viewing all), $allStudentsInScope might be too broad or empty if not handled well.
        // If $filters['class_id'] is empty and user is Admin/Staff, $allStudentsInScope will list all students in DB.
        // In this case, $resultsWithAttendance already contains all students who had attendance.
        // We might only want to iterate $resultsWithAttendance if no class_id is specified for admin.
        if (empty($filters['class_id']) && (has_role('Administrator Sistem') || has_role('Staf Tata Usaha') || has_role('Kepala Sekolah'))) {
            // For global recap, the initial $resultsWithAttendance is more appropriate as it's based on actual attendance records.
            // We'll recalculate percentage for this set.
            $recalculatedGlobalRecap = [];
            foreach($resultsWithAttendance as $row) {
                 $globalActivityDaysDb = $this->db->table('attendances att_global_recalc');
                 $globalActivityDaysDb->distinct()->select('att_global_recalc.attendance_date');
                 if (!empty($filters['date_from'])) {
                    $globalActivityDaysDb->where('att_global_recalc.attendance_date >=', $filters['date_from']);
                }
                if (!empty($filters['date_to'])) {
                    $globalActivityDaysDb->where('att_global_recalc.attendance_date <=', $filters['date_to']);
                }
                // If we want this per class of the student in the row:
                // $globalActivityDaysDb->where('att_global_recalc.class_id', $row['class_id_for_recap']);

                $distinctScheduledDaysGlobal = $globalActivityDaysDb->countAllResults();
                $row['total_days_for_percentage'] = $distinctScheduledDaysGlobal;
                 if ($distinctScheduledDaysGlobal > 0) {
                    $row['percentage_hadir'] = round(($row['total_hadir'] / $distinctScheduledDaysGlobal) * 100, 2);
                } else {
                    $row['percentage_hadir'] = 0;
                }
                $recalculatedGlobalRecap[] = $row;
            }
            return $recalculatedGlobalRecap;
        }


        return $finalRecap;
    }
}
