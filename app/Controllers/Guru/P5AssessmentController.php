<?php

namespace App\Controllers\Guru;

use App\Controllers\BaseController;
use App\Models\P5ProjectModel;
use App\Models\P5ProjectStudentModel;
use App\Models\P5AssessmentModel;
use App\Models\TeacherModel;
use App\Models\P5SubElementModel;
use App\Models\P5ProjectFacilitatorModel; // Added

class P5AssessmentController extends BaseController
{
    protected $p5ProjectModel;
    protected $p5ProjectStudentModel;
    protected $p5AssessmentModel;
    protected $teacherModel;
    protected $p5SubElementModel;
    protected $p5ProjectFacilitatorModel; // Added
    protected $teacherId;

    public function __construct()
    {
        $this->p5ProjectModel = new P5ProjectModel();
        $this->p5ProjectStudentModel = new P5ProjectStudentModel();
        $this->p5AssessmentModel = new P5AssessmentModel();
        $this->teacherModel = new TeacherModel();
        $this->p5SubElementModel = new P5SubElementModel();
        $this->p5ProjectFacilitatorModel = new P5ProjectFacilitatorModel(); // Added
        helper(['auth']); // Ensure auth helper is loaded for has_role or user_id

        // Get logged in teacher id
        $user_id = user_id(); // Use auth helper
        if ($user_id) {
            $teacher = $this->teacherModel->where('user_id', $user_id)->first();
            if ($teacher) {
                $this->teacherId = $teacher['id'];
            } else {
                $this->teacherId = null;
            }
        } else {
             $this->teacherId = null; // Should not happen if AuthFilter is working
        }
    }

    private function _checkFacilitatorAccess($projectId)
    {
        if (!$this->teacherId) {
            return false; // No teacher ID, deny access
        }
        // Allow Administrator Sistem to bypass facilitator check
        if (has_role('Administrator Sistem')) {
            return true;
        }
        return $this->p5ProjectFacilitatorModel->isFacilitator($this->teacherId, $projectId);
    }

    public function selectProject()
    {
        if (!$this->teacherId && !has_role('Administrator Sistem')) {
             return redirect()->to('/unauthorized-access')->with('error', 'Data guru tidak ditemukan atau Anda tidak memiliki hak akses.');
        }

        $projects = [];
        if (has_role('Administrator Sistem')) {
            // Admin sees all projects
            $projects = $this->p5ProjectModel->orderBy('name', 'ASC')->findAll();
        } elseif($this->teacherId) {
            // Teacher sees only projects they facilitate
            $projects = $this->p5ProjectFacilitatorModel->getProjectsByFacilitator($this->teacherId);
        }

        $data['projects'] = $projects;
        return view('guru/p5assessments/select_project', $data);
    }

    public function showAssessmentForm($projectId)
    {
        if (!$this->_checkFacilitatorAccess($projectId)) {
            return redirect()->to('guru/p5assessments')->with('error', 'Anda tidak memiliki hak akses untuk menilai projek ini.');
        }

        $project = $this->p5ProjectModel->find($projectId);
        if (!$project) {
            return redirect()->back()->with('error', 'Projek P5 tidak ditemukan.');
        }

        // Get students in this project
        $projectStudents = $this->p5ProjectStudentModel->where('p5_project_id', $projectId)
                                                       ->join('students', 'students.id = p5_project_students.student_id')
                                                       ->select('p5_project_students.id as p5_project_student_id, students.id as student_id, students.name as student_name, students.nis')
                                                       ->findAll();

        // Get target sub-elements for this project
        $targetSubElements = $this->p5SubElementModel
                                ->select('p5_sub_elements.id, p5_sub_elements.name, p5_elements.name as element_name, p5_dimensions.name as dimension_name')
                                ->join('p5_project_target_sub_elements pstse', 'pstse.p5_sub_element_id = p5_sub_elements.id')
                                ->join('p5_elements', 'p5_elements.id = p5_sub_elements.p5_element_id')
                                ->join('p5_dimensions', 'p5_dimensions.id = p5_elements.p5_dimension_id')
                                ->where('pstse.p5_project_id', $projectId)
                                ->findAll();

        $existingAssessments = [];
        foreach ($projectStudents as $ps) {
            foreach ($targetSubElements as $subElement) {
                $assessment = $this->p5AssessmentModel
                                    ->where('p5_project_student_id', $ps['p5_project_student_id'])
                                    ->where('p5_sub_element_id', $subElement['id'])
                                    ->first();
                if ($assessment) {
                    $existingAssessments[$ps['p5_project_student_id']][$subElement['id']] = $assessment;
                }
            }
        }

        $data = [
            'project' => $project,
            'projectStudents' => $projectStudents,
            'targetSubElements' => $targetSubElements,
            'existingAssessments' => $existingAssessments,
            'assessment_options' => ['BB', 'MB', 'BSH', 'SB'] // Berkembang, Mulai Berkembang, Berkembang Sesuai Harapan, Sangat Berkembang
        ];

        return view('guru/p5assessments/assessment_form', $data);
    }

