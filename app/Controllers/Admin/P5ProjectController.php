<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\P5ProjectModel;
use App\Models\P5ThemeModel;
use App\Models\P5SubElementModel;
use App\Models\P5ProjectTargetSubElementModel;
use App\Models\P5DimensionModel;
use App\Models\P5ElementModel;
use App\Models\StudentModel; // Needed for student allocation
use App\Models\P5ProjectStudentModel; // Needed for student allocation


class P5ProjectController extends BaseController
{
    protected $p5ProjectModel;
    protected $p5ThemeModel;
    protected $p5SubElementModel;
    protected $p5ProjectTargetSubElementModel;
    protected $p5DimensionModel;
    protected $p5ElementModel;
    protected $studentModel;
    protected $p5ProjectStudentModel;
    protected $p5ProjectFacilitatorModel; // Added
    protected $teacherModel; // Added
    protected $helpers = ['form', 'url', 'auth', 'text'];

    public function __construct()
    {
        $this->p5ProjectModel = new P5ProjectModel();
        $this->p5ThemeModel = new P5ThemeModel();
        $this->p5SubElementModel = new P5SubElementModel();
        $this->p5ProjectTargetSubElementModel = new P5ProjectTargetSubElementModel();
        $this->p5DimensionModel = new P5DimensionModel();
        $this->p5ElementModel = new P5ElementModel();
        $this->studentModel = new StudentModel();
        $this->p5ProjectStudentModel = new P5ProjectStudentModel();
        $this->p5ProjectFacilitatorModel = new \App\Models\P5ProjectFacilitatorModel(); // Added
        $this->teacherModel = new \App\Models\TeacherModel(); // Added

        // Ensure P5AssessmentModel is loaded if not already via autoloader or other means
        if (!isset($this->p5AssessmentModel)) {
            $this->p5AssessmentModel = new \App\Models\P5AssessmentModel();
        }
    }

    public function index()
    {
        if (!has_permission('manage_p5_projects')) {
            return redirect()->to('/unauthorized');
        }

        $projects = $this->p5ProjectModel->select('p5_projects.*, p5_themes.name as theme_name')
            ->join('p5_themes', 'p5_themes.id = p5_projects.p5_theme_id', 'left')
            ->orderBy('p5_projects.id', 'DESC')
            ->findAll();

        // Get target sub-elements for each project for display
        foreach ($projects as &$project) {
            $targets = $this->p5ProjectTargetSubElementModel
                ->select('p5_sub_elements.name')
                ->join('p5_sub_elements', 'p5_sub_elements.id = p5_project_target_sub_elements.p5_sub_element_id')
                ->where('p5_project_target_sub_elements.p5_project_id', $project['id'])
                ->findAll();
            $project['target_sub_elements_names'] = array_column($targets, 'name');
        }

        $data = [
            'title' => 'Manage P5 Projects',
            'projects' => $projects,
        ];
        return view('admin/p5projects/index', $data);
    }

    public function new()
    {
        if (!has_permission('manage_p5_projects')) {
            return redirect()->to('/unauthorized');
        }

        $sub_elements_with_hierarchy = $this->p5SubElementModel
            ->select('p5_sub_elements.*, p5_elements.name as element_name, p5_dimensions.name as dimension_name')
            ->join('p5_elements', 'p5_elements.id = p5_sub_elements.p5_element_id')
            ->join('p5_dimensions', 'p5_dimensions.id = p5_elements.p5_dimension_id')
            ->orderBy('p5_dimensions.name ASC, p5_elements.name ASC, p5_sub_elements.name ASC')
            ->findAll();

        $data = [
            'title' => 'Add New P5 Project',
            'validation' => \Config\Services::validation(),
            'themes' => $this->p5ThemeModel->orderBy('name', 'ASC')->findAll(),
            'sub_elements' => $sub_elements_with_hierarchy, // For multi-select
        ];
        return view('admin/p5projects/new', $data);
    }

