<?php

namespace App\Controllers\Guru;

use App\Controllers\BaseController;
use App\Models\AssessmentModel;
use App\Models\ClassModel;
use App\Models\SubjectModel;
use App\Models\StudentModel;
use App\Models\TeacherModel; // Untuk mendapatkan data guru yang login

class AssessmentController extends BaseController
{
    protected $assessmentModel;
    protected $classModel;
    protected $subjectModel;
    protected $studentModel;
    protected $teacherModel;

    public function __construct()
    {
        helper(['form', 'url', 'auth']);

        // Basic role check - Adjust as per more granular permissions later
        // For now, allow Guru and Administrator Sistem (for testing/override)
        if (!hasRole(['Guru', 'Administrator Sistem'])) {
            // This will throw a 404 error if the route is accessed directly by wrong role
            // Or redirect to an unauthorized page if preferred and route for it exists
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('You do not have permission to access this module.');
        }

        $this->assessmentModel = new AssessmentModel();
        $this->classModel = new ClassModel();
        $this->subjectModel = new SubjectModel();
        $this->studentModel = new StudentModel();
        $this->teacherModel = new TeacherModel();
    }

    /**
     * Displays a list of classes and subjects for the teacher to choose from.
     * Or, if the teacher is a wali_kelas, maybe directly show their class.
     */
    public function index()
    {
        // For MVP, let's list all classes and subjects.
        // Later, this should be filtered by classes/subjects taught by the logged-in teacher.
        $data = [
            'pageTitle' => 'Select Class and Subject for Assessment',
            'classes'   => $this->classModel->orderBy('class_name', 'ASC')->findAll(),
            'subjects'  => $this->subjectModel->orderBy('subject_name', 'ASC')->findAll(),
        ];
        return view('guru/assessments/select_context', $data);
    }

    /**
     * Shows the form for inputting assessments for a specific class and subject.
     * This method might be called via GET after selection from index,
     * or directly if class_id and subject_id are known.
     */
    public function showInputForm()
    {
        $classId = $this->request->getGet('class_id');
        $subjectId = $this->request->getGet('subject_id');

        if (empty($classId) || empty($subjectId)) {
            return redirect()->to('guru/assessments')->with('error', 'Please select both class and subject.');
        }

        $classInfo = $this->classModel->find($classId);
        $subjectInfo = $this->subjectModel->find($subjectId);

        if (!$classInfo || !$subjectInfo) {
            return redirect()->to('guru/assessments')->with('error', 'Invalid class or subject selected.');
        }

        // Fetch students in the class
        // This requires class_student table to be populated.
        // For now, let's assume StudentModel can fetch by class_id if such a method exists or is added.
        // Or, more correctly, use a join through class_student table.
        // $students = $this->studentModel->where('class_id', $classId)->findAll(); // Simplified for now

        // Correct way to get students of a class (assuming class_student table is managed elsewhere)
        $students = $this->studentModel
            ->select('students.*')
            ->join('class_student', 'class_student.student_id = students.id')
            ->where('class_student.class_id', $classId)
            ->orderBy('students.full_name', 'ASC')
            ->findAll();


        $data = [
            'pageTitle'   => 'Input Assessment Scores',
            'classInfo'   => $classInfo,
            'subjectInfo' => $subjectInfo,
            'students'    => $students,
            'validation'  => \Config\Services::validation()
        ];

        return view('guru/assessments/input_form', $data);
    }


