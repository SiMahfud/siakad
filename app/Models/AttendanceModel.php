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
        $builder = $this->select('attendances.id as attendance_id, attendances.attendance_date, attendances.status, attendances.remarks, schedules.start_time, schedules.end_time, subjects.subject_name, users.full_name as recorded_by_name')
                        ->join('schedules', 'schedules.id = attendances.schedule_id')
                        ->join('subjects', 'subjects.id = schedules.subject_id')
                        ->join('users', 'users.id = attendances.recorded_by_user_id', 'left') // Join with users table
                        ->where('attendances.student_id', $studentId)
                        ->where('attendances.attendance_date >=', $startDate)
                        ->where('attendances.attendance_date <=', $endDate);

        if (!empty($statusFilter) && $statusFilter !== ['ALL']) { // Assuming 'ALL' means no status filter
             if(is_array($statusFilter) && !empty($statusFilter) && $statusFilter[0] !== 'ALL'){ // Ensure it's a valid array and not just ['ALL']
                $builder->whereIn('attendances.status', $statusFilter);
            }
        }

        return $builder->orderBy('attendances.attendance_date', 'ASC')
                       ->orderBy('schedules.start_time', 'ASC')
                       ->findAll();
    }

    /**
     * Get daily attendance status for a student within a date range.
     * If multiple statuses on the same day, prioritize Non-Hadir (A > S > I > H).
     *
     * @param int    $studentId
     * @param string $startDate (Y-m-d format)
     * @param string $endDate (Y-m-d format)
     * @return array ['YYYY-MM-DD' => 'StatusChar (H/S/I/A)']
     */
    public function getDailyAttendanceStatusForStudent(int $studentId, string $startDate, string $endDate): array
    {
        $records = $this->select('attendance_date, status')
                        ->where('student_id', $studentId)
                        ->where('attendance_date >=', $startDate)
                        ->where('attendance_date <=', $endDate)
                        ->orderBy('attendance_date', 'ASC')
                        ->orderBy('status', 'DESC') // Prioritize A (4) > S (2) > I (3) > H (1) if multiple entries on same day
                                                // Note: Izin (3) is higher than Sakit (2) in value, so order by status DESC
                                                // A (4), I (3), S (2), H (1)
                        ->findAll();

        $dailyStatus = [];
        $statusPriority = [
            self::STATUS_ALFA => 4,
            self::STATUS_IZIN => 3,
            self::STATUS_SAKIT => 2,
            self::STATUS_HADIR => 1,
        ];
        $statusChars = array_flip(self::getStatusMap()); // [1 => 'Hadir', 2 => 'Sakit', ...] -> ['Hadir' => 1, ...] - no, other way around
        $statusCharsMap = [];
        foreach(self::getStatusMap() as $code => $text){
            $statusCharsMap[$code] = substr($text, 0, 1); // H, S, I, A
        }


        foreach ($records as $record) {
            $date = $record['attendance_date'];
            $currentStatusPriority = $statusPriority[$record['status']];

            if (!isset($dailyStatus[$date]) || $currentStatusPriority > $statusPriority[$dailyStatus[$date]['numeric_status']]) {
                 $dailyStatus[$date] = [
                    'char_status' => $statusCharsMap[$record['status']],
                    'numeric_status' => $record['status'] // Store numeric too for priority check
                 ];
            }
        }

        // Flatten to just char_status
        $result = [];
        foreach($dailyStatus as $date => $statusData){
            $result[$date] = $statusData['char_status'];
        }

        return $result;
    }


    public function getAttendanceRecap(array $filters = [])
    {
        $builder = $this->db->table($this->table . ' a'); // Alias table to 'a'
        $builder->select([
            's.id as student_id',
            's.nis',
            's.full_name',
            'c.class_name',
            'c.id as class_id_for_recap',
            'COALESCE(SUM(CASE WHEN a.status = ' . self::STATUS_HADIR . ' THEN 1 ELSE 0 END), 0) as total_hadir',
            'COALESCE(SUM(CASE WHEN a.status = ' . self::STATUS_IZIN . ' THEN 1 ELSE 0 END), 0) as total_izin',
            'COALESCE(SUM(CASE WHEN a.status = ' . self::STATUS_SAKIT . ' THEN 1 ELSE 0 END), 0) as total_sakit',
            'COALESCE(SUM(CASE WHEN a.status = ' . self::STATUS_ALFA . ' THEN 1 ELSE 0 END), 0) as total_alfa',
        ]);
        $builder->join('students s', 's.id = a.student_id');
        $builder->join('class_student cs', 'cs.student_id = s.id', 'left');
        $builder->join('classes c', 'c.id = cs.class_id', 'left');

        if (!empty($filters['class_id'])) {
            $builder->where('cs.class_id', $filters['class_id']);
        }
        if (!empty($filters['date_from'])) {
            $builder->where('a.attendance_date >=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $builder->where('a.attendance_date <=', $filters['date_to']);
        }
        // Filter by status if provided
        if (!empty($filters['status']) && $filters['status'] !== 'ALL') {
             // If filtering by a specific status, we sum only that status and others will be 0 for that row.
             // This might change the meaning of total_hadir, total_izin etc.
             // A better approach for "show only students with X status" is more complex.
             // For now, this will filter the records that contribute to the SUMs.
             // This means if status 'A' is selected, only 'A' records are counted.
             // This might not be the direct intention for a recap which usually shows all statuses.
             // Let's reconsider this: the filter 'status' should probably filter WHICH students appear,
             // or which records are considered AT ALL, not change the SUM structure.
             // For now, let's assume the filter means "only consider attendance records with this status"
             // This will effectively make other counts zero for the period if a specific status is chosen.
             // This is NOT ideal for a summary that shows all H,I,S,A counts.
             // A more accurate way for "filter by status" would be to get students who HAVE that status, then show their full recap.
             // Given the current simple filter, we will apply it directly to the `a.status` in the WHERE clause.
             // This means if you filter by 'H', it will only count 'H' and other totals will be based on those filtered records.
             // This is probably not what the user expects for a full HISA recap.
             //
             // **Correction**: The filter 'status' should probably be applied AFTER aggregation or not at all
             // in this type of summary. If the goal is to see "students who were Alfa", that's a different query.
             // For now, I will IGNORE the $filters['status'] in the main aggregation query
             // and assume it will be used for display filtering or a different type of report.
             // The per-day visual calendar will use status more directly.
        }


        $builder->groupBy('s.id, s.nis, s.full_name, c.class_name, c.id');
        $builder->orderBy('c.class_name', 'ASC');
        $builder->orderBy('s.full_name', 'ASC');
        $resultsWithAttendance = $builder->get()->getResultArray();

        // Logic to include all students from the class even if they have no attendance records
        $studentQuery = $this->db->table('students s_main');
        $studentQuery->select('s_main.id as student_id, s_main.nis, s_main.full_name, c_main.class_name, c_main.id as class_id');
        $studentQuery->join('class_student cs_main', 'cs_main.student_id = s_main.id');
        $studentQuery->join('classes c_main', 'c_main.id = cs_main.class_id');

        if (!empty($filters['class_id'])) {
            $studentQuery->where('cs_main.class_id', $filters['class_id']);
        } else if (!(has_role('Administrator Sistem') || has_role('Staf Tata Usaha') || has_role('Kepala Sekolah'))) {
            // Non-admins must select a class if they have access to multiple, or it defaults if only one.
            // This scenario should be handled by controller to ensure a class_id is always present for non-admins.
            // If somehow it reaches here without class_id for non-admin, return empty to be safe.
            return [];
        }
        // For Admin/Staff/Kepsek without class_id, it will fetch all students. This can be large.
        // The controller should ideally enforce class selection or provide warnings for "all students" view.

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
                $studentData = [
                    'student_id' => $student['student_id'],
                    'nis' => $student['nis'],
                    'full_name' => $student['full_name'],
                    'class_name' => $student['class_name'],
                    'class_id_for_recap' => $student['class_id'],
                    'total_hadir' => 0, 'total_izin' => 0, 'total_sakit' => 0, 'total_alfa' => 0,
                ];
            }

            $distinctScheduledDays = 0;
            $classIdForScheduleCalc = $studentData['class_id_for_recap']; // Use student's actual class for day count

            if ($classIdForScheduleCalc) {
                $activityDaysDb = $this->db->table($this->table . ' adays');
                $activityDaysDb->distinct()->select('adays.attendance_date');
                $activityDaysDb->join('schedules sch_eff', 'sch_eff.id = adays.schedule_id');
                $activityDaysDb->where('sch_eff.class_id', $classIdForScheduleCalc);

                if (!empty($filters['date_from'])) {
                    $activityDaysDb->where('adays.attendance_date >=', $filters['date_from']);
                }
                if (!empty($filters['date_to'])) {
                    $activityDaysDb->where('adays.attendance_date <=', $filters['date_to']);
                }
                $distinctScheduledDays = $activityDaysDb->countAllResults();
            }
            // If no class_id (e.g. admin view all), total_days_for_percentage might be less meaningful
            // or would need to be calculated based on a global academic calendar.
            // For now, it's based on days with any attendance record for that student's class.

            $studentData['total_days_for_percentage'] = $distinctScheduledDays;
            if ($distinctScheduledDays > 0) {
                $studentData['percentage_hadir'] = round(($studentData['total_hadir'] / $distinctScheduledDays) * 100, 2);
            } else {
                $studentData['percentage_hadir'] = 0;
            }
            $finalRecap[] = $studentData;
        }

        // If admin views all classes, the $allStudentsInScope loop handles it.
        // The previous complex logic for admin global recap might be redundant now.
        // The key is that $allStudentsInScope should correctly reflect the scope (all students if admin & no class, or students of a class).

        return $finalRecap;
    }

    /**
     * Get daily attendance summary for a class within a date range.
     * Used for calendar view and potentially line chart.
     * @param array $filters ['class_id', 'date_from', 'date_to']
     * @return array ['YYYY-MM-DD' => ['H' => count, 'I' => count, 'S' => count, 'A' => count, 'total_students_in_class_on_day' => count]]
     */
    public function getDailyAttendanceSummaryForClass(array $filters): array
    {
        if (empty($filters['class_id']) || empty($filters['date_from']) || empty($filters['date_to'])) {
            return [];
        }

        $builder = $this->db->table($this->table . ' a');
        $builder->select('a.attendance_date, a.status, COUNT(a.id) as count');
        $builder->join('schedules sch', 'sch.id = a.schedule_id');
        $builder->where('sch.class_id', $filters['class_id']);
        $builder->where('a.attendance_date >=', $filters['date_from']);
        $builder->where('a.attendance_date <=', $filters['date_to']);
        $builder->groupBy('a.attendance_date, a.status');
        $results = $builder->get()->getResultArray();

        $summary = [];
        $statusMap = array_flip(self::getStatusMap()); // Get 'Hadir' => 1, etc.

        // Get total students in class for percentage calculation on each day
        // This assumes student enrollment in a class is constant for the period.
        // A more complex system might track student enrollment changes over time.
        $studentModel = new StudentModel();
        $totalStudentsInClass = $studentModel->join('class_student cs', 'cs.student_id = students.id')
                                           ->where('cs.class_id', $filters['class_id'])
                                           ->countAllResults();

        foreach ($results as $row) {
            $date = $row['attendance_date'];
            if (!isset($summary[$date])) {
                $summary[$date] = [
                    'H' => 0, 'S' => 0, 'I' => 0, 'A' => 0, // Using actual status codes from constants
                    'total_scheduled_students_on_day' => 0, // Will be populated based on distinct students with entries for the day
                    'percentage_hadir_on_day' => 0
                ];
            }
            // Map numeric status back to H, S, I, A if necessary, or use numeric status directly
            $statusKey = array_search($row['status'], $statusMap); // e.g., 1 -> 'Hadir' ; need to ensure status map is correct
            // Assuming $row['status'] IS 'H', 'S', 'I', 'A' from the database.
            // The constants are 1,2,3,4. The table stores 1,2,3,4.
            // So we need to map 1 to 'H', 2 to 'S' etc. for keys in summary array.

            $statusChar = '';
            switch ($row['status']) {
                case self::STATUS_HADIR: $statusChar = 'H'; break;
                case self::STATUS_SAKIT: $statusChar = 'S'; break;
                case self::STATUS_IZIN:  $statusChar = 'I'; break;
                case self::STATUS_ALFA:  $statusChar = 'A'; break;
            }
            if ($statusChar) {
                $summary[$date][$statusChar] = (int)$row['count'];
            }
        }

        // Calculate total scheduled students and percentage for each day
        // This requires knowing how many students *should* have had an attendance record on that day.
        // This is usually the total number of students in the class for that day.
        // Simpler: count distinct student_id from attendance table for that day and class_id.
        $distinctStudentsPerDay = $this->db->table($this->table . ' a_dist')
            ->select('a_dist.attendance_date, COUNT(DISTINCT a_dist.student_id) as distinct_student_count')
            ->join('schedules sch_dist', 'sch_dist.id = a_dist.schedule_id')
            ->where('sch_dist.class_id', $filters['class_id'])
            ->where('a_dist.attendance_date >=', $filters['date_from'])
            ->where('a_dist.attendance_date <=', $filters['date_to'])
            ->groupBy('a_dist.attendance_date')
            ->get()->getResultArray();

        $distinctStudentCountMap = array_column($distinctStudentsPerDay, 'distinct_student_count', 'attendance_date');

        foreach ($summary as $date => &$dayData) {
            $dayData['total_scheduled_students_on_day'] = $distinctStudentCountMap[$date] ?? 0; // Number of students with entries
            // $dayData['total_students_in_class_on_day'] = $totalStudentsInClass; // Total enrollment
            if ($dayData['total_scheduled_students_on_day'] > 0) {
                 $dayData['percentage_hadir_on_day'] = round(($dayData['H'] / $dayData['total_scheduled_students_on_day']) * 100, 2);
            }
        }
        unset($dayData); // Unset reference

        return $summary;
    }
}
