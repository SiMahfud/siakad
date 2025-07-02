<?php

namespace App\Models;

use CodeIgniter\Model;

class SubjectOfferingModel extends Model
{
    protected $table            = 'subject_offerings';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields    = [
        'subject_id',
        'academic_year',
        'semester',
        'max_quota',
        'current_enrollment',
        'description',
        'is_active',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Validation
    protected $validationRules      = [
        'subject_id'    => 'required|integer|is_not_unique[subjects.id]',
        'academic_year' => 'required|max_length[10]',
        'semester'      => 'required|integer|in_list[1,2]',
        'max_quota'     => 'permit_empty|integer|greater_than_equal_to[0]',
        'current_enrollment' => 'permit_empty|integer|greater_than_equal_to[0]',
        'description'   => 'permit_empty|string',
        'is_active'     => 'permit_empty|in_list[0,1]',
    ];
    protected $validationMessages   = [
        'subject_id' => [
            'is_not_unique' => 'Selected subject does not exist.'
        ],
        'max_quota' => [
            'greater_than_equal_to' => 'Max quota must be a non-negative number.'
        ],
         'current_enrollment' => [
            'greater_than_equal_to' => 'Current enrollment must be a non-negative number.'
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

    /**
     * Get subject offerings with subject details.
     * @param array $filters Conditions for filtering (e.g., ['academic_year' => '2023/2024', 'semester' => 1, 'is_active' => true])
     * @return array
     */
    public function getOfferingsWithDetails(array $filters = []): array
    {
        $builder = $this->select('subject_offerings.*, subjects.subject_name, subjects.subject_code')
                        ->join('subjects', 'subjects.id = subject_offerings.subject_id');

        if (isset($filters['is_active'])) {
            $builder->where('subject_offerings.is_active', $filters['is_active']);
        }
        if (!empty($filters['academic_year'])) {
            $builder->where('subject_offerings.academic_year', $filters['academic_year']);
        }
        if (!empty($filters['semester'])) {
            $builder->where('subject_offerings.semester', $filters['semester']);
        }
        // Add more filters as needed

        return $builder->orderBy('subjects.subject_name', 'ASC')->findAll();
    }

    /**
     * Increment the current_enrollment count for a subject offering.
     * @param int $offeringId
     * @return bool
     */
    public function incrementEnrollment(int $offeringId): bool
    {
        return $this->where('id', $offeringId)
                    ->set('current_enrollment', 'current_enrollment + 1', false)
                    ->update();
    }

    /**
     * Decrement the current_enrollment count for a subject offering.
     * @param int $offeringId
     * @return bool
     */
    public function decrementEnrollment(int $offeringId): bool
    {
        return $this->where('id', $offeringId)
                    ->where('current_enrollment >', 0) // Ensure it doesn't go below zero
                    ->set('current_enrollment', 'current_enrollment - 1', false)
                    ->update();
    }
}