    public function create()
    {
        if (!has_permission('manage_p5_projects')) {
            return redirect()->to('/unauthorized');
        }

        // Custom validation rule for at least one sub-element
        $validationRules = $this->p5ProjectModel->getValidationRules();
        $validationRules['target_sub_elements'] = 'required';

        $validationMessages = [
            'target_sub_elements' => [
                'required' => 'At least one target sub-element must be selected.'
            ]
        ];

        if (!$this->validate($validationRules, $validationMessages)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $projectData = [
            'p5_theme_id' => $this->request->getPost('p5_theme_id'),
            'name' => $this->request->getPost('name'),
            'description' => $this->request->getPost('description'),
            'start_date' => $this->request->getPost('start_date') ?: null,
            'end_date' => $this->request->getPost('end_date') ?: null,
        ];

        $db = \Config\Database::connect();
        $db->transStart();

        $projectId = $this->p5ProjectModel->insert($projectData, true);

        if (!$projectId) {
            $db->transRollback();
            return redirect()->back()->withInput()->with('errors', $this->p5ProjectModel->errors());
        }

        $targetSubElementIds = $this->request->getPost('target_sub_elements'); // This is an array
        if (!empty($targetSubElementIds)) {
            $projectTargetData = [];
            foreach ($targetSubElementIds as $subElementId) {
                $projectTargetData[] = [
                    'p5_project_id' => $projectId,
                    'p5_sub_element_id' => $subElementId,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
            }
            if (!empty($projectTargetData)) {
                $this->p5ProjectTargetSubElementModel->insertBatch($projectTargetData);
            }
        }

        $db->transComplete();

        if ($db->transStatus() === false) {
            return redirect()->back()->withInput()->with('error', 'Failed to add P5 Project due to a transaction error.');
        }

        return redirect()->to('admin/p5projects')->with('message', 'P5 Project added successfully.');
    }

    public function edit($id = null)
    {
        if (!has_permission('manage_p5_projects')) {
            return redirect()->to('/unauthorized');
        }

        $project = $this->p5ProjectModel->find($id);
        if (!$project) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('P5 Project not found.');
        }

        $sub_elements_with_hierarchy = $this->p5SubElementModel
            ->select('p5_sub_elements.*, p5_elements.name as element_name, p5_dimensions.name as dimension_name')
            ->join('p5_elements', 'p5_elements.id = p5_sub_elements.p5_element_id')
            ->join('p5_dimensions', 'p5_dimensions.id = p5_elements.p5_dimension_id')
            ->orderBy('p5_dimensions.name ASC, p5_elements.name ASC, p5_sub_elements.name ASC')
            ->findAll();

        $currentTargets = $this->p5ProjectTargetSubElementModel->where('p5_project_id', $id)->findAll();
        $currentProjectSubElementIds = array_column($currentTargets, 'p5_sub_element_id');

        $data = [
            'title' => 'Edit P5 Project',
            'project' => $project,
            'validation' => \Config\Services::validation(),
            'themes' => $this->p5ThemeModel->orderBy('name', 'ASC')->findAll(),
            'sub_elements' => $sub_elements_with_hierarchy,
            'current_project_sub_element_ids' => $currentProjectSubElementIds,
        ];
        return view('admin/p5projects/edit', $data);
    }

    public function update($id = null)
    {
        if (!has_permission('manage_p5_projects')) {
            return redirect()->to('/unauthorized');
        }

        $project = $this->p5ProjectModel->find($id);
        if (!$project) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('P5 Project not found.');
        }

        $validationRules = $this->p5ProjectModel->getValidationRules();
        $validationRules['target_sub_elements'] = 'required';

        $validationMessages = [
            'target_sub_elements' => [
                'required' => 'At least one target sub-element must be selected.'
            ]
        ];

        if (!$this->validate($validationRules, $validationMessages)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $projectData = [
            'p5_theme_id' => $this->request->getPost('p5_theme_id'),
            'name' => $this->request->getPost('name'),
            'description' => $this->request->getPost('description'),
            'start_date' => $this->request->getPost('start_date') ?: null,
            'end_date' => $this->request->getPost('end_date') ?: null,
        ];

        $db = \Config\Database::connect();
        $db->transStart();

        if (!$this->p5ProjectModel->update($id, $projectData)) {
            $db->transRollback();
            return redirect()->back()->withInput()->with('errors', $this->p5ProjectModel->errors());
        }

        // Update target sub-elements: delete old ones, insert new ones
        $this->p5ProjectTargetSubElementModel->where('p5_project_id', $id)->delete();

        $targetSubElementIds = $this->request->getPost('target_sub_elements');
        if (!empty($targetSubElementIds)) {
            $projectTargetData = [];
            foreach ($targetSubElementIds as $subElementId) {
                $projectTargetData[] = [
                    'p5_project_id' => $id,
                    'p5_sub_element_id' => $subElementId,
                    'created_at' => date('Y-m-d H:i:s'), // Or retain original if that's a requirement
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
            }
            if(!empty($projectTargetData)){
                $this->p5ProjectTargetSubElementModel->insertBatch($projectTargetData);
            }
        }

        $db->transComplete();

        if ($db->transStatus() === false) {
            return redirect()->back()->withInput()->with('error', 'Failed to update P5 Project due to a transaction error.');
        }

        return redirect()->to('admin/p5projects')->with('message', 'P5 Project updated successfully.');
    }

    public function delete($id = null)
    {
        if (!has_permission('manage_p5_projects')) {
            return redirect()->to('/unauthorized');
        }

        $project = $this->p5ProjectModel->find($id);
        if (!$project) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('P5 Project not found.');
        }

        $db = \Config\Database::connect();
        $db->transStart();

        // Manually delete related target sub-elements first due to potential foreign key constraints
        $this->p5ProjectTargetSubElementModel->where('p5_project_id', $id)->delete();
        // Also delete project students if that table is populated for this project
        // $this->p5ProjectStudentModel->where('p5_project_id', $id)->delete(); // Placeholder for when students are implemented

        if ($this->p5ProjectModel->delete($id)) {
            $db->transComplete();
            if ($db->transStatus() === false) {
                 return redirect()->to('admin/p5projects')->with('error', 'P5 Project deleted, but related data cleanup might have issues.');
            }
            return redirect()->to('admin/p5projects')->with('message', 'P5 Project and its target sub-elements deleted successfully.');
        } else {
            $db->transRollback();
            return redirect()->to('admin/p5projects')->with('error', 'Failed to delete P5 Project.');
        }
    }

    // Placeholder for student management for a project
    // public function manageStudents($project_id) {}
    // public function addStudentToProject($project_id) {}
    // public function removeStudentFromProject($project_id, $student_id) {}

    public function manageStudents($project_id)
    {
        if (!has_permission('manage_p5_project_students')) { // New permission
            return redirect()->to('/unauthorized');
        }

        $project = $this->p5ProjectModel->find($project_id);
        if (!$project) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('P5 Project not found.');
        }

        $assignedStudents = $this->p5ProjectStudentModel
            ->select('p5_project_students.id as p5_project_student_id, students.id as student_id, students.full_name, students.nis')
            ->join('students', 'students.id = p5_project_students.student_id')
            ->where('p5_project_students.p5_project_id', $project_id)
            ->orderBy('students.full_name', 'ASC')
            ->findAll();

        $assignedStudentIds = array_column($assignedStudents, 'student_id');

        $availableStudents = $this->studentModel
            ->whereNotIn('id', $assignedStudentIds ?: [0]) // whereNotIn requires non-empty array or it errors
            ->orderBy('full_name', 'ASC')
            ->findAll();

        // If you want to show class info for students:
        // You would need to join with class_student and classes table for availableStudents
        // And potentially for assignedStudents as well if that info is desired there.

        $data = [
            'title' => 'Manage Students for Project: ' . esc($project['name']),
            'project' => $project,
            'assigned_students' => $assignedStudents,
            'available_students' => $availableStudents,
            'validation' => \Config\Services::validation()
        ];

        return view('admin/p5projects/manage_students', $data);
    }

