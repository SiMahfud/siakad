<?php

namespace App\Controllers\Siswa;

use App\Controllers\BaseController;
use App\Models\StudentModel;
use App\Models\SubjectOfferingModel;
use App\Models\StudentSubjectChoiceModel;
use App\Models\ClassModel; // To check student's phase if needed


class SubjectChoiceController extends BaseController
{
    protected $studentModel;
    protected $subjectOfferingModel;
    protected $studentSubjectChoiceModel;
    protected $classModel;
    protected $loggedInStudentId;
    protected $loggedInStudentFase; // To determine if eligible for subject choice

    // Define selection rules here or load from a config/DB setting
    protected $maxChoices = 2; // Example: Student can choose max 2 subjects
    protected $currentAcademicYear; // Should be set from a global config or dynamically
    protected $currentSemester;     // Should be set from a global config or dynamically

    public function __construct()
    {
        helper(['form', 'url', 'auth']);
        if (!hasRole(['Siswa'])) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Unauthorized access attempt.');
        }

        $this->studentModel = new StudentModel();
        $this->subjectOfferingModel = new SubjectOfferingModel();
        $this->studentSubjectChoiceModel = new StudentSubjectChoiceModel();
        $this->classModel = new ClassModel();

        // Simulate getting current academic year and semester (replace with actual logic)
        $this->currentAcademicYear = '2023/2024'; // Placeholder
        $this->currentSemester = 1;               // Placeholder


