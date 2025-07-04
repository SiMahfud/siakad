<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\StudentModel;
use App\Models\UserModel; // To check if user_id and parent_user_id exist, though model validation handles this.

class StudentController extends BaseController
{
    protected $studentModel;
    protected $userModel; // For potential lookups if needed, though validation handles existence
    protected $p5ProjectStudentModel;
    protected $p5AssessmentModel;
    protected $p5ProjectModel;
    protected $p5SubElementModel;
    protected $p5DimensionModel; // Added for dimension names

    public function __construct()
    {
        $this->studentModel = new StudentModel();
        $this->userModel = new UserModel(); // Initialize UserModel
        $this->p5ProjectStudentModel = new \App\Models\P5ProjectStudentModel();
        $this->p5AssessmentModel = new \App\Models\P5AssessmentModel();
        $this->p5ProjectModel = new \App\Models\P5ProjectModel();
        $this->p5SubElementModel = new \App\Models\P5SubElementModel();
        $this->p5DimensionModel = new \App\Models\P5DimensionModel(); // Added
        helper(['form', 'url', 'auth']); // Load form, URL, and auth helpers
    }

    public function index()
    {
        // Allowed for Admin, Staf TU, Kepala Sekolah (via Route filter or broader access)
        // No specific controller check needed if route filter is 'auth:Administrator Sistem,Staf Tata Usaha,Kepala Sekolah'
        // or if Kepala Sekolah has a different route to a read-only view.
        // For now, assuming the route filter handles general access for these roles.
        $data = [
            'students' => $this->studentModel->orderBy('full_name', 'ASC')->findAll(),
            'title' => 'Manage Students'
        ];
        return view('admin/students/index', $data);
    }

    public function new()
    {
        if (!hasRole(['Administrator Sistem', 'Staf Tata Usaha'])) {
            return redirect()->to('/admin/students')->with('error', 'You do not have permission to add new students.');
        }
        $data = [
            'title' => 'Add New Student',
            'validation' => \Config\Services::validation()
        ];
        return view('admin/students/new', $data);
    }

    public function create()
    {
        if (!hasRole(['Administrator Sistem', 'Staf Tata Usaha'])) {
            return redirect()->to('/admin/students')->with('error', 'You do not have permission to create students.');
        }
        $validationRules = $this->studentModel->getValidationRules();

        // Handle nullable foreign keys: if empty string is passed, convert to null
        $studentData = [
            'full_name'      => $this->request->getPost('full_name'),
            'nisn'           => $this->request->getPost('nisn') ?: null,
            'user_id'        => $this->request->getPost('user_id') ?: null,
            'parent_user_id' => $this->request->getPost('parent_user_id') ?: null,
        ];

        if (!$this->validate($validationRules)) {
            // Re-render the 'new' view with validation errors
            $data = [
                'title'      => 'Add New Student',
                'validation' => $this->validator,
                // Add any other data the 'new' view might need (e.g., for dropdowns)
            ];
            return view('admin/students/new', $data);
        }

        if ($this->studentModel->insert($studentData)) {
            return redirect()->to('/admin/students')->with('success', 'Student added successfully.');
        } else {
            // This part might not be reached if DB errors are exceptions
            return redirect()->back()->withInput()->with('error', 'Failed to add student. Check data and try again.');
        }
    }

    public function edit($id = null)
    {
        if (!hasRole(['Administrator Sistem', 'Staf Tata Usaha'])) {
            return redirect()->to('/admin/students')->with('error', 'You do not have permission to edit students.');
        }
        if ($id === null) {
            return redirect()->to('/admin/students')->with('error', 'Student ID not provided.');
        }

        $student = $this->studentModel->find($id);
        if (!$student) {
            return redirect()->to('/admin/students')->with('error', 'Student not found.');
        }

        $data = [
            'title'   => 'Edit Student',
            'student' => $student,
            'validation' => \Config\Services::validation()
        ];
        return view('admin/students/edit', $data);
    }

    public function update($id = null)
    {
        if (!hasRole(['Administrator Sistem', 'Staf Tata Usaha'])) {
            return redirect()->to('/admin/students')->with('error', 'You do not have permission to update students.');
        }
        if ($id === null) {
            return redirect()->to('/admin/students')->with('error', 'Student ID not provided for update.');
        }

        $student = $this->studentModel->find($id);
        if (!$student) {
            return redirect()->to('/admin/students')->with('error', 'Student not found for update.');
        }

        // Adjust validation rules for unique fields during update
        $validationRules = $this->studentModel->getValidationRules([
            'nisn' => "permit_empty|max_length[20]|is_unique[students.nisn,id,{$id}]"
        ]);

        // Handle nullable foreign keys: if empty string is passed, convert to null
        $studentData = [
            'full_name'      => $this->request->getPost('full_name'),
            'nisn'           => $this->request->getPost('nisn') ?: null,
            'user_id'        => $this->request->getPost('user_id') ?: null,
            'parent_user_id' => $this->request->getPost('parent_user_id') ?: null,
        ];

        if (!$this->validate($validationRules)) {
            // Re-render the 'edit' view with validation errors
            $data = [
                'title'      => 'Edit Student',
                'student'    => $student, // Pass existing student data back to the view
                'validation' => $this->validator,
                // Add any other data the 'edit' view might need
            ];
            return view('admin/students/edit', $data);
        }

        if ($this->studentModel->update($id, $studentData)) {
            return redirect()->to('/admin/students')->with('success', 'Student updated successfully.');
        } else {
             // This part might not be reached if DB errors are exceptions
            return redirect()->back()->withInput()->with('error', 'Failed to update student. Check data and try again.');
        }
    }

