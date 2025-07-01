<?php

namespace App\Models;

use CodeIgniter\Model;

class TeacherModel extends Model
{
    protected $table            = 'teachers';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields    = [
        'user_id',
        'nip',
        'full_name',
        // Add other teacher-specific fields
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    // protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules      = [
        'full_name' => 'required|max_length[255]',
        'nip'       => 'permit_empty|max_length[30]|is_unique[teachers.nip,id,{id}]',
        'user_id'   => 'permit_empty|integer|is_not_unique[users.id]', // Ensure user_id exists in users table if provided
    ];
    protected $validationMessages   = [
        'nip' => [
            'is_unique' => 'This NIP is already registered.'
        ],
        'user_id' => [
            'is_not_unique' => 'The selected User ID for teacher login does not exist.'
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
