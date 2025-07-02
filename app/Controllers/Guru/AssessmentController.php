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
    protected $teacherClassSubjectAssignmentModel; // Added
    protected $classStudentModel; // Added

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
        $this->teacherClassSubjectAssignmentModel = new \App\Models\TeacherClassSubjectAssignmentModel(); // Added
        $this->classStudentModel = new \App\Models\ClassStudentModel(); // Added
    }

    /**
     * Displays a list of classes and subjects for the teacher to choose from.
     * Or, if the teacher is a wali_kelas, maybe directly show their class.
     */
    public function index()
    {
        $loggedInUserId = current_user_id();
        $teacher = $this->teacherModel->where('user_id', $loggedInUserId)->first();
        $teacherId = $teacher ? $teacher['id'] : null;

        $filteredClasses = [];
        $filteredSubjects = [];
        $selectedClassId = $this->request->getGet('class_id'); // Untuk mempertahankan pilihan kelas

        if ($teacherId || isAdmin()) {
            if (isAdmin() && !$teacherId) { // Admin murni, bukan guru
                $filteredClasses = $this->classModel->orderBy('class_name', 'ASC')->findAll();
                // Jika admin dan kelas sudah dipilih, tampilkan semua mapel untuk kelas itu (atau semua mapel jika tidak ada assignment)
                // Namun, admin sebaiknya melihat semua mapel jika tidak ada konteks guru.
                // Untuk admin, jika kelas DIPILIH, filter mapel berdasarkan kelas itu (jika ada assignment) atau semua mapel.
                // Jika kelas BELUM DIPILIH, tampilkan semua mapel.
                if ($selectedClassId) {
                     // Admin bisa melihat semua mapel yang diajarkan di kelas tersebut, atau semua mapel jika tidak ada assignment spesifik
                     // Ini bisa disempurnakan lagi, untuk admin mungkin selalu tampilkan semua mapel saja.
                    $assignedSubjects = $this->teacherClassSubjectAssignmentModel
                        ->distinct()
                        ->select('subjects.*')
                        ->join('subjects', 'subjects.id = teacher_class_subject_assignments.subject_id')
                        ->where('teacher_class_subject_assignments.class_id', $selectedClassId)
                        ->orderBy('subjects.subject_name', 'ASC')
                        ->findAll();
                    if(!empty($assignedSubjects)){
                        $filteredSubjects = $assignedSubjects;
                    } else {
                        $filteredSubjects = $this->subjectModel->orderBy('subject_name', 'ASC')->findAll();
                    }
                } else {
                    $filteredSubjects = $this->subjectModel->orderBy('subject_name', 'ASC')->findAll();
                }

            } else if ($teacherId) { // User adalah Guru (mungkin juga admin)
                $waliKelasClasses = $this->classModel->where('wali_kelas_id', $teacherId)
                                                    ->orderBy('class_name', 'ASC')
                                                    ->findAll();
                if (!empty($waliKelasClasses)) {
                    $filteredClasses = $waliKelasClasses;
                    // Jika wali kelas dan hanya punya 1 kelas perwalian, anggap itu selectedClassId
                    if (count($waliKelasClasses) === 1 && !$selectedClassId) {
                        $selectedClassId = $waliKelasClasses[0]['id'];
                    }
                } else {
                    // Guru non-wali kelas, atau admin yang juga guru tapi bukan wali kelas
                    // Tampilkan semua kelas dimana guru tersebut punya assignment, atau semua kelas jika admin
                    if (isAdmin()){
                        $filteredClasses = $this->classModel->orderBy('class_name', 'ASC')->findAll();
                    } else {
                         $assignedClasses = $this->teacherClassSubjectAssignmentModel
                            ->distinct()
                            ->select('classes.*')
                            ->join('classes', 'classes.id = teacher_class_subject_assignments.class_id')
                            ->where('teacher_class_subject_assignments.teacher_id', $teacherId)
                            ->orderBy('classes.class_name', 'ASC')
                            ->findAll();
                        $filteredClasses = $assignedClasses ?: []; // Jika tidak ada assignment, array kosong
                    }
                }

                if ($selectedClassId) {
                    // Subjects will be loaded by AJAX, but keep $selectedClassId for the view
                }
                 // $filteredSubjects is not pre-populated here anymore, AJAX will handle it.
            }
        } else { // User bukan Guru dan bukan Admin (seharusnya tidak bisa akses karena filter rute)
            session()->setFlashdata('error', 'Access denied or teacher data not found.');
            return redirect()->to('/'); // Redirect ke halaman utama atau error
        }

        if (empty($filteredClasses) && !isAdmin() && $teacherId) {
             session()->setFlashdata('info', 'You are not assigned to any classes yet. Please contact administrator.');
        }


        $data = [
            'pageTitle'       => 'Select Class and Subject for Assessment',
            'classes'         => $filteredClasses,
            // 'subjects'        => $filteredSubjects, // No longer sending subjects directly
            'selectedClassId' => $selectedClassId,
            'formAction'      => site_url('guru/assessments/input'),
            // 'currentUrl'      => site_url('guru/assessments') // Not needed if AJAX handles subject loading without page reload for filter
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

        // --- Authorization Check for Teacher ---
        if (!isAdmin()) {
            $loggedInUserId = current_user_id();
            $teacher = $this->teacherModel->where('user_id', $loggedInUserId)->first();
            $teacherId = $teacher ? $teacher['id'] : null;

            if ($teacherId) {
                $assignment = $this->teacherClassSubjectAssignmentModel
                    ->where('teacher_id', $teacherId)
                    ->where('class_id', $classId)
                    ->where('subject_id', $subjectId)
                    ->first();

                if (!$assignment) {
                    return redirect()->to('guru/assessments')->with('error', 'You are not authorized to input assessments for this class and subject.');
                }
            } else {
                // Should not happen if role is 'Guru' but no teacher record found
                return redirect()->to('guru/assessments')->with('error', 'Teacher data not found. Authorization failed.');
            }
        }
        // --- End Authorization Check ---

        // Fetch students in the class
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

        $loggedInUserId = current_user_id();
        $teacherRecord = $this->teacherModel->where('user_id', $loggedInUserId)->first();

        // An admin might not have a teacher record.
        // A guru MUST have a teacher record.
        if (!isAdmin() && !$teacherRecord) {
            return redirect()->back()->withInput()->with('error', 'Teacher data not found for the logged in user.');
        }
        $dbTeacherId = $teacherRecord ? $teacherRecord['id'] : null;

        // --- Authorization Check for Teacher ---
        // Admin can save for any class/subject, assuming they have a teacher_id if assessments.teacher_id is NOT NULL.
        // If admin is also a teacher, their $dbTeacherId will be used.
        // If admin is NOT a teacher, $dbTeacherId is null. This will fail if assessments.teacher_id is NOT NULL and no default is set.
        // (Migration for assessments states teacher_id is NOT NULL and FK to teachers.id)
        // So, an Admin who is not in `teachers` table cannot save new assessments. This is an existing constraint.
        if (!isAdmin() && $dbTeacherId) { // This check is for Guru role
            $assignment = $this->teacherClassSubjectAssignmentModel
                ->where('teacher_id', $dbTeacherId)
                ->where('class_id', $classId)
                ->where('subject_id', $subjectId)
                ->first();

            if (!$assignment) {
                return redirect()->back()->withInput()->with('error', 'You are not authorized to save assessments for this class and subject.');
            }
        } else if (isAdmin() && !$dbTeacherId) {
            // Admin is trying to save, but their user account is not linked to a teacher record.
            // Since assessments.teacher_id is NOT NULL, this will fail at DB level.
            // It's better to stop it here.
            return redirect()->back()->withInput()->with('error', 'Administrator account is not linked to a teacher record. Cannot save assessment without a valid teacher ID.');
        }
        // --- End Authorization Check ---

        $assessmentsData = $this->request->getPost('assessments');

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

                // --- Validate student is in class ---
                $isStudentInClass = $this->classStudentModel
                    ->where('class_id', $classId)
                    ->where('student_id', $studentId)
                    ->first();

                if (!$isStudentInClass) {
                    $allValid = false;
                    // Find student name for error message
                    $studentInfo = $this->studentModel->find($studentId);
                    $studentName = $studentInfo ? $studentInfo['full_name'] : "ID {$studentId}";
                    if (!isset($errors[$studentId][$index])) $errors[$studentId][$index] = [];
                    $errors[$studentId][$index]['student_class_validation'] = "Student " . esc($studentName) . " is not registered in this class.";
                    continue; // Skip this assessment entry, proceed to next assessment for the student or next student
                }
                // --- End student in class validation ---

                $dataToSave = [
                    'student_id'       => $studentId,
                    'subject_id'       => $subjectId,
                    'class_id'         => $classId,
                    'teacher_id'       => $dbTeacherId,
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
        $teacherId = $teacher ? $teacher['id'] : null;

        // Authorization:
        // Admin can edit any assessment.
        // Teacher can only edit their own assessments for class/subject they are assigned to.
        if (!isAdmin()) {
            if (!$teacherId) { // Guru must have a teacher record
                 return redirect()->to(route_to('guru_assessment_index'))->with('error', 'Teacher data not found. Cannot verify permissions.');
            }
            if ($assessment['teacher_id'] != $teacherId) { // Must be creator
                return redirect()->to(route_to('guru_assessment_index'))->with('error', 'You are not authorized to edit this assessment as it was created by another teacher.');
            }
            // Must be assigned to teach this class/subject
            $assignment = $this->teacherClassSubjectAssignmentModel
                ->where('teacher_id', $teacherId)
                ->where('class_id', $assessment['class_id'])
                ->where('subject_id', $assessment['subject_id'])
                ->first();
            if (!$assignment) {
                 return redirect()->to(route_to('guru_assessment_index'))->with('error', 'You are not authorized to edit assessments for this class/subject, or you are no longer assigned.');
            }
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
        $teacherId = $teacher ? $teacher['id'] : null;

        // Authorization: Similar to editAssessment
        if (!isAdmin()) {
            if (!$teacherId) {
                return redirect()->to(route_to('guru_assessment_index'))->with('error', 'Teacher data not found. Cannot verify permissions.');
            }
            if ($assessment['teacher_id'] != $teacherId) {
                 return redirect()->to(route_to('guru_assessment_index'))->with('error', 'You are not authorized to update this assessment as it was created by another teacher.');
            }
            $assignment = $this->teacherClassSubjectAssignmentModel
                ->where('teacher_id', $teacherId)
                ->where('class_id', $assessment['class_id'])
                ->where('subject_id', $assessment['subject_id'])
                ->first();
            if (!$assignment) {
                 return redirect()->to(route_to('guru_assessment_index'))->with('error', 'You are not authorized to update assessments for this class/subject, or you are no longer assigned.');
            }
        }

        // $rules = $this->assessmentModel->getValidationRules([]); // Not needed if using direct save with model's built-in validation

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
        $teacherId = $teacher ? $teacher['id'] : null;

        // Authorization: Similar to editAssessment
        if (!isAdmin()) {
            if (!$teacherId) {
                return redirect()->to(route_to('guru_assessment_index'))->with('error', 'Teacher data not found. Cannot verify permissions.');
            }
            if ($assessment['teacher_id'] != $teacherId) {
                return redirect()->to(route_to('guru_assessment_index'))->with('error', 'You are not authorized to delete this assessment as it was created by another teacher.');
            }
            $assignment = $this->teacherClassSubjectAssignmentModel
                ->where('teacher_id', $teacherId)
                ->where('class_id', $assessment['class_id'])
                ->where('subject_id', $assessment['subject_id'])
                ->first();
            if (!$assignment) {
                return redirect()->to(route_to('guru_assessment_index'))->with('error', 'You are not authorized to delete assessments for this class/subject, or you are no longer assigned.');
            }
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

    /**
     * Displays a form for the teacher to select class and subject to view assessment recap.
     */
    public function showRecapSelection()
    {
        $loggedInUserId = current_user_id();
        $teacher = $this->teacherModel->where('user_id', $loggedInUserId)->first();
        $teacherId = $teacher ? $teacher['id'] : null;

        $filteredClasses = [];
        $filteredSubjects = [];
        $selectedClassId = $this->request->getGet('class_id'); // Untuk mempertahankan pilihan kelas

        if ($teacherId || isAdmin()) {
            if (isAdmin() && !$teacherId) { // Admin murni
                $filteredClasses = $this->classModel->orderBy('class_name', 'ASC')->findAll();
                if ($selectedClassId) {
                    $assignedSubjects = $this->teacherClassSubjectAssignmentModel
                        ->distinct()
                        ->select('subjects.*')
                        ->join('subjects', 'subjects.id = teacher_class_subject_assignments.subject_id')
                        ->where('teacher_class_subject_assignments.class_id', $selectedClassId)
                        ->orderBy('subjects.subject_name', 'ASC')
                        ->findAll();
                    if(!empty($assignedSubjects)){
                        $filteredSubjects = $assignedSubjects;
                    } else {
                        $filteredSubjects = $this->subjectModel->orderBy('subject_name', 'ASC')->findAll();
                    }
                } else {
                    $filteredSubjects = $this->subjectModel->orderBy('subject_name', 'ASC')->findAll();
                }
            } else if ($teacherId) { // User adalah Guru
                $waliKelasClasses = $this->classModel->where('wali_kelas_id', $teacherId)
                                                    ->orderBy('class_name', 'ASC')
                                                    ->findAll();
                if (!empty($waliKelasClasses)) {
                    $filteredClasses = $waliKelasClasses;
                    if (count($waliKelasClasses) === 1 && !$selectedClassId) {
                        $selectedClassId = $waliKelasClasses[0]['id'];
                    }
                } else {
                    if (isAdmin()){ // Admin yg juga guru, bukan wali kelas
                        $filteredClasses = $this->classModel->orderBy('class_name', 'ASC')->findAll();
                    } else { // Guru murni, bukan wali kelas
                         $assignedClasses = $this->teacherClassSubjectAssignmentModel
                            ->distinct()
                            ->select('classes.*')
                            ->join('classes', 'classes.id = teacher_class_subject_assignments.class_id')
                            ->where('teacher_class_subject_assignments.teacher_id', $teacherId)
                            ->orderBy('classes.class_name', 'ASC')
                            ->findAll();
                        $filteredClasses = $assignedClasses ?: [];
                    }
                }

                if ($selectedClassId) {
                    // Subjects will be loaded by AJAX
                }
                // $filteredSubjects is not pre-populated here anymore, AJAX will handle it.
            }
        } else { // User bukan Guru dan bukan Admin
            session()->setFlashdata('error', 'Access denied or teacher data not found.');
            return redirect()->to('/');
        }

        if (empty($filteredClasses) && !isAdmin() && $teacherId) {
             session()->setFlashdata('info', 'You are not assigned to any classes yet. Please contact administrator.');
        }

        $data = [
            'pageTitle'       => 'Select Class and Subject for Recap',
            'classes'         => $filteredClasses,
            // 'subjects'        => $filteredSubjects, // No longer sending subjects directly
            'selectedClassId' => $selectedClassId,
            'formAction'      => site_url('guru/assessments/show-recap'),
            // 'currentUrl'      => site_url('guru/assessments/recap') // Not needed for AJAX
        ];
        return view('guru/assessments/select_recap_context', $data);
    }

    /**
     * Displays the assessment recap for the selected class and subject.
     */
    public function displayRecap()
    {
        $classId = $this->request->getGet('class_id');
        $subjectId = $this->request->getGet('subject_id');

        if (empty($classId) || empty($subjectId)) {
            return redirect()->to(route_to('guru_assessment_recap_select'))->with('error', 'Please select both class and subject for the recap.');
        }

        $classInfo = $this->classModel->find($classId);
        $subjectInfo = $this->subjectModel->find($subjectId);

        if (!$classInfo || !$subjectInfo) {
            return redirect()->to(route_to('guru_assessment_recap_select'))->with('error', 'Invalid class or subject selected.');
        }

        // --- Authorization Check for Teacher (for recap view) ---
        // Admin can view any recap.
        // Teacher can view recap if they teach the subject in that class OR if they are the wali kelas of that class.
        if (!isAdmin()) {
            $loggedInUserId = current_user_id();
            $teacher = $this->teacherModel->where('user_id', $loggedInUserId)->first();
            $teacherId = $teacher ? $teacher['id'] : null;

            if ($teacherId) {
                $isAssignedToTeach = $this->teacherClassSubjectAssignmentModel
                    ->where('teacher_id', $teacherId)
                    ->where('class_id', $classId)
                    ->where('subject_id', $subjectId)
                    ->first();

                $isWaliKelas = ($classInfo && $classInfo['wali_kelas_id'] == $teacherId);

                if (!$isAssignedToTeach && !$isWaliKelas) {
                    return redirect()->to(route_to('guru_assessment_recap_select'))->with('error', 'You are not authorized to view the recap for this class and subject.');
                }
            } else { // Guru role but no teacher record
                return redirect()->to(route_to('guru_assessment_recap_select'))->with('error', 'Teacher data not found. Authorization failed.');
            }
        }
        // --- End Authorization Check ---

        // Fetch assessments
        $assessments = $this->assessmentModel->getAssessmentsForRecap($classId, $subjectId);

        // Group assessments by student for easier display in the view
        $studentsWithAssessments = [];
        foreach ($assessments as $assessment) {
            if (!isset($studentsWithAssessments[$assessment['student_id']])) {
                $studentsWithAssessments[$assessment['student_id']] = [
                    'student_id'   => $assessment['student_id'],
                    'student_name' => $assessment['student_name'],
                    'student_nisn' => $assessment['student_nisn'],
                    'assessments'  => []
                ];
            }
            $studentsWithAssessments[$assessment['student_id']]['assessments'][] = $assessment;
        }

        // Sort students by name if the keys are student_id (numeric)
        // ksort($studentsWithAssessments); // if keys are numeric student_id
        // If using student_name as key, it would be sorted.
        // Or sort the final array by student_name if needed, though SQL ORDER BY should handle it.

        $data = [
            'pageTitle'             => 'Assessment Recap: ' . esc($classInfo['class_name']) . ' - ' . esc($subjectInfo['subject_name']),
            'classInfo'             => $classInfo,
            'subjectInfo'           => $subjectInfo,
            'studentsWithAssessments' => $studentsWithAssessments,
        ];

        return view('guru/assessments/recap_display', $data);
    }

    /**
     * AJAX handler to get subjects for a given class, filtered by the logged-in teacher.
     * If the user is a pure Admin (not linked to a teacher record), it returns all subjects taught in that class.
     *
     * @param int $classId
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function ajaxGetSubjectsForClass(int $classId)
    {
        if (!$this->request->isAJAX()) {
            // Or throw a 403/404 error
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Direct access not allowed.']);
        }

        $loggedInUserId = current_user_id();
        $teacher = $this->teacherModel->where('user_id', $loggedInUserId)->first();
        $teacherId = $teacher ? $teacher['id'] : null;

        $subjects = [];

        if ($classId > 0) { // Basic validation for classId
            if ($teacherId) { // User is a Teacher (or Admin who is also a Teacher)
                $subjects = $this->teacherClassSubjectAssignmentModel->getSubjectsForTeacherInClass($teacherId, $classId);
            } elseif (isAdmin()) { // User is a pure Admin (not a Teacher)
                // Admin gets to see all subjects taught in that class by any teacher
                $subjects = $this->teacherClassSubjectAssignmentModel->getDistinctSubjectsForClass($classId);
                 if (empty($subjects)) { // Fallback for admin if no assignments exist for the class
                    // $subjects = $this->subjectModel->orderBy('subject_name', 'ASC')->findAll();
                 }
            }
            // If not a teacher and not an admin, $subjects remains empty, access should be blocked by route filter anyway.
        }

        return $this->response->setJSON($subjects);
    }
}
