<?php

namespace App\Models;

use CodeIgniter\Model;

class TeacherClassSubjectAssignmentModel extends Model
{
    protected $table            = 'teacher_class_subject_assignments';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields    = [
        'teacher_id',
        'class_id',
        'subject_id',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    // protected $deletedField  = 'deleted_at';

    // Validation
    // Aturan validasi dasar. Validasi yang lebih kompleks (seperti unique composite)
    // bisa ditangani di controller atau mengandalkan constraint database.
    protected $validationRules      = [
        'teacher_id' => 'required|integer|is_not_unique[teachers.id]',
        'class_id'   => 'required|integer|is_not_unique[classes.id]',
        'subject_id' => 'required|integer|is_not_unique[subjects.id]',
        // Unique check for combination of teacher_id, class_id, subject_id
        // This is better handled by a custom rule or in the controller before attempting to save,
        // or by catching database exceptions for duplicate entries if the DB constraint is reliable.
        // Example: 'teacher_class_subject' => 'is_unique_assignment[teacher_id,class_id,subject_id]'
        // For now, we rely on the database unique constraint defined in the migration.
    ];
    protected $validationMessages   = [
        'teacher_id' => [
            'is_not_unique' => 'Selected teacher does not exist.',
        ],
        'class_id' => [
            'is_not_unique' => 'Selected class does not exist.',
        ],
        'subject_id' => [
            'is_not_unique' => 'Selected subject does not exist.',
        ],
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
     * Retrieves assignments joined with teacher, class, and subject names.
     * @return array
     */
    public function getAssignmentsDetails(): array
    {
        return $this->select('
                teacher_class_subject_assignments.*,
                teachers.full_name as teacher_name,
                classes.class_name, classes.academic_year,
                subjects.subject_name
            ')
            ->join('teachers', 'teachers.id = teacher_class_subject_assignments.teacher_id')
            ->join('classes', 'classes.id = teacher_class_subject_assignments.class_id')
            ->join('subjects', 'subjects.id = teacher_class_subject_assignments.subject_id')
            ->orderBy('teachers.full_name', 'ASC')
            ->orderBy('classes.class_name', 'ASC')
            ->orderBy('subjects.subject_name', 'ASC')
            ->findAll();
    }

    /**
     * Retrieves subjects taught by a specific teacher in a specific class.
     * @param int $teacherId
     * @param int $classId
     * @return array
     */
    public function getSubjectsForTeacherInClass(int $teacherId, int $classId): array
    {
        return $this->select('subjects.id, subjects.subject_name, subjects.subject_code, subjects.is_pilihan')
            ->join('subjects', 'subjects.id = teacher_class_subject_assignments.subject_id')
            ->where('teacher_class_subject_assignments.teacher_id', $teacherId)
            ->where('teacher_class_subject_assignments.class_id', $classId)
            ->orderBy('subjects.subject_name', 'ASC')
            ->findAll();
    }

    /**
     * Retrieves all distinct subjects taught in a specific class.
     * @param int $classId
     * @return array
     */
    public function getDistinctSubjectsForClass(int $classId): array
    {
        return $this->select('subjects.id as subject_id, subjects.subject_name, subjects.subject_code, subjects.is_pilihan')
            ->join('subjects', 'subjects.id = teacher_class_subject_assignments.subject_id')
            ->where('teacher_class_subject_assignments.class_id', $classId)
            ->distinct()
            ->orderBy('subjects.subject_name', 'ASC')
            ->findAll();
    }
}
