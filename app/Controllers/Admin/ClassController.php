<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ClassModel;
use App\Models\TeacherModel;
use App\Models\StudentModel;
use App\Models\ClassStudentModel;

class ClassController extends BaseController
{
    protected $classModel;
    protected $teacherModel;
    protected $studentModel;
    protected $classStudentModel;

    public function __construct()
    {
        $this->classModel = new ClassModel();
        $this->teacherModel = new TeacherModel();
        $this->studentModel = new StudentModel();
        $this->classStudentModel = new ClassStudentModel();
        helper(['form', 'url', 'auth']); // Ensure auth_helper is loaded
    }

    public function index()
    {
        // Access controlled by route filter for 'Administrator Sistem', 'Staf Tata Usaha'
        // Kepala Sekolah might need a separate read-only view or logic here.
        $data = [
            // Use the method from ClassModel to get classes with wali_kelas_name
            'classes' => $this->classModel->getAllClassesWithWaliKelas(),
            'title'   => 'Manage Classes (Rombel)'
        ];
        return view('admin/classes/index', $data);
    }

    public function new()
    {
        if (!hasRole(['Administrator Sistem', 'Staf Tata Usaha'])) {
            return redirect()->to('/admin/classes')->with('error', 'You do not have permission to add new classes.');
        }
        $data = [
            'title'      => 'Add New Class (Rombel)',
            'teachers'   => $this->teacherModel->orderBy('full_name', 'ASC')->findAll(),
            'validation' => \Config\Services::validation()
        ];
        return view('admin/classes/new', $data);
    }

    public function create()
    {
        if (!hasRole(['Administrator Sistem', 'Staf Tata Usaha'])) {
            return redirect()->to('/admin/classes')->with('error', 'You do not have permission to create classes.');
        }
        $validationRules = $this->classModel->getValidationRules();

        $classData = [
            'class_name'    => $this->request->getPost('class_name'),
            'academic_year' => $this->request->getPost('academic_year'),
            'fase'          => $this->request->getPost('fase') ?: null,
            'wali_kelas_id' => $this->request->getPost('wali_kelas_id') ?: null,
        ];

        if (!$this->validate($validationRules)) {
            // Need to pass teachers list back to the view on validation error
            $data = [
                'title'      => 'Add New Class (Rombel)',
                'teachers'   => $this->teacherModel->orderBy('full_name', 'ASC')->findAll(),
                'validation' => $this->validator
            ];
            return view('admin/classes/new', $data);
        }

        if ($this->classModel->insert($classData)) {
            return redirect()->to('/admin/classes')->with('success', 'Class added successfully.');
        } else {
            $data = [
                'title'      => 'Add New Class (Rombel)',
                'teachers'   => $this->teacherModel->orderBy('full_name', 'ASC')->findAll(),
                'validation' => $this->validator, // Or a generic error message
                'error'      => 'Failed to add class. Check data and try again.' // Generic error
            ];
            return view('admin/classes/new', $data);
        }
    }

    public function edit($id = null)
    {
        if (!hasRole(['Administrator Sistem', 'Staf Tata Usaha'])) {
            return redirect()->to('/admin/classes')->with('error', 'You do not have permission to edit classes.');
        }
        if ($id === null) {
            return redirect()->to('/admin/classes')->with('error', 'Class ID not provided.');
        }

        $class_item = $this->classModel->find($id);
        if (!$class_item) {
            return redirect()->to('/admin/classes')->with('error', 'Class not found.');
        }

        $data = [
            'title'      => 'Edit Class (Rombel)',
            'class_item' => $class_item,
            'teachers'   => $this->teacherModel->orderBy('full_name', 'ASC')->findAll(),
            'validation' => \Config\Services::validation()
        ];
        return view('admin/classes/edit', $data);
    }

    public function update($id = null)
    {
        if (!hasRole(['Administrator Sistem', 'Staf Tata Usaha'])) {
            return redirect()->to('/admin/classes')->with('error', 'You do not have permission to update classes.');
        }
        if ($id === null) {
            return redirect()->to('/admin/classes')->with('error', 'Class ID not provided for update.');
        }

        $class_item_exists = $this->classModel->find($id);
        if (!$class_item_exists) {
            return redirect()->to('/admin/classes')->with('error', 'Class not found for update.');
        }

        $validationRules = $this->classModel->getValidationRules();
         // No unique fields to adjust typically for class name per se, but could add compound unique constraints in DB if needed

        $classData = [
            'class_name'    => $this->request->getPost('class_name'),
            'academic_year' => $this->request->getPost('academic_year'),
            'fase'          => $this->request->getPost('fase') ?: null,
            'wali_kelas_id' => $this->request->getPost('wali_kelas_id') ?: null,
        ];

        if (!$this->validate($validationRules)) {
            // Need to pass teachers list and existing class item back
            $data = [
                'title'      => 'Edit Class (Rombel)',
                'class_item' => array_merge($class_item_exists, $this->request->getPost()), // Repopulate form with attempted values
                'teachers'   => $this->teacherModel->orderBy('full_name', 'ASC')->findAll(),
                'validation' => $this->validator
            ];
            return view('admin/classes/edit', $data);
        }

        if ($this->classModel->update($id, $classData)) {
            return redirect()->to('/admin/classes')->with('success', 'Class updated successfully.');
        } else {
            $data = [
                'title'      => 'Edit Class (Rombel)',
                'class_item' => array_merge($class_item_exists, $this->request->getPost()),
                'teachers'   => $this->teacherModel->orderBy('full_name', 'ASC')->findAll(),
                'validation' => $this->validator, // Or a generic error
                'error'      => 'Failed to update class. Check data and try again.'
            ];
             return view('admin/classes/edit', $data);
        }
    }

