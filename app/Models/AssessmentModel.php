<?php

namespace App\Models;

use CodeIgniter\Model;

class AssessmentModel extends Model
{
    protected $table            = 'assessments';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields    = [
        'student_id',
        'subject_id',
        'class_id',
        'teacher_id',
        'assessment_type',
        'assessment_title',
        'score',
        'description',
        'assessment_date',
    ];

    // Dates
    protected $useTimestamps = true; // created_at and updated_at
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    // protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules = [
        'student_id'      => 'required|integer|is_not_unique[students.id]',
        'subject_id'      => 'required|integer|is_not_unique[subjects.id]',
        'class_id'        => 'required|integer|is_not_unique[classes.id]',
        'teacher_id'      => 'required|integer|is_not_unique[teachers.id]',
        'assessment_type' => 'required|in_list[FORMATIF,SUMATIF]',
        'assessment_title'=> 'permit_empty|max_length[255]', // Title can be optional for quick notes
        'assessment_date' => 'required|valid_date',

        // Conditional validation for score and description might be better handled in controller
        // or with custom validation rules if complexity increases.
        'score'           => 'permit_empty|decimal|greater_than_equal_to[0]|less_than_equal_to[100]',
        'description'     => 'permit_empty|string',
    ];

    protected $validationMessages = [
        'student_id' => [
            'is_not_unique' => 'Invalid student selected.'
        ],
        'subject_id' => [
            'is_not_unique' => 'Invalid subject selected.'
        ],
        'class_id' => [
            'is_not_unique' => 'Invalid class selected.'
        ],
        'teacher_id' => [
            'is_not_unique' => 'Invalid teacher (assessor) selected.'
        ],
        'score' => [
            'decimal'              => 'Score must be a valid number (e.g., 85.50).',
            'greater_than_equal_to'=> 'Score must be 0 or greater.',
            'less_than_equal_to'   => 'Score cannot exceed 100.',
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

    // You can add methods here to fetch assessments with related data, e.g.:
    // public function getAssessmentsForStudent($studentId, $subjectId = null, $classId = null) { ... }

    /**
     * Fetches assessments for a specific class and subject, joined with student details,
     * ordered for recap display.
     *
     * @param int $classId
     * @param int $subjectId
     * @return array
     */
    public function getAssessmentsForRecap(int $classId, int $subjectId): array
    {
        return $this->select('assessments.*, students.full_name as student_name, students.nisn as student_nisn')
                    ->join('students', 'students.id = assessments.student_id')
                    ->where('assessments.class_id', $classId)
                    ->where('assessments.subject_id', $subjectId)
                    ->orderBy('students.full_name', 'ASC')
                    ->orderBy('assessments.assessment_date', 'ASC')
                    ->orderBy('assessments.assessment_type', 'ASC') // Formatif then Sumatif for same date
                    ->findAll();
    }

    /**
     * Get data formatted for e-Rapor export.
     * Calculates average of summative scores per subject for each student in a class.
     *
     * @param int    $classId
     * @param string $academicYear
     * @param int    $semester
     * @return array ['students' => [...], 'subjects' => [...]]
     */
    public function getExportDataForErapor(int $classId, string $academicYear, int $semester): array
    {
        // 1. Get all students in the class
        $studentModel = new StudentModel();
        $students = $studentModel->select('students.id, students.nisn, students.nis, students.full_name')
                                 ->join('class_student cs', 'cs.student_id = students.id')
                                 ->where('cs.class_id', $classId)
                                 ->orderBy('students.full_name', 'ASC')
                                 ->findAll();

        if (empty($students)) {
            return ['students' => [], 'subjects' => []];
        }
        $studentIdList = array_column($students, 'id');

        // 2. Determine subjects:
        // For e-Rapor, we typically need scores for all subjects assigned to the class in that semester,
        // not just subjects that happen to have a summative assessment recorded.
        // We'll fetch subjects assigned to the class via TeacherClassSubjectAssignmentModel or ScheduleModel.
        // Using ScheduleModel as it directly links class, subject, teacher, academic_year, and semester.

        $scheduleModel = new ScheduleModel();
        $assignedSubjectsQuery = $scheduleModel->distinct()
                                        ->select('schedules.subject_id, sub.subject_name, sub.subject_code')
                                        ->join('subjects sub', 'sub.id = schedules.subject_id')
                                        ->where('schedules.class_id', $classId)
                                        ->where('schedules.academic_year', $academicYear)
                                        ->where('schedules.semester', $semester);

        $assignedSubjects = $assignedSubjectsQuery->orderBy('sub.subject_name', 'ASC')->findAll();

        if (empty($assignedSubjects)) {
             // Return students but no subjects/scores if no subjects are scheduled for the class in that period
            $studentsDataEmptyScores = [];
            foreach($students as $student) {
                $studentsDataEmptyScores[$student['id']] = [
                    'nisn'      => $student['nisn'],
                    'nis'       => $student['nis'],
                    'full_name' => $student['full_name'],
                    'scores'    => [], // No subjects, so no scores
                ];
            }
            return ['students' => $studentsDataEmptyScores, 'subjects' => []];
        }
        $subjectIds = array_column($assignedSubjects, 'subject_id');
        $subjectsMap = array_column($assignedSubjects, null, 'subject_id');


        // 3. Get all SUMMATIVE scores for these students, subjects, class, for the GIVEN ACADEMIC YEAR AND SEMESTER.
        // The assessment_date should fall within the academic year and semester.
        // This logic can be complex if semester start/end dates are not strictly defined.
        // For now, we assume assessment_date is the primary key for time, and we need to ensure
        // the assessments fetched are indeed for the specified academic_year and semester.
        // The `assessments` table itself does not directly store academic_year or semester.
        // We must infer this, perhaps by joining with `schedules` via `assessments.schedule_id` if assessments are linked to schedules,
        // or by a convention on `assessment_date`.
        // The current `assessments` table in README does not show `schedule_id`.
        // Let's assume `assessments.class_id` and `assessments.assessment_date` are the primary filters for now,
        // and we'll need a robust way to map assessment_date to academic_year/semester if not directly stored or linkable.

        // Given the current `assessments` table structure from README (student_id, subject_id, class_id, teacher_id, assessment_type, score, assessment_date),
        // we will filter by class_id, student_ids, subject_ids, assessment_type='Sumatif'.
        // The filtering by academic_year and semester based on assessment_date needs a defined period.
        // For simplicity in this step, we'll fetch all summatives and assume the controller or a helper function
        // would have pre-filtered assessments based on date ranges corresponding to the academic year/semester.
        // OR, a more robust way: link assessments to schedules (if schedule_id was in assessments table)
        // OR, add academic_year and semester to assessments table during input.

        // For now, let's assume the `assessment_date` itself is sufficient if we know the date range for the semester.
        // This is a simplification. A real system would need a clear link.
        // Let's assume the controller will pass date_from and date_to for the semester.
        // If not, we'll have to query all summatives and then average them.

        $allScoresQuery = $this->select('assessments.student_id, assessments.subject_id, assessments.score, assessments.assessment_date')
                               ->where('assessments.class_id', $classId)
                               ->whereIn('assessments.student_id', $studentIdList)
                               ->whereIn('assessments.subject_id', $subjectIds)
                               ->where('assessments.assessment_type', 'SUMATIF'); // Case-insensitive if DB default, but be explicit.

        // TODO: Add date range filtering for academic_year and semester if $date_from and $date_to are available
        // Example:
        // if (!empty($filters['date_from']) && !empty($filters['date_to'])) {
        //    $allScoresQuery->where('assessment_date >=', $filters['date_from']);
        //    $allScoresQuery->where('assessment_date <=', $filters['date_to']);
        // }
        // Without date_from/date_to, this will average ALL summatives ever for that class/student/subject. This is likely not correct for a specific semester.
        // This part highlights a potential need to refine how assessments are queried for specific academic periods.
        // For the purpose of this function, we'll proceed with averaging scores found matching the core criteria.
        // The controller calling this should ideally provide a date range.

        // Determine date range for the given academic year and semester
        $yearParts = explode('/', $academicYear); // Expects format like "2023/2024"
        $startYear = intval($yearParts[0]);
        // If academic year is just "2023", endYear will be same as startYear.
        // For "2023/2024", startYear = 2023, endYear = 2024.
        $endYear = isset($yearParts[1]) ? intval($yearParts[1]) : $startYear;


        $date_from = null;
        $date_to = null;

        if ($semester == 1) { // Semester Ganjil (July to December of startYear)
            $date_from = $startYear . '-07-01';
            $date_to = $startYear . '-12-31';
        } elseif ($semester == 2) { // Semester Genap (January to June of endYear)
            // If academic year is "2023/2024", semester 2 is Jan-June 2024.
            // If academic year is "2023" (single year), semester 2 implies Jan-June of that same year,
            // which might be unusual unless it's a short course.
            // Assuming standard academic year structure.
            $date_from = $endYear . '-01-01';
            $date_to = $endYear . '-06-30';
        }

        if (!$date_from || !$date_to) {
            log_message('error', "Could not determine valid date range for eRapor export. Academic Year: {$academicYear}, Semester: {$semester}. Exporting with empty scores for this period.");
            // Return empty scores for all students for all subjects to prevent incorrect data export
            $studentsDataEmptyScores = [];
            foreach ($students as $student) {
                $studentScores = [];
                foreach ($subjectIds as $subjectId) {
                    $studentScores[$subjectId] = ''; // Empty score
                }
                $studentsDataEmptyScores[$student['id']] = [
                    'nisn'      => $student['nisn'],
                    'nis'       => $student['nis'],
                    'full_name' => $student['full_name'],
                    'scores'    => $studentScores,
                ];
            }
            return [
                'students' => $studentsDataEmptyScores,
                'subjects' => $subjectsMap,
            ];
        }

        // Proceed with $allScoresQuery only if date_from and date_to are valid
        $allScoresQuery->where('assessments.assessment_date >=', $date_from);
        $allScoresQuery->where('assessments.assessment_date <=', $date_to);

        $allScores = $allScoresQuery->findAll();

        // 4. Process scores: calculate average per student per subject
        $processedScores = []; // [student_id][subject_id] => [scores_array]
        foreach ($allScores as $score) {
            // Consider filtering by assessment_date here if a date range for the semester is known
            // For now, we average all summatives found for that student/subject/class.
            $processedScores[$score['student_id']][$score['subject_id']][] = (float)$score['score'];
        }

        $studentsData = [];
        foreach ($students as $student) {
            $studentScores = [];
            foreach ($subjectIds as $subjectId) {
                if (isset($processedScores[$student['id']][$subjectId]) && !empty($processedScores[$student['id']][$subjectId])) {
                    $scoresForSubject = $processedScores[$student['id']][$subjectId];
                    $averageScore = array_sum($scoresForSubject) / count($scoresForSubject);
                    $studentScores[$subjectId] = round($averageScore); // Round to nearest integer or use specific rounding rule
                } else {
                    $studentScores[$subjectId] = ''; // Or 0, or null, based on e-Rapor requirements for missing values
                }
            }
            $studentsData[$student['id']] = [
                'nisn'      => $student['nisn'],
                'nis'       => $student['nis'],
                'full_name' => $student['full_name'],
                'scores'    => $studentScores,
            ];
        }

        return [
            'students' => $studentsData,
            'subjects' => $subjectsMap, // Map of subjects [id => subject_data]
        ];
    }
}
