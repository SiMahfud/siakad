<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\TeacherModel;
use App\Models\UserModel; // For potential lookups, though validation handles existence

class TeacherController extends BaseController
{
    protected $teacherModel;
    protected $userModel;

    public function __construct()
    {
        $this->teacherModel = new TeacherModel();
        $this->userModel = new UserModel();
        helper(['form', 'url', 'auth']); // Ensure auth_helper is loaded
    }

    public function index()
    {
        // Access controlled by route filter for 'Administrator Sistem', 'Staf Tata Usaha'
        // Kepala Sekolah might need a separate read-only view or logic here if general index is not for them.
        $data = [
            'teachers' => $this->teacherModel->orderBy('full_name', 'ASC')->findAll(),
            'title'    => 'Manage Teachers'
        ];
        return view('admin/teachers/index', $data);
    }

    public function new()
    {
        if (!hasRole(['Administrator Sistem', 'Staf Tata Usaha'])) {
            return redirect()->to('/admin/teachers')->with('error', 'You do not have permission to add new teachers.');
        }
        $data = [
            'title'      => 'Add New Teacher',
            'validation' => \Config\Services::validation()
        ];
        return view('admin/teachers/new', $data);
    }

    public function create()
    {
        if (!hasRole(['Administrator Sistem', 'Staf Tata Usaha'])) {
            return redirect()->to('/admin/teachers')->with('error', 'You do not have permission to create teachers.');
        }
        $validationRules = $this->teacherModel->getValidationRules();

        $teacherData = [
            'full_name' => $this->request->getPost('full_name'),
            'nip'       => $this->request->getPost('nip') ?: null,
            'user_id'   => $this->request->getPost('user_id') ?: null,
        ];

        if (!$this->validate($validationRules)) {
            $data = [
                'title'      => 'Add New Teacher',
                'validation' => $this->validator,
            ];
            return view('admin/teachers/new', $data);
        }

        if ($this->teacherModel->insert($teacherData)) {
            return redirect()->to('/admin/teachers')->with('success', 'Teacher added successfully.');
        } else {
            return redirect()->back()->withInput()->with('error', 'Failed to add teacher. Check data and try again.');
        }
    }

    public function edit($id = null)
    {
        if (!hasRole(['Administrator Sistem', 'Staf Tata Usaha'])) {
            return redirect()->to('/admin/teachers')->with('error', 'You do not have permission to edit teachers.');
        }
        if ($id === null) {
            return redirect()->to('/admin/teachers')->with('error', 'Teacher ID not provided.');
        }

        $teacher = $this->teacherModel->find($id);
        if (!$teacher) {
            return redirect()->to('/admin/teachers')->with('error', 'Teacher not found.');
        }

        $data = [
            'title'      => 'Edit Teacher',
            'teacher'    => $teacher,
            'validation' => \Config\Services::validation()
        ];
        return view('admin/teachers/edit', $data);
    }

    public function update($id = null)
    {
        if (!hasRole(['Administrator Sistem', 'Staf Tata Usaha'])) {
            return redirect()->to('/admin/teachers')->with('error', 'You do not have permission to update teachers.');
        }
        if ($id === null) {
            return redirect()->to('/admin/teachers')->with('error', 'Teacher ID not provided for update.');
        }

        $teacher = $this->teacherModel->find($id);
        if (!$teacher) {
            return redirect()->to('/admin/teachers')->with('error', 'Teacher not found for update.');
        }

        $validationRules = $this->teacherModel->getValidationRules([
            'nip' => "permit_empty|max_length[30]|is_unique[teachers.nip,id,{$id}]"
        ]);

        $teacherData = [
            'full_name' => $this->request->getPost('full_name'),
            'nip'       => $this->request->getPost('nip') ?: null,
            'user_id'   => $this->request->getPost('user_id') ?: null,
        ];

        if (!$this->validate($validationRules)) {
            $data = [
                'title'      => 'Edit Teacher',
                'teacher'    => $teacher, // Pass existing teacher data back
                'validation' => $this->validator,
            ];
            return view('admin/teachers/edit', $data);
        }

        if ($this->teacherModel->update($id, $teacherData)) {
            return redirect()->to('/admin/teachers')->with('success', 'Teacher updated successfully.');
        } else {
            return redirect()->back()->withInput()->with('error', 'Failed to update teacher. Check data and try again.');
        }
    }

    public function delete($id = null)
    {
        if (!hasRole(['Administrator Sistem', 'Staf Tata Usaha'])) {
             // Potentially even restrict further to only 'Administrator Sistem' for deletion
            return redirect()->to('/admin/teachers')->with('error', 'You do not have permission to delete teachers.');
        }
        if ($id === null) {
            return redirect()->to('/admin/teachers')->with('error', 'Teacher ID not provided for deletion.');
        }

        $teacher = $this->teacherModel->find($id);
        if (!$teacher) {
            return redirect()->to('/admin/teachers')->with('error', 'Teacher not found for deletion.');
        }

        if ($this->teacherModel->delete($id)) {
            return redirect()->to('/admin/teachers')->with('success', 'Teacher deleted successfully.');
        } else {
            return redirect()->to('/admin/teachers')->with('error', 'Failed to delete teacher.');
        }
    }
}