    public function addStudentToProject($project_id)
    {
        if (!has_permission('manage_p5_project_students')) {
            return redirect()->to('/unauthorized');
        }

        $project = $this->p5ProjectModel->find($project_id);
        if (!$project) {
            return redirect()->back()->with('error', 'P5 Project not found.');
        }

        $studentIdsToAdd = $this->request->getPost('students_to_add'); // Expects an array of student IDs

        if (empty($studentIdsToAdd)) {
            return redirect()->back()->with('error', 'No students selected to add.');
        }

        $dataToInsert = [];
        foreach ($studentIdsToAdd as $studentId) {
            // Check if student is already in project to prevent duplicates (though UI should ideally prevent this)
            $exists = $this->p5ProjectStudentModel
                ->where('p5_project_id', $project_id)
                ->where('student_id', $studentId)
                ->first();

            if (!$exists) {
                $dataToInsert[] = [
                    'p5_project_id' => $project_id,
                    'student_id' => $studentId,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
            }
        }

        if (!empty($dataToInsert)) {
            if ($this->p5ProjectStudentModel->insertBatch($dataToInsert)) {
                return redirect()->to('admin/p5projects/manage-students/' . $project_id)->with('message', 'Students added to project successfully.');
            } else {
                return redirect()->to('admin/p5projects/manage-students/' . $project_id)->with('error', 'Failed to add students to project.');
            }
        } else {
            return redirect()->to('admin/p5projects/manage-students/' . $project_id)->with('message', 'Selected students were already in the project or no new students to add.');
        }
    }

    public function removeStudentFromProject($project_id, $project_student_id) // Using p5_project_students.id for direct deletion
    {
        if (!has_permission('manage_p5_project_students')) {
            return redirect()->to('/unauthorized');
        }

        $project = $this->p5ProjectModel->find($project_id);
        if (!$project) {
            return redirect()->to('admin/p5projects')->with('error', 'P5 Project not found.');
        }

        $projectStudentEntry = $this->p5ProjectStudentModel->find($project_student_id);
        if (!$projectStudentEntry || $projectStudentEntry['p5_project_id'] != $project_id) {
            return redirect()->to('admin/p5projects/manage-students/' . $project_id)->with('error', 'Student project entry not found or does not belong to this project.');
        }

        // Consider implications: if there are assessments for this student in this project, what happens?
        // For now, direct delete. Could add checks or soft delete later.
        if ($this->p5ProjectStudentModel->delete($project_student_id)) {
            return redirect()->to('admin/p5projects/manage-students/' . $project_id)->with('message', 'Student removed from project successfully.');
        } else {
            return redirect()->to('admin/p5projects/manage-students/' . $project_id)->with('error', 'Failed to remove student from project.');
        }
    }

    public function report($project_id)
    {
        if (!has_permission('manage_p5_projects')) { // Or a specific 'view_p5_reports' permission
            return redirect()->to('/unauthorized');
        }

        $project = $this->p5ProjectModel->find($project_id);
        if (!$project) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('P5 Project not found.');
        }

        // Get students in this project
        $projectStudents = $this->p5ProjectStudentModel
            ->select('p5_project_students.id as p5_project_student_id, students.id as student_id, students.full_name as student_name, students.nis')
            ->join('students', 'students.id = p5_project_students.student_id')
            ->where('p5_project_students.p5_project_id', $project_id)
            ->orderBy('students.full_name', 'ASC')
            ->findAll();

        // Get target sub-elements for this project
        $targetSubElements = $this->p5SubElementModel
            ->select('p5_sub_elements.id, p5_sub_elements.name, p5_elements.name as element_name, p5_dimensions.name as dimension_name')
            ->join('p5_project_target_sub_elements pstse', 'pstse.p5_sub_element_id = p5_sub_elements.id')
            ->join('p5_elements', 'p5_elements.id = p5_sub_elements.p5_element_id')
            ->join('p5_dimensions', 'p5_dimensions.id = p5_elements.p5_dimension_id')
            ->where('pstse.p5_project_id', $project_id)
            ->orderBy('p5_dimensions.id, p5_elements.id, p5_sub_elements.id')
            ->findAll();

        $assessmentsData = [];
        if (!empty($projectStudents) && !empty($targetSubElements)) {
            foreach ($projectStudents as $ps) {
                foreach ($targetSubElements as $subElement) {
                    $assessment = $this->p5AssessmentModel
                        ->select('p5_assessments.assessment_value, p5_assessments.notes, teachers.full_name as assessor_name, p5_assessments.assessment_date')
                        ->join('teachers', 'teachers.id = p5_assessments.assessed_by', 'left')
                        ->where('p5_assessments.p5_project_student_id', $ps['p5_project_student_id'])
                        ->where('p5_assessments.p5_sub_element_id', $subElement['id'])
                        ->first();
                    $assessmentsData[$ps['p5_project_student_id']][$subElement['id']] = $assessment;
                }
            }
        }

        $data = [
            'title' => 'P5 Project Report: ' . esc($project['name']),
            'project' => $project,
            'projectStudents' => $projectStudents,
            'targetSubElements' => $targetSubElements,
            'assessmentsData' => $assessmentsData,
        ];

        // Prepare data for charts
        $subElementChartData = [];
        $assessmentLevels = ['BB', 'MB', 'BSH', 'SB', '-']; // Include '-' for not assessed

        if (!empty($targetSubElements) && !empty($projectStudents)) {
            foreach ($targetSubElements as $subElement) {
                $counts = array_fill_keys($assessmentLevels, 0);
                foreach ($projectStudents as $ps) {
                    $assessmentValue = $assessmentsData[$ps['p5_project_student_id']][$subElement['id']]['assessment_value'] ?? '-';
                    if (array_key_exists($assessmentValue, $counts)) {
                        $counts[$assessmentValue]++;
                    } else {
                        // Fallback for unexpected values, though ideally assessment_value is constrained
                        $counts['-']++;
                    }
                }
                $subElementChartData[$subElement['id']] = [
                    'name' => esc($subElement['name']),
                    'dimension_name' => esc($subElement['dimension_name']),
                    'element_name' => esc($subElement['element_name']),
                    'counts' => $counts,
                ];
            }
        }
        $data['subElementChartData'] = $subElementChartData;

        return view('admin/p5projects/report', $data);
    }

