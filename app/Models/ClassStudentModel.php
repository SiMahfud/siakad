<?php

namespace App\Models;

use CodeIgniter\Model;

class ClassStudentModel extends Model
{
    protected $table            = 'class_student';
    protected $primaryKey       = 'id'; // Assuming 'id' is the primary key of this pivot table
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields    = ['class_id', 'student_id'];

    // Dates - Pivot tables might not need timestamps unless you want to track when a student was added/removed.
    // For now, assuming no timestamps as per the migration for class_student.
    // protected $useTimestamps = true;
    // protected $createdField  = 'created_at';
    // protected $updatedField  = 'updated_at';

    // Validation Rules
    protected $validationRules = [
        'class_id'   => 'required|integer|is_not_unique[classes.id]',
        'student_id' => 'required|integer|is_not_unique[students.id]',
        // Add unique constraint for class_id and student_id combination if not handled by DB or needed here
        // 'class_student_unique' => 'is_unique[class_student.class_id,student_id]'
        // This is typically best handled by a database unique constraint.
    ];

    protected $validationMessages = [
        'class_id' => [
            'is_not_unique' => 'The selected class does not exist.'
        ],
        'student_id' => [
            'is_not_unique' => 'The selected student does not exist.'
        ]
    ];

    protected $skipValidation       = false;
    protected $cleanValidationRules = true;
}
