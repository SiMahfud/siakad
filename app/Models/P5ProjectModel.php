<?php

namespace App\Models;

use CodeIgniter\Model;

class P5ProjectModel extends Model
{
    protected $table = 'p5_projects';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = ['p5_theme_id', 'name', 'description', 'start_date', 'end_date'];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    // Validation
    protected $validationRules = [
        'p5_theme_id' => 'required|integer',
        'name' => 'required|max_length[255]',
        'description' => 'permit_empty|string',
        'start_date' => 'permit_empty|valid_date',
        'end_date' => 'permit_empty|valid_date|valid_date_range_if_set[start_date,end_date]',
    ];
    protected $validationMessages = [];
    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    // Custom validation rule will be added in a later step
}
