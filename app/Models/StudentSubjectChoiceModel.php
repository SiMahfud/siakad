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

    /**
     * Get recap of subject choices.
     * Provides a list of subject offerings with the count of students who chose them,
     * and optionally the list of students.
     *
     * @param array $filters (e.g., ['academic_year' => '2023/2024', 'semester' => 1, 'include_student_names' => false])
     * @return array
     */
    public function getSubjectChoiceRecap(array $filters = []): array
    {
        $offeringModel = new SubjectOfferingModel();

        // Base query for subject offerings
        $offeringQuery = $offeringModel->select('subject_offerings.id as offering_id, subject_offerings.academic_year, subject_offerings.semester, subject_offerings.max_quota, subjects.subject_name, subjects.subject_code, subject_offerings.description as offering_description')
                                       ->join('subjects', 'subjects.id = subject_offerings.subject_id');

        if (!empty($filters['academic_year'])) {
            $offeringQuery->where('subject_offerings.academic_year', $filters['academic_year']);
        }
        if (!empty($filters['semester'])) {
            $offeringQuery->where('subject_offerings.semester', $filters['semester']);
        }
        if (!empty($filters['subject_id'])) {
            $offeringQuery->where('subject_offerings.subject_id', $filters['subject_id']);
        }
        // Default to active offerings unless specified otherwise
        if (!isset($filters['is_active'])) {
            $offeringQuery->where('subject_offerings.is_active', 1);
        } elseif ($filters['is_active'] !== null) { // Allow is_active = 0 or 1 if explicitly passed
            $offeringQuery->where('subject_offerings.is_active', $filters['is_active']);
        }

        $offerings = $offeringQuery->orderBy('subjects.subject_name', 'ASC')->findAll();

        $recap = [];
        foreach ($offerings as $offering) {
            // Count students for this offering
            // Considering only 'Approved' or 'Chosen/Pending' status as valid choices for recap counts.
            // Adjust if 'Rejected' choices should also be considered for some reason.
            $choiceCount = $this->where('subject_offering_id', $offering['offering_id'])
                                ->whereIn('status', [self::STATUS_CHOSEN_PENDING, self::STATUS_APPROVED])
                                ->countAllResults();

            $offering['number_of_choosers'] = $choiceCount;
            $offering['remaining_quota'] = ($offering['max_quota'] !== null) ? $offering['max_quota'] - $choiceCount : null;

            if (!empty($filters['include_student_names']) && $filters['include_student_names'] === true) {
                $students = $this->select('students.nis, students.full_name, students.id as student_id, sc.class_name') // Added class_name
                                 ->join('students', 'students.id = student_subject_choices.student_id')
                                 ->join('class_student cs', 'cs.student_id = students.id', 'left') // Join to get class_id
                                 ->join('classes sc', 'sc.id = cs.class_id', 'left') // Join to get class_name
                                 ->where('student_subject_choices.subject_offering_id', $offering['offering_id'])
                                 ->whereIn('student_subject_choices.status', [self::STATUS_CHOSEN_PENDING, self::STATUS_APPROVED])
                                 ->orderBy('students.full_name', 'ASC')
                                 ->findAll();
                $offering['students_list'] = $students;
            }
            $recap[] = $offering;
        }
        return $recap;
    }
}
