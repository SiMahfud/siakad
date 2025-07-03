<?php

namespace App\Models;

use CodeIgniter\Model;

class P5AssessmentModel extends Model
{
    protected $table = 'p5_assessments';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = ['p5_project_student_id', 'p5_sub_element_id', 'assessment_value', 'notes', 'assessed_by'];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    // Validation
    protected $validationRules = [
        'p5_project_student_id' => 'required|integer',
        'p5_sub_element_id' => 'required|integer',
        'assessment_value' => 'required|max_length[255]',
        'notes' => 'permit_empty|string',
        'assessed_by' => 'required|integer',
    ];
    protected $validationMessages = [];
    protected $skipValidation = false;
    protected $cleanValidationRules = true;
}
