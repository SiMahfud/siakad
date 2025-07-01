<?php

namespace App\Models;

use CodeIgniter\Model;

class RoleModel extends Model
{
    protected $table            = 'roles';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields    = ['role_name']; // Only role_name can be set directly

    // Dates - Roles table doesn't have timestamps in the initial design
    // protected $useTimestamps = false;
    // protected $createdField  = 'created_at';
    // protected $updatedField  = 'updated_at';

    // Validation - Basic validation if roles were to be managed via app
    protected $validationRules      = [
        'role_name' => 'required|min_length[3]|max_length[50]|is_unique[roles.role_name,id,{id}]'
    ];
    protected $validationMessages   = [
        'role_name' => [
            'is_unique' => 'This role name already exists.'
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
