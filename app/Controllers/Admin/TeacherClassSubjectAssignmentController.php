<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\TeacherClassSubjectAssignmentModel;
use App\Models\TeacherModel;
use App\Models\ClassModel;
use App\Models\SubjectModel;

class TeacherClassSubjectAssignmentController extends BaseController
{
    protected $assignmentModel;
    protected $teacherModel;
    protected $classModel;
    protected $subjectModel;

    public function __construct()
    {
        helper(['form', 'url', 'auth']);
        // Role check is handled by the route filter 'auth:Administrator Sistem'
        // but an additional check here can be a fallback.
        if (!isAdmin()) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Unauthorized access.');
        }

        $this->assignmentModel = new TeacherClassSubjectAssignmentModel();
        $this->teacherModel = new TeacherModel();
        $this->classModel = new ClassModel();
        $this->subjectModel = new SubjectModel();
    }

    /**
     * Display a list of current assignments.
     */
    public function index()
    {
        $data = [
            'pageTitle'   => 'Teacher Assignments',
            'assignments' => $this->assignmentModel->getAssignmentsDetails(), // Using the new method
        ];
        return view('admin/assignments/index', $data);
    }

    /**
     * Show the form for creating a new assignment.
     */
    public function new()
    {
        $data = [
            'pageTitle'  => 'Add New Assignment',
            'teachers'   => $this->teacherModel->orderBy('full_name', 'ASC')->findAll(),
            'classes'    => $this->classModel->orderBy('class_name', 'ASC')->findAll(), // Consider adding academic_year for clarity
            'subjects'   => $this->subjectModel->orderBy('subject_name', 'ASC')->findAll(),
            'validation' => \Config\Services::validation()
        ];
        return view('admin/assignments/new', $data);
    }

    /**
     * Process the creation of a new assignment.
     */
    public function create()
    {
        $data = [
            'teacher_id' => $this->request->getPost('teacher_id'),
            'class_id'   => $this->request->getPost('class_id'),
            'subject_id' => $this->request->getPost('subject_id'),
        ];

        // Custom rule for unique combination
        $isUniqueRule = sprintf(
            'is_unique_assignment[%s,%s,%s]',
            $data['teacher_id'],
            $data['class_id'],
            $data['subject_id']
        );

        // Temporary validation rules, ideally a custom rule for is_unique_assignment is better
        // For now, we rely on DB constraint mostly and model's basic is_not_unique for individual FKs
        $validationRules = $this->assignmentModel->getValidationRules();
        // Add a check for the combination if possible, or handle DB error
        // For simplicity, we'll try to save and catch DB error for unique constraint for now.


        if ($this->assignmentModel->validate($data)) { // Validates individual FKs exist
            // Check for duplicate entry before attempting to save
            $existing = $this->assignmentModel->where([
                'teacher_id' => $data['teacher_id'],
                'class_id' => $data['class_id'],
                'subject_id' => $data['subject_id']
            ])->first();

            if ($existing) {
                return redirect()->back()->withInput()->with('error', 'This assignment (Teacher-Class-Subject combination) already exists.');
            }

            if ($this->assignmentModel->insert($data)) {
                return redirect()->to(route_to('admin_assignments.index'))->with('success', 'Assignment added successfully.');
            } else {
                // This might not be reached if DB errors are thrown
                return redirect()->back()->withInput()->with('error', 'Failed to add assignment. Database error.');
            }
        } else {
            return redirect()->back()->withInput()->with('errors', $this->assignmentModel->errors());
        }
    }

    /**
     * Delete the specified assignment.
     * Note: The 'show', 'edit', 'update' methods from resource route are not implemented yet for brevity.
     */
    public function delete($id = null)
    {
        if (!$this->assignmentModel->find($id)) {
             return redirect()->to(route_to('admin_assignments.index'))->with('error', 'Assignment not found.');
        }

        if ($this->assignmentModel->delete($id)) {
            return redirect()->to(route_to('admin_assignments.index'))->with('success', 'Assignment deleted successfully.');
        } else {
            return redirect()->to(route_to('admin_assignments.index'))->with('error', 'Failed to delete assignment.');
        }
    }

    // show($id = null), edit($id = null), update($id = null) methods can be added later if needed.
}