    /**
     * Saves the assessment data submitted from the form.
     */
    public function saveAssessments()
    {
        // This will be complex as it involves multiple student entries.
        // Each student might have multiple assessments (formative/sumative lines in the form).
        // Data will likely come as arrays.

        // Example: $this->request->getPost('assessments');
        // where 'assessments' is an array like:
        // [
        //   student_id_1 => [
        //     ['type' => 'FORMATIF', 'title' => 'Tugas 1', 'date' => '2023-01-15', 'description' => 'Baik'],
        //     ['type' => 'SUMATIF', 'title' => 'UTS', 'date' => '2023-03-10', 'score' => 85],
        //   ],
        //   student_id_2 => [ ... ]
        // ]

        // For MVP, let's assume a simpler structure first if the form submits one assessment type at a time.
        // Or, handle a batch of similar assessments for all students.

        // Placeholder logic:
        // 1. Get current teacher_id from session.
        // 2. Get class_id, subject_id from hidden form fields or POST data.
        // 3. Loop through $this->request->getPost('student_scores') or similar array.
        // 4. For each student:
        //    a. Prepare data array for AssessmentModel.
        //    b. Validate.
        //    c. Insert if valid.
        // 5. Collect errors or success messages.
        // 6. Redirect with flash data.

        $classId = $this->request->getPost('class_id');
        $subjectId = $this->request->getPost('subject_id');
        $teacherId = session()->get('user_id'); // Assuming teacher's user_id is their teacher_id in assessments.
                                               // This might need adjustment if `teachers.id` is different from `users.id`.
                                               // For now, let's find teacher record by user_id.
        $teacherRecord = $this->teacherModel->where('user_id', session()->get('user_id'))->first();
        if(!$teacherRecord){
             return redirect()->back()->withInput()->with('error', 'Teacher data not found for the logged in user.');
        }
        $dbTeacherId = $teacherRecord['id'];


        $assessmentsData = $this->request->getPost('assessments'); // Expects an array of assessments

        $allValid = true;
        $errors = [];
        $processedData = [];

        if (empty($assessmentsData)) {
            return redirect()->back()->withInput()->with('error', 'No assessment data submitted.');
        }

        foreach ($assessmentsData as $studentId => $studentAssessments) {
            foreach($studentAssessments as $index => $assessment) {
                // Skip if essential fields like type or title are empty, or if it's a template row
                if (empty($assessment['assessment_type']) && empty($assessment['assessment_title']) && empty($assessment['score']) && empty($assessment['description'])) {
                    continue;
                }

                $dataToSave = [
                    'student_id'       => $studentId,
                    'subject_id'       => $subjectId,
                    'class_id'         => $classId,
                    'teacher_id'       => $dbTeacherId, // Use actual teacher ID from teachers table
                    'assessment_type'  => $assessment['assessment_type'],
                    'assessment_title' => $assessment['assessment_title'],
                    'assessment_date'  => !empty($assessment['assessment_date']) ? $assessment['assessment_date'] : null,
                    'score'            => ($assessment['assessment_type'] === 'SUMATIF' && !empty($assessment['score'])) ? $assessment['score'] : null,
                    'description'      => !empty($assessment['description']) ? $assessment['description'] : null,
                ];

                // Basic check for required fields based on type
                if ($dataToSave['assessment_type'] === 'SUMATIF' && $dataToSave['score'] === null) {
                     $errors[$studentId][$index][] = "Score is required for Summative assessment.";
                     $allValid = false;
                }
                 if (empty($dataToSave['assessment_date'])) {
                    $errors[$studentId][$index][] = "Assessment date is required.";
                    $allValid = false;
                }


                if ($this->assessmentModel->validate($dataToSave)) {
                    $processedData[] = $dataToSave;
                } else {
                    $allValid = false;
                    $errors[$studentId][$index] = $this->assessmentModel->errors();
                }
            }
        }

        if ($allValid && !empty($processedData)) {
            if ($this->assessmentModel->insertBatch($processedData)) {
                return redirect()->to("guru/assessments/input?class_id={$classId}&subject_id={$subjectId}")->with('success', 'Assessments saved successfully.');
            } else {
                return redirect()->back()->withInput()->with('error', 'Failed to save assessments due to a database error.');
            }
        } else {
            // Store errors in flash session to display them back on the form
            // This is a bit tricky with complex array structures for errors.
            // For simplicity, just a generic error for now if validation fails for any.
            // A more robust solution would repopulate form with errors per field.
            return redirect()->back()->withInput()->with('validation_errors', $errors)->with('error', 'Please correct the errors in the form.');
        }
    }
}
