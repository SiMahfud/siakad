<?php

namespace App\Models;

use CodeIgniter\Model;

class P5ProjectFacilitatorModel extends Model
{
    protected $table            = 'p5_project_facilitators';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false; // Consider if soft deletes are needed

    protected $allowedFields    = ['p5_project_id', 'teacher_id'];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    // protected $deletedField  = 'deleted_at'; // Uncomment if using soft deletes

    // Validation - Basic validation, more complex rules can be added as needed
    protected $validationRules      = [
        'p5_project_id' => 'required|integer|is_not_unique[p5_projects.id]',
        'teacher_id'    => 'required|integer|is_not_unique[teachers.id]',
    ];
    protected $validationMessages   = [
        'p5_project_id' => [
            'is_not_unique' => 'Invalid P5 Project selected.',
        ],
        'teacher_id' => [
            'is_not_unique' => 'Invalid Teacher selected.',
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
     * Get facilitators for a specific project.
     *
     * @param int $projectId
     * @return array
     */
    public function getFacilitatorsForProject(int $projectId): array
    {
        return $this->select('p5_project_facilitators.*, teachers.full_name as teacher_name, teachers.nip as teacher_nip')
                    ->join('teachers', 'teachers.id = p5_project_facilitators.teacher_id')
                    ->where('p5_project_facilitators.p5_project_id', $projectId)
                    ->orderBy('teachers.full_name', 'ASC')
                    ->findAll();
    }

    /**
     * Check if a teacher is a facilitator for a specific project.
     *
     * @param int $teacherId
     * @param int $projectId
     * @return bool
     */
    public function isFacilitator(int $teacherId, int $projectId): bool
    {
        return $this->where('teacher_id', $teacherId)
                    ->where('p5_project_id', $projectId)
                    ->countAllResults() > 0;
    }

    /**
     * Get projects facilitated by a specific teacher.
     *
     * @param int $teacherId
     * @return array
     */
    public function getProjectsByFacilitator(int $teacherId): array
    {
        return $this->select('p5_project_facilitators.p5_project_id, p5_projects.name as project_name, p5_projects.description as project_description, p5_projects.start_date, p5_projects.end_date')
                    ->join('p5_projects', 'p5_projects.id = p5_project_facilitators.p5_project_id')
                    ->where('p5_project_facilitators.teacher_id', $teacherId)
                    ->orderBy('p5_projects.name', 'ASC')
                    ->findAll();
    }
}
