<?php

namespace App\Models;

use CodeIgniter\Model;

class ClassStudentModel extends Model
{
    protected $table            = 'class_student';
    // Pivot table often doesn't have a single auto-incrementing PK if composite key is used.
    // protected $primaryKey       = 'id';
    // protected $useAutoIncrement = true;
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

    /**
     * Get all students registered in a specific class.
     *
     * @param int $class_id
     * @return array
     */
    public function getStudentsInClass(int $class_id): array
    {
        return $this->select('students.id, students.nis, students.nisn, students.full_name') // Removed class_student.id
                    ->join('students', 'students.id = class_student.student_id')
                    ->where('class_student.class_id', $class_id)
                    ->orderBy('students.full_name', 'ASC')
                    ->findAll();
    }

    /**
     * Get students who are not yet assigned to the specified class.
     * This can be adjusted to "not assigned to ANY class" if needed.
     * For now, it gets students not in the *current* class.
     * A more complex version might exclude students already in *any* class.
     *
     * @param int $class_id
     * @return array
     */
    public function getAvailableStudents(int $class_id): array
    {
        // Get IDs of students already in *this* class
        $studentsInThisClass = $this->where('class_id', $class_id)
                                     ->findColumn('student_id');

        // If no students are in this class, all students are available.
        // Otherwise, find students whose IDs are not in the list.
        $studentModel = new StudentModel();
        if (empty($studentsInThisClass)) {
            return $studentModel->orderBy('full_name', 'ASC')->findAll();
        } else {
            return $studentModel->whereNotIn('id', $studentsInThisClass)
                                ->orderBy('full_name', 'ASC')
                                ->findAll();
        }
    }

    // Note: Methods for adding and removing entries are implicitly handled by
    // the parent Model's insert() and delete() methods, given the correct data or primary key.
    // No need to explicitly create addStudent() or removeStudent() here unless more complex logic
    // is required before/after the DB operation.
}
