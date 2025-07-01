<?php

namespace App\Models;

use CodeIgniter\Model;

class StudentModel extends Model
{
    protected $table            = 'students';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array'; // Or 'object' or your custom class
    protected $useSoftDeletes   = false; // Set to true if you want soft deletes

    protected $allowedFields    = [
        'user_id',
        'nisn',
        'full_name',
        'parent_user_id',
        // Add other student-specific fields from the migration if they should be directly settable
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    // protected $deletedField  = 'deleted_at'; // Uncomment if useSoftDeletes is true

    // Validation
    protected $validationRules      = [
        'full_name' => 'required|max_length[255]',
        'nisn' => 'permit_empty|max_length[20]|is_unique[students.nisn,id,{id}]',
        'user_id' => 'permit_empty|integer|is_not_unique[users.id]', // Ensure user_id exists in users table if provided
        'parent_user_id' => 'permit_empty|integer|is_not_unique[users.id]', // Ensure parent_user_id exists in users table if provided
    ];
    protected $validationMessages   = [
        'nisn' => [
            'is_unique' => 'This NISN is already registered.'
        ],
        'user_id' => [
            'is_not_unique' => 'The selected User ID for student login does not exist.'
        ],
        'parent_user_id' => [
            'is_not_unique' => 'The selected User ID for parent login does not exist.'
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
}