    public function manageFacilitators($project_id)
    {
        // Assuming 'manage_p5_projects' or a more specific 'manage_p5_facilitators' permission
        if (!has_permission('manage_p5_projects')) {
            return redirect()->to('/unauthorized');
        }

        $project = $this->p5ProjectModel->find($project_id);
        if (!$project) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('P5 Project not found.');
        }

        $assignedFacilitators = $this->p5ProjectFacilitatorModel->getFacilitatorsForProject($project_id);
        $assignedTeacherIds = array_column($assignedFacilitators, 'teacher_id');

        $availableTeachers = $this->teacherModel
            ->whereNotIn('id', $assignedTeacherIds ?: [0]) // whereNotIn requires non-empty array
            ->orderBy('full_name', 'ASC')
            ->findAll();

        $data = [
            'title' => 'Manage Facilitators for Project: ' . esc($project['name']),
            'project' => $project,
            'assigned_facilitators' => $assignedFacilitators,
            'available_teachers' => $availableTeachers,
            'validation' => \Config\Services::validation()
        ];

        return view('admin/p5projects/manage_facilitators', $data);
    }

    public function addFacilitatorToProject($project_id)
    {
        if (!has_permission('manage_p5_projects')) {
            return redirect()->to('/unauthorized');
        }

        $project = $this->p5ProjectModel->find($project_id);
        if (!$project) {
            return redirect()->back()->with('error', 'P5 Project not found.');
        }

        $teacherIdsToAdd = $this->request->getPost('teachers_to_add'); // Expects an array of teacher IDs

        if (empty($teacherIdsToAdd)) {
            return redirect()->back()->with('error', 'No teachers selected to add as facilitators.');
        }

        $dataToInsert = [];
        $errors = [];
        foreach ($teacherIdsToAdd as $teacherId) {
            $facilitatorData = [
                'p5_project_id' => $project_id,
                'teacher_id' => $teacherId,
            ];
            if ($this->p5ProjectFacilitatorModel->validate($facilitatorData)) {
                 // Check if already exists (though unique constraint should handle this at DB level)
                if (!$this->p5ProjectFacilitatorModel->isFacilitator($teacherId, $project_id)) {
                    $dataToInsert[] = $facilitatorData;
                }
            } else {
                // Collect validation errors if any (though basic FK checks are main ones here)
                $errors[] = "Invalid data for teacher ID: {$teacherId}.";
            }
        }

        if (!empty($errors)) {
             return redirect()->to('admin/p5projects/manage-facilitators/' . $project_id)->with('error', implode('<br>', $errors));
        }

        if (!empty($dataToInsert)) {
            if ($this->p5ProjectFacilitatorModel->insertBatch($dataToInsert)) {
                return redirect()->to('admin/p5projects/manage-facilitators/' . $project_id)->with('message', 'Facilitators added to project successfully.');
            } else {
                return redirect()->to('admin/p5projects/manage-facilitators/' . $project_id)->with('error', 'Failed to add facilitators to project.');
            }
        } else {
            return redirect()->to('admin/p5projects/manage-facilitators/' . $project_id)->with('message', 'Selected teachers were already facilitators or no new valid teachers to add.');
        }
    }

    public function removeFacilitatorFromProject($project_id, $teacher_id)
    {
        if (!has_permission('manage_p5_projects')) {
            return redirect()->to('/unauthorized');
        }

        $project = $this->p5ProjectModel->find($project_id);
        if (!$project) {
            return redirect()->to('admin/p5projects')->with('error', 'P5 Project not found.');
        }

        $facilitatorEntry = $this->p5ProjectFacilitatorModel
                                ->where('p5_project_id', $project_id)
                                ->where('teacher_id', $teacher_id)
                                ->first();

        if (!$facilitatorEntry) {
            return redirect()->to('admin/p5projects/manage-facilitators/' . $project_id)->with('error', 'Facilitator entry not found for this project.');
        }

        // Consider implications: if this facilitator has made assessments, what happens?
        // For now, direct delete of the facilitator assignment. Assessments remain attributed to them.
        if ($this->p5ProjectFacilitatorModel->delete($facilitatorEntry['id'])) {
            return redirect()->to('admin/p5projects/manage-facilitators/' . $project_id)->with('message', 'Facilitator removed from project successfully.');
        } else {
            return redirect()->to('admin/p5projects/manage-facilitators/' . $project_id)->with('error', 'Failed to remove facilitator from project.');
        }
    }
}
