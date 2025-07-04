<?php

namespace App\Models;

use CodeIgniter\Model;

class ScheduleModel extends Model
{
    protected $table            = 'schedules';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields    = [
        'class_id',
        'subject_id',
        'teacher_id',
        'day_of_week',
        'start_time',
        'end_time',
        'academic_year',
        'semester',
        'notes',
    ];

    // Dates
    protected $useTimestamps = true; // Menggunakan created_at dan updated_at
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    // protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules      = [
        'class_id'      => 'required|integer|is_not_unique[classes.id]',
        'subject_id'    => 'required|integer|is_not_unique[subjects.id]',
        'teacher_id'    => 'required|integer|is_not_unique[teachers.id]',
        'day_of_week'   => 'required|integer|in_list[1,2,3,4,5,6,7]', // 1:Mon, 7:Sun
        'start_time'    => 'required|regex_match[/^([01]\d|2[0-3]):([0-5]\d)(:([0-5]\d))?$/]',
        'end_time'      => 'required|regex_match[/^([01]\d|2[0-3]):([0-5]\d)(:([0-5]\d))?$/]', // Removed matches_time_greater_than for now
        'academic_year' => 'required|max_length[10]', // e.g., 2023/2024
        'semester'      => 'required|integer|in_list[1,2]',     // 1:Ganjil, 2:Genap
        'notes'         => 'permit_empty|string'
    ];
    protected $validationMessages   = [
        'end_time' => [
            'matches_time_greater_than' => 'End time must be after start time.'
        ],
        'class_id' => [
            'is_not_unique' => 'Selected class does not exist.'
        ],
        'subject_id' => [
            'is_not_unique' => 'Selected subject does not exist.'
        ],
        'teacher_id' => [
            'is_not_unique' => 'Selected teacher does not exist.'
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
     * Custom validation rule: Checks if end_time is greater than start_time.
     * This is a placeholder as CodeIgniter's built-in validation might not have
     * a direct time comparison rule like this. This logic is often handled
     * in the controller or with a custom validation rule if CI4 doesn't support it directly in array.
     *
     * CI4.x has `matches_field_greater_than[field_name]` but it's for numerical comparison.
     * For time, a custom rule is usually better.
     * For now, 'matches_time_greater_than' is a conceptual placeholder.
     * We'll rely on controller-level validation or a proper custom rule for this if needed.
     *
     * Update: CI4 does not have `valid_time` or a direct time comparison rule like `matches_time_greater_than`.
     * `valid_date` exists. For time, we'd typically use regex or a custom rule.
     * Let's use a simple regex for HH:MM format for `valid_time` concept.
     * True time comparison needs a custom rule or controller logic.
     */

    // Example method to get schedule details with names
    public function getScheduleDetails(array $filters = [])
    {
        $builder = $this->select('
                schedules.id, schedules.class_id, schedules.subject_id, schedules.teacher_id,
                schedules.day_of_week, schedules.start_time, schedules.end_time, schedules.academic_year,
                schedules.semester, schedules.notes, schedules.created_at, schedules.updated_at,
                classes.class_name,
                subjects.subject_name,
                teachers.full_name as teacher_name
            ')
            // ->from('schedules schedules') // Alias was removed
            ->join('classes', 'classes.id = schedules.class_id')
            ->join('subjects', 'subjects.id = schedules.subject_id')
            ->join('teachers', 'teachers.id = schedules.teacher_id');

        // Filters now refer to schedules.id directly or other fields
        if (!empty($filters['schedules.id'])) {
            $builder->where('schedules.id', $filters['schedules.id']);
        }
        if (!empty($filters['class_id'])) {
            $builder->where('schedules.class_id', $filters['class_id']);
        }
        if (!empty($filters['teacher_id'])) {
            $builder->where('schedules.teacher_id', $filters['teacher_id']);
        }
        if (!empty($filters['day_of_week'])) {
            $builder->where('schedules.day_of_week', $filters['day_of_week']);
        }
        if (!empty($filters['academic_year'])) {
            $builder->where('schedules.academic_year', $filters['academic_year']);
        }
        if (!empty($filters['semester'])) {
            $builder->where('schedules.semester', $filters['semester']);
        }

        // Order by day, then start_time
        $builder->orderBy('schedules.day_of_week', 'ASC')
                ->orderBy('schedules.start_time', 'ASC');

        return $builder->findAll();
    }
}
