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
}
