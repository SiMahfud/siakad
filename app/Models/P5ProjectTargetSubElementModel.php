<?php

namespace App\Models;

use CodeIgniter\Model;

class P5ProjectTargetSubElementModel extends Model
{
    protected $table = 'p5_project_target_sub_elements';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = ['p5_project_id', 'p5_sub_element_id'];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    // Validation
    protected $validationRules = [
        'p5_project_id' => 'required|integer',
        'p5_sub_element_id' => 'required|integer',
    ];
    protected $validationMessages = [];
    protected $skipValidation = false;
    protected $cleanValidationRules = true;
}
