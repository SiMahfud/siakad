<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\StudentModel;
use App\Models\UserModel; // To check if user_id and parent_user_id exist, though model validation handles this.

class StudentController extends BaseController
{
    protected $studentModel;
    protected $userModel; // For potential lookups if needed, though validation handles existence

    public function __construct()
    {
        $this->studentModel = new StudentModel();
        $this->userModel = new UserModel(); // Initialize UserModel
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
            return redirect()->back()->withInput()->with('validation', $this->validator);
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
            return redirect()->back()->withInput()->with('validation', $this->validator);
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
}
