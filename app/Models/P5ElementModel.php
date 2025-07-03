<?php

namespace App\Models;

use CodeIgniter\Model;

class P5ElementModel extends Model
{
    protected $table = 'p5_elements';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = ['p5_dimension_id', 'name', 'description'];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    // Validation
    protected $validationRules = [
        'p5_dimension_id' => 'required|integer',
        'name' => 'required|max_length[255]',
        'description' => 'permit_empty|string',
    ];
    protected $validationMessages = [];
    protected $skipValidation = false;
    protected $cleanValidationRules = true;
}