    public function delete($id = null)
    {
        if (!hasRole(['Administrator Sistem', 'Staf Tata Usaha'])) {
            // Potentially even restrict further to only 'Administrator Sistem' for deletion
            return redirect()->to('/admin/classes')->with('error', 'You do not have permission to delete classes.');
        }
        if ($id === null) {
            return redirect()->to('/admin/classes')->with('error', 'Class ID not provided for deletion.');
        }

        $class_item = $this->classModel->find($id);
        if (!$class_item) {
            return redirect()->to('/admin/classes')->with('error', 'Class not found for deletion.');
        }

        // Consider checking for related records (e.g., students in class_student) before deleting
        // For now, direct delete. Foreign key constraints in DB should handle integrity if set to CASCADE/RESTRICT.

        if ($this->classModel->delete($id)) {
            return redirect()->to('/admin/classes')->with('success', 'Class deleted successfully.');
        } else {
            // This might occur if there are RESTRICT foreign key constraints preventing deletion
            return redirect()->to('/admin/classes')->with('error', 'Failed to delete class. It might be in use.');
        }
    }

    // --- Class Student Management ---

    public function manageStudents($class_id = null)
    {
        if (!hasRole(['Administrator Sistem', 'Staf Tata Usaha'])) {
            return redirect()->to('/admin/classes')->with('error', 'You do not have permission to manage students in classes.');
        }
        if ($class_id === null) {
            return redirect()->to('/admin/classes')->with('error', 'Class ID not provided.');
        }

        $class_item = $this->classModel->find($class_id);
        if (!$class_item) {
            return redirect()->to('/admin/classes')->with('error', 'Class not found.');
        }

        $data = [
            'title'            => 'Manage Students in Class: ' . esc($class_item['class_name']),
            'class_item'       => $class_item,
            'students_in_class' => $this->classStudentModel->getStudentsInClass($class_id),
            'available_students' => $this->classStudentModel->getAvailableStudents($class_id),
            'validation'       => \Config\Services::validation()
        ];

        return view('admin/classes/manage_students', $data);
    }

    public function addStudentToClass($class_id = null)
    {
        if (!hasRole(['Administrator Sistem', 'Staf Tata Usaha'])) {
            return redirect()->back()->with('error', 'You do not have permission.');
        }
        if ($class_id === null) {
            return redirect()->to('/admin/classes')->with('error', 'Class ID not provided.');
        }

        $student_id = $this->request->getPost('student_id');

        if (empty($student_id)) {
            return redirect()->back()->withInput()->with('error', 'No student selected.');
        }

        // Check if student is already in this class
        $existing = $this->classStudentModel->where(['class_id' => $class_id, 'student_id' => $student_id])->first();
        if ($existing) {
            return redirect()->back()->with('error', 'Student is already in this class.');
        }

        $data = [
            'class_id'   => $class_id,
            'student_id' => $student_id
        ];

        if ($this->classStudentModel->insert($data)) {
            return redirect()->to('admin/classes/manage-students/' . $class_id)->with('success', 'Student added to class successfully.');
        } else {
            // This typically catches validation errors if set up in model, or DB errors
            $errors = $this->classStudentModel->errors();
            $errorMessage = $errors ? implode(', ', $errors) : 'Failed to add student to class. Please try again.';
            return redirect()->back()->withInput()->with('error', $errorMessage);
        }
    }

    public function removeStudentFromClass($class_id = null, $student_id = null)
    {
        if (!hasRole(['Administrator Sistem', 'Staf Tata Usaha'])) {
            return redirect()->back()->with('error', 'You do not have permission.');
        }
        if ($class_id === null || $student_id === null) {
            return redirect()->to('/admin/classes')->with('error', 'Class ID or Student ID not provided.');
        }

        // Find the specific class_student entry to delete
        $classStudentEntry = $this->classStudentModel
            ->where('class_id', $class_id)
            ->where('student_id', $student_id)
            ->first();

        if (!$classStudentEntry) {
            return redirect()->to('admin/classes/manage-students/' . $class_id)->with('error', 'Student not found in this class or record missing.');
        }

        // Use the primary key of the class_student table for deletion
        if ($this->classStudentModel->delete($classStudentEntry['id'])) {
            return redirect()->to('admin/classes/manage-students/' . $class_id)->with('success', 'Student removed from class successfully.');
        } else {
            return redirect()->to('admin/classes/manage-students/' . $class_id)->with('error', 'Failed to remove student from class.');
        }
    }
}
