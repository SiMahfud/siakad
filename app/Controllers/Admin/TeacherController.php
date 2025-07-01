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
        helper(['form', 'url']);
    }

    public function index()
    {
        $data = [
            'teachers' => $this->teacherModel->orderBy('full_name', 'ASC')->findAll(),
            'title'    => 'Manage Teachers'
        ];
        return view('admin/teachers/index', $data);
    }

    public function new()
    {
        $data = [
            'title'      => 'Add New Teacher',
            'validation' => \Config\Services::validation()
        ];
        return view('admin/teachers/new', $data);
    }

    public function create()
    {
        $validationRules = $this->teacherModel->getValidationRules();

        $teacherData = [
            'full_name' => $this->request->getPost('full_name'),
            'nip'       => $this->request->getPost('nip') ?: null,
            'user_id'   => $this->request->getPost('user_id') ?: null,
        ];

        if (!$this->validate($validationRules)) {
            return redirect()->back()->withInput()->with('validation', $this->validator);
        }

        if ($this->teacherModel->insert($teacherData)) {
            return redirect()->to('/admin/teachers')->with('success', 'Teacher added successfully.');
        } else {
            return redirect()->back()->withInput()->with('error', 'Failed to add teacher. Check data and try again.');
        }
    }

    public function edit($id = null)
    {
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
            return redirect()->back()->withInput()->with('validation', $this->validator);
        }

        if ($this->teacherModel->update($id, $teacherData)) {
            return redirect()->to('/admin/teachers')->with('success', 'Teacher updated successfully.');
        } else {
            return redirect()->back()->withInput()->with('error', 'Failed to update teacher. Check data and try again.');
        }
    }

    public function delete($id = null)
    {
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
