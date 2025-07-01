<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ClassModel;
use App\Models\TeacherModel;

class ClassController extends BaseController
{
    protected $classModel;
    protected $teacherModel;

    public function __construct()
    {
        $this->classModel = new ClassModel();
        $this->teacherModel = new TeacherModel();
        helper(['form', 'url']);
    }

    public function index()
    {
        $data = [
            // Use the method from ClassModel to get classes with wali_kelas_name
            'classes' => $this->classModel->getAllClassesWithWaliKelas(),
            'title'   => 'Manage Classes (Rombel)'
        ];
        return view('admin/classes/index', $data);
    }

    public function new()
    {
        $data = [
            'title'      => 'Add New Class (Rombel)',
            'teachers'   => $this->teacherModel->orderBy('full_name', 'ASC')->findAll(),
            'validation' => \Config\Services::validation()
        ];
        return view('admin/classes/new', $data);
    }

    public function create()
    {
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
}
