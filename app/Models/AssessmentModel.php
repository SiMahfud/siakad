<?php

namespace App\Models;

use CodeIgniter\Model;

class AssessmentModel extends Model
{
    protected $table            = 'assessments';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields    = [
        'student_id',
        'subject_id',
        'class_id',
        'teacher_id',
        'assessment_type',
        'assessment_title',
        'score',
        'description',
        'assessment_date',
    ];

    // Dates
    protected $useTimestamps = true; // created_at and updated_at
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    // protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules = [
        'student_id'      => 'required|integer|is_not_unique[students.id]',
        'subject_id'      => 'required|integer|is_not_unique[subjects.id]',
        'class_id'        => 'required|integer|is_not_unique[classes.id]',
        'teacher_id'      => 'required|integer|is_not_unique[teachers.id]',
        'assessment_type' => 'required|in_list[FORMATIF,SUMATIF]',
        'assessment_title'=> 'permit_empty|max_length[255]',
        'assessment_date' => 'permit_empty|valid_date', // Should ideally be required, but table allows null for now

        // Conditional validation for score and description might be better handled in controller
        // or with custom validation rules if complexity increases.
        'score'           => 'permit_empty|decimal|greater_than_equal_to[0]|less_than_equal_to[100]',
        'description'     => 'permit_empty|string',
    ];

    protected $validationMessages = [
        'student_id' => [
            'is_not_unique' => 'Invalid student selected.'
        ],
        'subject_id' => [
            'is_not_unique' => 'Invalid subject selected.'
        ],
        'class_id' => [
            'is_not_unique' => 'Invalid class selected.'
        ],
        'teacher_id' => [
            'is_not_unique' => 'Invalid teacher (assessor) selected.'
        ],
        'score' => [
            'decimal'              => 'Score must be a valid number (e.g., 85.50).',
            'greater_than_equal_to'=> 'Score must be 0 or greater.',
            'less_than_equal_to'   => 'Score cannot exceed 100.',
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

    // You can add methods here to fetch assessments with related data, e.g.:
    // public function getAssessmentsForStudent($studentId, $subjectId = null, $classId = null) { ... }
}
