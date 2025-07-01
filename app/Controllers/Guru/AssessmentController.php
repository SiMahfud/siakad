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
        $loggedInUserId = current_user_id();
        $teacher = $this->teacherModel->where('user_id', $loggedInUserId)->first();

        $filteredClasses = [];
        $allSubjects = $this->subjectModel->orderBy('subject_name', 'ASC')->findAll();

        if ($teacher) {
            $teacherId = $teacher['id'];
            // Cek apakah guru ini adalah wali kelas
            $waliKelasClasses = $this->classModel->where('wali_kelas_id', $teacherId)
                                                ->orderBy('class_name', 'ASC')
                                                ->findAll();

            if (!empty($waliKelasClasses)) {
                // Jika guru adalah wali kelas, tampilkan hanya kelas perwaliannya
                $filteredClasses = $waliKelasClasses;
            } else {
                // Jika guru bukan wali kelas di kelas manapun, tampilkan semua kelas
                // Ini asumsi agar guru mapel non-wali kelas tetap bisa input.
                // Idealnya, ini akan diganti dengan query ke tabel penugasan guru-mapel-kelas.
                $filteredClasses = $this->classModel->orderBy('class_name', 'ASC')->findAll();
            }
        } else {
            // Jika tidak ada data guru terkait user login (seharusnya tidak terjadi jika user adalah Guru)
            // atau jika user adalah Administrator Sistem yang ingin melihat semua, tampilkan semua kelas.
            // Untuk Administrator Sistem, mereka mungkin perlu akses ke semua kelas.
            // Peran 'Guru' seharusnya selalu punya record di tabel 'teachers'.
            if (isAdmin()) { // isAdmin() adalah helper dari auth_helper
                 $filteredClasses = $this->classModel->orderBy('class_name', 'ASC')->findAll();
            } else {
                // Untuk guru yang tidak ditemukan datanya, beri array kosong atau pesan error.
                // Untuk saat ini, beri array kosong.
                $filteredClasses = [];
                 session()->setFlashdata('error', 'Teacher data not found for your account. Please contact administrator.');
            }
        }

        // Untuk mata pelajaran, sesuai rencana awal, tampilkan semua.
        // Ini juga akan memerlukan tabel penugasan guru-mapel-kelas untuk filter yang lebih akurat.
        $data = [
            'pageTitle' => 'Select Class and Subject for Assessment',
            'classes'   => $filteredClasses,
            'subjects'  => $allSubjects, // Semua mata pelajaran
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

                // --- Custom validation logic before model validation ---
                $currentEntryErrors = [];
                if (empty($dataToSave['assessment_type'])) {
                    $currentEntryErrors['assessment_type'] = 'Assessment type is required.';
                }
                if (empty($dataToSave['assessment_date'])) { // Model validation now handles this as 'required'
                    // This check is now redundant if model validation for assessment_date is 'required'
                    // $currentEntryErrors['assessment_date'] = 'Assessment date is required.';
                }
                if (empty($dataToSave['assessment_title']) && (!empty($dataToSave['score']) || !empty($dataToSave['description']))) {
                    // Title is required if there's a score or description
                    $currentEntryErrors['assessment_title'] = 'Assessment title is required if score or description is provided.';
                }
                if ($dataToSave['assessment_type'] === 'SUMATIF' && $dataToSave['score'] === null) {
                    $currentEntryErrors['score'] = 'Score is required for Summative assessment.';
                }
                // --- End custom validation ---

                if (!empty($currentEntryErrors)) {
                    $allValid = false;
                    if (!isset($errors[$studentId][$index])) $errors[$studentId][$index] = [];
                    $errors[$studentId][$index] = array_merge($errors[$studentId][$index], $currentEntryErrors);
                }

                // Validate with model rules IF no custom errors found yet for this entry OR if we want model errors too
                if (empty($currentEntryErrors)) { // Only run model validation if custom checks pass for this entry
                    if ($this->assessmentModel->validate($dataToSave)) {
                        $processedData[] = $dataToSave;
                    } else {
                        $allValid = false;
                        if (!isset($errors[$studentId][$index])) $errors[$studentId][$index] = [];
                        $errors[$studentId][$index] = array_merge($errors[$studentId][$index], $this->assessmentModel->errors());
                    }
                } else {
                    $allValid = false; // Ensure allValid is false if custom errors exist
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

    /**
     * Displays the form to edit an existing assessment.
     */
    public function editAssessment($assessmentId)
    {
        $assessment = $this->assessmentModel->find($assessmentId);

        if (!$assessment) {
            return redirect()->to('guru/assessments')->with('error', 'Assessment not found.');
        }

        // Authorization: Only the teacher who created it or an admin can edit.
        $loggedInUserId = current_user_id();
        $teacher = $this->teacherModel->where('user_id', $loggedInUserId)->first();

        if (!$teacher && !isAdmin()) {
            return redirect()->to('guru/assessments')->with('error', 'Teacher data not found. Cannot verify permissions.');
        }

        // Admins can edit anything. For teachers, check if they are the ones who created the assessment.
        if (!isAdmin() && ($teacher && $assessment['teacher_id'] != $teacher['id'])) {
            return redirect()->to('guru/assessments')->with('error', 'You are not authorized to edit this assessment.');
        }

        $student = $this->studentModel->find($assessment['student_id']);
        $classInfo = $this->classModel->find($assessment['class_id']);
        $subjectInfo = $this->subjectModel->find($assessment['subject_id']);

        $data = [
            'pageTitle'   => 'Edit Assessment',
            'assessment'  => $assessment,
            'student'     => $student,
            'classInfo'   => $classInfo,
            'subjectInfo' => $subjectInfo,
            'validation'  => \Config\Services::validation()
        ];

        return view('guru/assessments/edit_form', $data);
    }

    /**
     * Updates an existing assessment.
     */
    public function updateAssessment($assessmentId)
    {
        $assessment = $this->assessmentModel->find($assessmentId);

        if (!$assessment) {
            return redirect()->to('guru/assessments')->with('error', 'Assessment not found.');
        }

        // Authorization: Only the teacher who created it or an admin can update.
        $loggedInUserId = current_user_id();
        $teacher = $this->teacherModel->where('user_id', $loggedInUserId)->first();

        if (!$teacher && !isAdmin()) {
            return redirect()->to('guru/assessments')->with('error', 'Teacher data not found. Cannot verify permissions.');
        }

        if (!isAdmin() && ($teacher && $assessment['teacher_id'] != $teacher['id'])) {
            return redirect()->to('guru/assessments')->with('error', 'You are not authorized to update this assessment.');
        }

        $rules = $this->assessmentModel->getValidationRules([
            // if specific rules for update are needed, define here
            // e.g., 'assessment_title' => 'required|max_length[255]',
        ]);

        // Custom validation logic similar to saveAssessments
        $dataToUpdate = [
            'id'               => $assessmentId, // Important for update
            'student_id'       => $assessment['student_id'], // Usually not changed during edit of score
            'subject_id'       => $assessment['subject_id'], // Usually not changed
            'class_id'         => $assessment['class_id'],   // Usually not changed
            'teacher_id'       => $assessment['teacher_id'], // Original teacher_id
            'assessment_type'  => $this->request->getPost('assessment_type'),
            'assessment_title' => $this->request->getPost('assessment_title'),
            'assessment_date'  => $this->request->getPost('assessment_date'),
            'score'            => ($this->request->getPost('assessment_type') === 'SUMATIF') ? $this->request->getPost('score') : null,
            'description'      => $this->request->getPost('description'),
        ];

        $errors = [];
        if (empty($dataToUpdate['assessment_type'])) {
            $errors['assessment_type'] = 'Assessment type is required.';
        }
        if (empty($dataToUpdate['assessment_date'])) {
            $errors['assessment_date'] = 'Assessment date is required.';
        }
        if (empty($dataToUpdate['assessment_title']) && (!empty($dataToUpdate['score']) || !empty($dataToUpdate['description']))) {
            $errors['assessment_title'] = 'Assessment title is required if score or description is provided.';
        }
        if ($dataToUpdate['assessment_type'] === 'SUMATIF' && ($dataToUpdate['score'] === null || $dataToUpdate['score'] === '')) {
            $errors['score'] = 'Score is required for Summative assessment.';
        }


        if (!empty($errors)) {
             return redirect()->back()->withInput()->with('validation_errors', $errors);
        }

        if ($this->assessmentModel->validate($dataToUpdate)) {
            if ($this->assessmentModel->save($dataToUpdate)) { // save() handles insert or update
                // Redirect to a relevant page, e.g., assessment list or back to context selection
                return redirect()->to("guru/assessments/input?class_id={$assessment['class_id']}&subject_id={$assessment['subject_id']}")->with('success', 'Assessment updated successfully.');
            } else {
                return redirect()->back()->withInput()->with('error', 'Failed to update assessment due to a database error.');
            }
        } else {
            return redirect()->back()->withInput()->with('validation_errors', $this->assessmentModel->errors());
        }
    }

    /**
     * Deletes an existing assessment.
     */
    public function deleteAssessment($assessmentId)
    {
        $assessment = $this->assessmentModel->find($assessmentId);

        if (!$assessment) {
            return redirect()->to('guru/assessments')->with('error', 'Assessment not found.');
        }

        // Authorization: Only the teacher who created it or an admin can delete.
        $loggedInUserId = current_user_id();
        $teacher = $this->teacherModel->where('user_id', $loggedInUserId)->first();

        if (!$teacher && !isAdmin()) {
            return redirect()->to('guru/assessments')->with('error', 'Teacher data not found. Cannot verify permissions.');
        }

        if (!isAdmin() && ($teacher && $assessment['teacher_id'] != $teacher['id'])) {
            return redirect()->to('guru/assessments')->with('error', 'You are not authorized to delete this assessment.');
        }

        // Store class_id and subject_id for redirect before deleting
        $classId = $assessment['class_id'];
        $subjectId = $assessment['subject_id'];

        if ($this->assessmentModel->delete($assessmentId)) {
            return redirect()->to("guru/assessments/input?class_id={$classId}&subject_id={$subjectId}")->with('success', 'Assessment deleted successfully.');
        } else {
            // This part might not be reached if delete() throws an exception on failure,
            // or if DB errors are handled differently. But good to have a fallback.
            return redirect()->to("guru/assessments/input?class_id={$classId}&subject_id={$subjectId}")->with('error', 'Failed to delete assessment.');
        }
    }
}