        $loggedInUserId = current_user_id();
        if ($loggedInUserId) {
            $student = $this->studentModel->where('user_id', $loggedInUserId)->first();
            if ($student) {
                $this->loggedInStudentId = $student['id'];
                // Determine student's phase (e.g., from their class)
                // This is a simplified way; a more robust method would be needed
                $classStudentModel = new \App\Models\ClassStudentModel();
                $currentClass = $classStudentModel
                                ->select('classes.fase')
                                ->join('classes', 'classes.id = class_student.class_id')
                                ->where('class_student.student_id', $this->loggedInStudentId)
                                // Add filter for current academic year if class assignment is historical
                                ->orderBy('classes.academic_year', 'DESC') // Get most recent class
                                ->first();
                if ($currentClass) {
                    $this->loggedInStudentFase = $currentClass['fase'];
                }
            }
        }
    }

    private function isEligibleForChoice(): bool
    {
        // Example eligibility: Student must be in Fase F
        // And selection period must be active (this part needs a setting/config)
        return $this->loggedInStudentFase === 'F';
    }

    public function index()
    {
        if (!$this->loggedInStudentId) {
            return redirect()->to('/')->with('error', 'Student data not found.');
        }
        if (!$this->isEligibleForChoice()) {
            return redirect()->to('/')->with('error', 'You are not eligible to make subject choices at this time or not in the required phase (Fase F).');
        }

        $offerings = $this->subjectOfferingModel->getOfferingsWithDetails([
            'is_active' => true,
            'academic_year' => $this->currentAcademicYear,
            'semester' => $this->currentSemester
        ]);

        $studentChoicesRaw = $this->studentSubjectChoiceModel
                                ->where('student_id', $this->loggedInStudentId)
                                ->join('subject_offerings so', 'so.id = student_subject_choices.subject_offering_id')
                                ->where('so.academic_year', $this->currentAcademicYear)
                                ->where('so.semester', $this->currentSemester)
                                ->findAll();

        $studentChoicesMap = array_column($studentChoicesRaw, 'subject_offering_id');
        $currentChoiceCount = count($studentChoicesMap);

        $data = [
            'pageTitle' => 'Elective Subject Choice',
            'offerings' => $offerings,
            'studentChoicesMap' => $studentChoicesMap, // Array of chosen offering IDs
            'currentChoiceCount' => $currentChoiceCount,
            'maxChoices' => $this->maxChoices,
            'academicYear' => $this->currentAcademicYear,
            'semester' => $this->currentSemester,
        ];

        return view('siswa/subject_choices/index', $data);
    }

    public function processChoice()
    {
        if (!$this->request->isAJAX() && !$this->request->is('post')) {
             return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Invalid request method.']);
        }

        if (!$this->loggedInStudentId) {
            return $this->response->setStatusCode(401)->setJSON(['status' => 'error', 'message' => 'Student data not found.']);
        }
        if (!$this->isEligibleForChoice()) {
             return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'You are not eligible to make subject choices.']);
        }

        $offeringId = $this->request->getPost('offering_id');
        $action = $this->request->getPost('action'); // 'choose' or 'unchoose'

        if (empty($offeringId) || !in_array($action, ['choose', 'unchoose'])) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Invalid offering ID or action.']);
        }

        $offering = $this->subjectOfferingModel
                        ->where('id', $offeringId)
                        ->where('is_active', true)
                        ->where('academic_year', $this->currentAcademicYear) // Ensure it's for current period
                        ->where('semester', $this->currentSemester)
                        ->first();

        if (!$offering) {
            return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => 'Subject offering not found or not active.']);
        }

        $db = \Config\Database::connect();
        $db->transStart();

        $message = '';
        $success = false;

        if ($action === 'choose') {
            // Check current choices count
            $currentChoices = $this->studentSubjectChoiceModel
                                ->where('student_id', $this->loggedInStudentId)
                                ->join('subject_offerings so', 'so.id = student_subject_choices.subject_offering_id')
                                ->where('so.academic_year', $this->currentAcademicYear)
                                ->where('so.semester', $this->currentSemester)
                                ->countAllResults();

            if ($currentChoices >= $this->maxChoices) {
                $db->transRollback(); // Ensure no transaction is left open
                return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'You have reached the maximum number of choices ('.$this->maxChoices.').']);
            }

            // Check quota
            if ($offering['max_quota'] !== null && $offering['current_enrollment'] >= $offering['max_quota']) {
                $db->transRollback();
                return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'This subject offering is full.']);
            }

            // Add choice
            $choiceData = [
                'student_id' => $this->loggedInStudentId,
                'subject_offering_id' => $offeringId,
                'status' => StudentSubjectChoiceModel::STATUS_CHOSEN_PENDING
            ];
            if ($this->studentSubjectChoiceModel->insert($choiceData)) {
                $this->subjectOfferingModel->incrementEnrollment($offeringId);
                $message = 'Subject chosen successfully.';
                $success = true;
            } else {
                log_message('error', 'Failed to insert student choice: ' . json_encode($this->studentSubjectChoiceModel->errors()));
                $message = 'Failed to choose subject.';
            }
        } elseif ($action === 'unchoose') {
            $existingChoice = $this->studentSubjectChoiceModel
                                ->where('student_id', $this->loggedInStudentId)
                                ->where('subject_offering_id', $offeringId)
                                ->first();
            if ($existingChoice) {
                if ($this->studentSubjectChoiceModel->delete($existingChoice['id'])) {
                    $this->subjectOfferingModel->decrementEnrollment($offeringId);
                    $message = 'Subject unchosen successfully.';
                    $success = true;
                } else {
                    log_message('error', 'Failed to delete student choice: ' . json_encode($this->studentSubjectChoiceModel->errors()));
                    $message = 'Failed to unchoose subject.';
                }
            } else {
                $message = 'Choice not found to unchoose.';
            }
        }

        $db->transComplete();

        if ($success && $db->transStatus() !== false) {
            // Recalculate currentChoiceCount for the response
            $currentChoiceCount = $this->studentSubjectChoiceModel
                                ->where('student_id', $this->loggedInStudentId)
                                ->join('subject_offerings so', 'so.id = student_subject_choices.subject_offering_id')
                                ->where('so.academic_year', $this->currentAcademicYear)
                                ->where('so.semester', $this->currentSemester)
                                ->countAllResults();
            $offeringAfterUpdate = $this->subjectOfferingModel->find($offeringId); // Get updated enrollment

            return $this->response->setJSON([
                'status' => 'success',
                'message' => $message,
                'currentChoiceCount' => $currentChoiceCount,
                'maxChoices' => $this->maxChoices,
                'offeringId' => $offeringId,
                'newEnrollment' => $offeringAfterUpdate ? $offeringAfterUpdate['current_enrollment'] : $offering['current_enrollment']
            ]);
        } else {
            $errorMsg = $message ?: 'An error occurred during the operation.';
            if ($db->transStatus() === false) {
                $errorMsg .= ' Database transaction failed.';
            }
            return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => $errorMsg]);
        }
    }
}
