<?php

namespace App\Models;

use CodeIgniter\Model;

class ClassModel extends Model
{
    protected $table            = 'classes'; // Database table name
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields    = [
        'class_name',
        'academic_year',
        'wali_kelas_id',
        'fase',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    // protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules      = [
        'class_name'    => 'required|max_length[50]',
        'academic_year' => 'required|max_length[10]',
        'fase'          => 'permit_empty|max_length[1]',
        'wali_kelas_id' => 'permit_empty|integer', // Check if teacher exists will be in controller
    ];
    protected $validationMessages   = [];
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

    // You can add relationships here if needed, e.g., to get wali_kelas name
    public function getWaliKelas($classId)
    {
        return $this->select('classes.*, teachers.full_name as wali_kelas_name')
                    ->join('teachers', 'teachers.id = classes.wali_kelas_id', 'left')
                    ->find($classId);
    }

    public function getAllClassesWithWaliKelas()
    {
        return $this->select('classes.*, teachers.full_name as wali_kelas_name')
                    ->join('teachers', 'teachers.id = classes.wali_kelas_id', 'left')
                    ->findAll();
    }
}
