<?php

namespace App\Models;

use CodeIgniter\Model;

class DailyAttendanceModel extends Model
{
    protected $table            = 'daily_attendances';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'student_id',
        'class_id',
        'attendance_date',
        'status',
        'remarks',
        'recorded_by_user_id'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Status constants consistent with AttendanceModel
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

    public static function getStatusCharMap(): array
    {
        return [
            self::STATUS_HADIR => 'H',
            self::STATUS_SAKIT => 'S',
            self::STATUS_IZIN  => 'I',
            self::STATUS_ALFA  => 'A',
        ];
    }


    // Validation
    protected $validationRules      = [
        'student_id'      => 'required|integer|is_not_unique[students.id]',
        'class_id'        => 'required|integer|is_not_unique[classes.id]',
        'attendance_date' => 'required|valid_date',
        'status'          => 'required|integer|in_list[1,2,3,4]',
        'remarks'         => 'permit_empty|string|max_length[65535]', // TEXT max length
        'recorded_by_user_id' => 'required|integer|is_not_unique[users.id]',
    ];
    protected $validationMessages   = [
        // Custom messages can be added here if needed
    ];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    /**
     * Get all daily attendance entries for a specific class and date.
     *
     * @param int    $classId
     * @param string $date (Y-m-d format)
     * @return array
     */
    public function getDailyAttendance(int $classId, string $date): array
    {
        return $this->where('class_id', $classId)
                    ->where('attendance_date', $date)
                    ->findAll();
    }

    /**
     * Get daily attendance entries for a specific student and date range.
     *
     * @param int    $studentId
     * @param string $dateFrom (Y-m-d format)
     * @param string $dateTo (Y-m-d format)
     * @return array
     */
    public function getStudentDailyAttendanceRange(int $studentId, string $dateFrom, string $dateTo): array
    {
        return $this->where('student_id', $studentId)
                    ->where('attendance_date >=', $dateFrom)
                    ->where('attendance_date <=', $dateTo)
                    ->orderBy('attendance_date', 'ASC')
                    ->findAll();
    }


    /**
     * Save or update daily attendance data for multiple students for a specific class and date.
     *
     * @param int    $classId
     * @param string $date (Y-m-d format)
     * @param array  $attendanceData Array of ['student_id' => x, 'status' => y, 'remarks' => z]
     * @param int    $recordedByUserId
     * @return bool True on success, false on failure or if any part fails.
     */
    public function saveBulkDailyAttendance(int $classId, string $date, array $studentAttendances, int $recordedByUserId): bool
    {
        $this->db->transStart();

        foreach ($studentAttendances as $studentId => $data) {
            if (!isset($data['status']) || $data['status'] === '' || $data['status'] === null) {
                // If status is empty, delete existing record for that student on that day
                $this->where('student_id', $studentId)
                     ->where('attendance_date', $date)
                     ->delete();
                continue; // Move to next student
            }

            $existingRecord = $this->where('student_id', $studentId)
                                   ->where('attendance_date', $date)
                                   ->first();

            $saveData = [
                'student_id' => $studentId,
                'class_id' => $classId,
                'attendance_date' => $date,
                'status' => $data['status'],
                'remarks' => $data['remarks'] ?? null,
                'recorded_by_user_id' => $recordedByUserId,
            ];

            if ($existingRecord) {
                $this->update($existingRecord['id'], $saveData);
            } else {
                $this->insert($saveData);
            }
        }

        $this->db->transComplete();
        return $this->db->transStatus();
    }
}