    public function delete($id = null)
    {
        if (!hasRole(['Administrator Sistem', 'Staf Tata Usaha'])) {
            // Potentially even restrict further to only 'Administrator Sistem' for deletion
            return redirect()->to('/admin/students')->with('error', 'You do not have permission to delete students.');
        }
        if ($id === null) {
            return redirect()->to('/admin/students')->with('error', 'Student ID not provided for deletion.');
        }

        $student = $this->studentModel->find($id);
        if (!$student) {
            return redirect()->to('/admin/students')->with('error', 'Student not found for deletion.');
        }

        if ($this->studentModel->delete($id)) {
            return redirect()->to('/admin/students')->with('success', 'Student deleted successfully.');
        } else {
            return redirect()->to('/admin/students')->with('error', 'Failed to delete student.');
        }
    }

    public function p5Report($student_id)
    {
        if (!hasRole(['Administrator Sistem', 'Staf Tata Usaha', 'Kepala Sekolah'])) {
            return redirect()->to('/unauthorized-access');
        }

        $student = $this->studentModel->find($student_id);
        if (!$student) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Student not found.');
        }

        // Get all P5 projects this student is part of
        $studentProjects = $this->p5ProjectStudentModel
            ->select('p5_project_students.id as p5_project_student_id, p5_project_students.p5_project_id, p5_projects.name as project_name, p5_projects.description as project_description, p5_projects.start_date, p5_projects.end_date')
            ->join('p5_projects', 'p5_projects.id = p5_project_students.p5_project_id')
            ->where('p5_project_students.student_id', $student_id)
            ->orderBy('p5_projects.start_date DESC, p5_projects.name ASC')
            ->findAll();

        $reportData = [];

        foreach ($studentProjects as $sp) {
            $projectId = $sp['p5_project_id'];
            $p5ProjectStudentId = $sp['p5_project_student_id'];

            // Get target sub-elements for this project
            $targetSubElements = $this->p5SubElementModel
                ->select('p5_sub_elements.id, p5_sub_elements.name, p5_elements.name as element_name, p5_dimensions.name as dimension_name')
                ->join('p5_project_target_sub_elements pstse', 'pstse.p5_sub_element_id = p5_sub_elements.id')
                ->join('p5_elements', 'p5_elements.id = p5_sub_elements.p5_element_id')
                ->join('p5_dimensions', 'p5_dimensions.id = p5_elements.p5_dimension_id')
                ->where('pstse.p5_project_id', $projectId)
                ->orderBy('p5_dimensions.id, p5_elements.id, p5_sub_elements.id')
                ->findAll();

            $assessments = [];
            if (!empty($targetSubElements)) {
                foreach ($targetSubElements as $subElement) {
                    $assessment = $this->p5AssessmentModel
                        ->select('assessment_value, notes, assessed_by, assessment_date') // Add teachers.full_name later if needed
                        ->where('p5_project_student_id', $p5ProjectStudentId)
                        ->where('p5_sub_element_id', $subElement['id'])
                        ->first();
                    $assessments[$subElement['id']] = $assessment;
                }
            }

            $reportData[] = [
                'project_info' => $sp,
                'target_sub_elements' => $targetSubElements,
                'assessments' => $assessments,
            ];
        }

        $data = [
            'title' => 'Laporan Projek P5 untuk: ' . esc($student['full_name']),
            'student' => $student,
            'reportData' => $reportData,
        ];

        // --- Data preparation for Radar Chart ---
        $dimensionScores = [];
        $assessmentValueMapping = ['BB' => 1, 'MB' => 2, 'BSH' => 3, 'SB' => 4];

        foreach ($reportData as $projectReport) {
            foreach ($projectReport['target_sub_elements'] as $subElement) {
                $assessment = $projectReport['assessments'][$subElement['id']] ?? null;
                if ($assessment && isset($assessmentValueMapping[$assessment['assessment_value']])) {
                    $score = $assessmentValueMapping[$assessment['assessment_value']];
                    $dimensionName = $subElement['dimension_name'];

                    if (!isset($dimensionScores[$dimensionName])) {
                        $dimensionScores[$dimensionName] = ['total_score' => 0, 'count' => 0];
                    }
                    $dimensionScores[$dimensionName]['total_score'] += $score;
                    $dimensionScores[$dimensionName]['count']++;
                }
            }
        }

        $radarChartData = ['labels' => [], 'scores' => []];
        if (!empty($dimensionScores)) {
            // Fetch all dimension names for consistent ordering if needed, or just use what's assessed
            // For simplicity, using assessed dimensions. For consistency, query P5DimensionModel.
            $allDimensions = $this->p5DimensionModel->orderBy('id', 'ASC')->findAll();
            $orderedDimensionNames = array_column($allDimensions, 'name');

            $dimensionAverages = [];
            foreach ($dimensionScores as $dimName => $scores) {
                if ($scores['count'] > 0) {
                    $dimensionAverages[$dimName] = $scores['total_score'] / $scores['count'];
                }
            }

            // Ensure radar chart data follows a consistent order of dimensions
            foreach($orderedDimensionNames as $orderedDimName) {
                if (isset($dimensionAverages[$orderedDimName])) {
                    $radarChartData['labels'][] = esc($orderedDimName);
                    $radarChartData['scores'][] = round($dimensionAverages[$orderedDimName], 2);
                }
                // Optionally, include dimensions with no score as 0 or skip them
                // else {
                //    $radarChartData['labels'][] = esc($orderedDimName);
                //    $radarChartData['scores'][] = 0; // If you want to show all dimensions
                // }
            }
        }
        $data['radarChartData'] = $radarChartData;
        // --- End Radar Chart Data ---

        return view('admin/students/p5_report', $data);
    }
}
