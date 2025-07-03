<?php

namespace App\Models;

use CodeIgniter\Model;

class P5ProjectStudentModel extends Model
{
    protected $table = 'p5_project_students';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = ['p5_project_id', 'student_id'];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    // Validation
    protected $validationRules = [
        'p5_project_id' => 'required|integer',
        'student_id' => 'required|integer',
    ];
    protected $validationMessages = [];
    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    /**
     * Get the project associated with this entry.
     *
     * @return array|null
     */
    public function getProject(): ?array
    {
        if (empty($this->attributes['p5_project_id'])) {
            return null;
        }

        $projectModel = new P5ProjectModel();
        return $projectModel->find($this->attributes['p5_project_id']);
    }

    /**
     * Get the student associated with this entry.
     *
     * @return array|null
     */
    public function getStudent(): ?array
    {
        if (empty($this->attributes['student_id'])) {
            return null;
        }

        $studentModel = new StudentModel(); // Assuming StudentModel exists
        return $studentModel->find($this->attributes['student_id']);
    }
}
