<?php

namespace App\Models;

use CodeIgniter\Model;

class StudentSubjectChoiceModel extends Model
{
    protected $table            = 'student_subject_choices';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields    = [
        'student_id',
        'subject_offering_id',
        'chosen_at',
        'status', // 1: Chosen/Pending, 2: Approved, 3: Rejected
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Status constants
    public const STATUS_CHOSEN_PENDING = 1;
    public const STATUS_APPROVED = 2;
    public const STATUS_REJECTED = 3;

    public static function getStatusChoiceMap(): array
    {
        return [
            self::STATUS_CHOSEN_PENDING => 'Chosen/Pending',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
        ];
    }

    // Validation
    protected $validationRules      = [
        'student_id'          => 'required|integer|is_not_unique[students.id]',
        'subject_offering_id' => 'required|integer|is_not_unique[subject_offerings.id]',
        'chosen_at'           => 'permit_empty|valid_date[Y-m-d H:i:s]',
        'status'              => 'permit_empty|integer|in_list[1,2,3]',
    ];
    protected $validationMessages   = [
        'student_id' => [
            'is_not_unique' => 'Selected student does not exist.'
        ],
        'subject_offering_id' => [
            'is_not_unique' => 'Selected subject offering does not exist.'
        ]
    ];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = ['setChosenAt'];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];

    protected function setChosenAt(array $data): array
    {
        if (!isset($data['data']['chosen_at'])) {
            $data['data']['chosen_at'] = date('Y-m-d H:i:s');
        }
        return $data;
    }

    /**
     * Get choices for a student with offering and subject details.
     * @param int $studentId
     * @param array $filters (e.g., ['academic_year' => '2023/2024', 'semester' => 1])
     * @return array
     */
    public function getStudentChoicesWithDetails(int $studentId, array $filters = []): array
    {
        $builder = $this->select('
                            student_subject_choices.*,
                            so.academic_year, so.semester, so.description as offering_description,
                            s.subject_name, s.subject_code
                        ')
                        ->join('subject_offerings so', 'so.id = student_subject_choices.subject_offering_id')
                        ->join('subjects s', 's.id = so.subject_id')
                        ->where('student_subject_choices.student_id', $studentId);

        if (!empty($filters['academic_year'])) {
            $builder->where('so.academic_year', $filters['academic_year']);
        }
        if (!empty($filters['semester'])) {
            $builder->where('so.semester', $filters['semester']);
        }
        if (isset($filters['status'])) {
            $builder->where('student_subject_choices.status', $filters['status']);
        }

        return $builder->orderBy('s.subject_name', 'ASC')->findAll();
    }

    /**
     * Get students who chose a specific subject offering.
     * @param int $subjectOfferingId
     * @return array
     */
    public function getStudentsByOffering(int $subjectOfferingId): array
    {
        return $this->select('student_subject_choices.*, students.full_name, students.nisn')
                    ->join('students', 'students.id = student_subject_choices.student_id')
                    ->where('student_subject_choices.subject_offering_id', $subjectOfferingId)
                    ->orderBy('students.full_name', 'ASC')
                    ->findAll();
    }
}