    public function saveAssessments($projectId)
    {
        if (!$this->_checkFacilitatorAccess($projectId)) {
            return redirect()->to('guru/p5assessments')->with('error', 'Anda tidak memiliki hak akses untuk menyimpan penilaian projek ini.');
        }

        if (!$this->teacherId) {
            // This check is somewhat redundant if _checkFacilitatorAccess is called,
            // but good as a fallback, especially if admin can save.
            // However, admin saving should ideally impersonate or have a clear "assessed_by_admin" mechanism.
            // For now, assessed_by is always $this->teacherId.
            return redirect()->back()->with('error', 'Tidak dapat menyimpan penilaian. Data guru tidak ditemukan.');
        }

        $project = $this->p5ProjectModel->find($projectId);
        if (!$project) {
            return redirect()->back()->with('error', 'Projek P5 tidak ditemukan.');
        }

        $assessmentsData = $this->request->getPost('assessments');
        $validationRules = [];
        $validationMessages = [];
        $dataToSave = [];

        if (empty($assessmentsData)) {
            return redirect()->back()->with('error', 'Tidak ada data penilaian yang dikirim.');
        }

        foreach ($assessmentsData as $p5ProjectStudentId => $subElements) {
            foreach ($subElements as $subElementId => $values) {
                $projectStudent = $this->p5ProjectStudentModel->find($p5ProjectStudentId);
                if (!$projectStudent) continue; // Skip if invalid project_student_id

                $student = $this->p5ProjectStudentModel->getStudent($projectStudent['student_id']);
                $subElement = $this->p5SubElementModel->find($subElementId);
                if (!$student || !$subElement) continue;

                $assessmentValue = $values['assessment_value'] ?? null;
                $notes = $values['notes'] ?? null;

                // Only validate if at least one field is filled for this sub-element
                if (!empty($assessmentValue) || !empty($notes)) {
                    $validationRules["assessments.{$p5ProjectStudentId}.{$subElementId}.assessment_value"] = 'permit_empty|in_list[BB,MB,BSH,SB]';
                    $validationMessages["assessments.{$p5ProjectStudentId}.{$subElementId}.assessment_value.in_list"] = "Nilai untuk {$student['name']} - {$subElement['name']} harus salah satu dari BB, MB, BSH, SB.";
                }


                // Check if assessment already exists
                $existingAssessment = $this->p5AssessmentModel
                    ->where('p5_project_student_id', $p5ProjectStudentId)
                    ->where('p5_sub_element_id', $subElementId)
                    ->first();

                if ($existingAssessment) {
                    // Update existing assessment
                    if (!empty($assessmentValue) || !empty($notes) || $existingAssessment['assessment_value'] || $existingAssessment['notes']) {
                         $updateData = [
                            'assessment_value' => $assessmentValue,
                            'notes' => $notes,
                            'assessed_by' => $this->teacherId,
                            'assessment_date' => date('Y-m-d')
                        ];
                        if (empty($assessmentValue) && empty($notes)) { // If both are emptied, consider it a delete or clear
                             $this->p5AssessmentModel->delete($existingAssessment['id']);
                        } else {
                             $this->p5AssessmentModel->update($existingAssessment['id'], $updateData);
                        }
                    }
                } elseif (!empty($assessmentValue) || !empty($notes)) {
                    // Insert new assessment
                    $dataToSave[] = [
                        'p5_project_student_id' => $p5ProjectStudentId,
                        'p5_sub_element_id' => $subElementId,
                        'assessment_value' => $assessmentValue,
                        'notes' => $notes,
                        'assessed_by' => $this->teacherId,
                        'assessment_date' => date('Y-m-d')
                    ];
                }
            }
        }

        if (!empty($validationRules) && !$this->validate($validationRules, $validationMessages)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        if (!empty($dataToSave)) {
            if (!$this->p5AssessmentModel->insertBatch($dataToSave)) {
                // Log error or handle
                return redirect()->back()->with('error', 'Gagal menyimpan sebagian data penilaian baru.')->withInput();
            }
        }

        return redirect()->to('guru/p5assessments/project/' . $projectId)->with('success', 'Penilaian P5 berhasil disimpan.');
    }
}
