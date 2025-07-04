<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\SubjectModel;

class SubjectController extends BaseController
{
    protected $subjectModel;

    public function __construct()
    {
        $this->subjectModel = new SubjectModel();
        helper(['form', 'url', 'auth']); // Ensure auth_helper is loaded
    }

    public function index()
    {
        // Access controlled by route filter for 'Administrator Sistem', 'Staf Tata Usaha'
        // Kepala Sekolah might need a separate read-only view or logic here.
        $data = [
            'subjects' => $this->subjectModel->orderBy('subject_name', 'ASC')->findAll(),
            'title'    => 'Manage Subjects'
        ];
        return view('admin/subjects/index', $data);
    }

    public function new()
    {
        if (!hasRole(['Administrator Sistem', 'Staf Tata Usaha'])) {
            return redirect()->to('/admin/subjects')->with('error', 'You do not have permission to add new subjects.');
        }
        $data = [
            'title'      => 'Add New Subject',
            'validation' => \Config\Services::validation()
        ];
        return view('admin/subjects/new', $data);
    }

    public function create()
    {
        if (!hasRole(['Administrator Sistem', 'Staf Tata Usaha'])) {
            return redirect()->to('/admin/subjects')->with('error', 'You do not have permission to create subjects.');
        }
        $validationRules = $this->subjectModel->getValidationRules();

        $subjectData = [
            'subject_name' => $this->request->getPost('subject_name'),
            'subject_code' => $this->request->getPost('subject_code') ?: null,
            'is_pilihan'   => $this->request->getPost('is_pilihan'), // '0' or '1'
        ];

        // Convert 'is_pilihan' to integer 0 or 1 for validation and DB
        $subjectData['is_pilihan'] = ($subjectData['is_pilihan'] == '1') ? 1 : 0;


        if (!$this->validate($validationRules)) {
            // Re-render the 'new' view with validation errors
            $data = [
                'title'      => 'Add New Subject',
                'validation' => $this->validator,
                // 'subject' field is not needed for 'new' view, but old input will be repopulated by withInput()
                // Ensure 'is_pilihan' is passed back correctly if needed by view's old() specifically
                // The view's old('is_pilihan', '0') handles default for new form.
            ];
            // It's important that old input is available. `withInput()` handles this if redirecting.
            // When rendering view directly, CI by default makes $validation available.
            // For `old()` to work with the submitted values, they need to be available.
            // The `redirect()->back()->withInput()` does this.
            // If rendering view directly, we need to pass old input if view uses `old()`.
            // The view uses `old('subject_name')` etc.
            // The $this->request->getPost() is implicitly available to `old()` when view is rendered.
            // The $this->validator is passed as 'validation'.
             return view('admin/subjects/new', $data);
        }

        if ($this->subjectModel->insert($subjectData)) {
            return redirect()->to('/admin/subjects')->with('success', 'Subject added successfully.');
        } else {
            // Failed to insert, likely a DB issue not caught by validation.
            // Re-render the 'new' view with an error message.
            $dbError = $this->subjectModel->errors();
            $data = [
                'title'      => 'Add New Subject',
                'validation' => $this->validator, // Pass validator (might be empty if validation passed but insert failed)
                'custom_error' => 'Failed to save the subject. DB Errors: ' . print_r($dbError, true),
            ];
            // old() helper should pick up POST data automatically when re-rendering view.
            return view('admin/subjects/new', $data);
        }
    }

    public function edit($id = null)
    {
        if (!hasRole(['Administrator Sistem', 'Staf Tata Usaha'])) {
            return redirect()->to('/admin/subjects')->with('error', 'You do not have permission to edit subjects.');
        }
        if ($id === null) {
            return redirect()->to('/admin/subjects')->with('error', 'Subject ID not provided.');
        }

        $subject = $this->subjectModel->find($id);
        if (!$subject) {
            return redirect()->to('/admin/subjects')->with('error', 'Subject not found.');
        }

        $data = [
            'title'      => 'Edit Subject',
            'subject'    => $subject,
            'validation' => \Config\Services::validation()
        ];
        return view('admin/subjects/edit', $data);
    }

    public function update($id = null)
    {
        if (!hasRole(['Administrator Sistem', 'Staf Tata Usaha'])) {
            return redirect()->to('/admin/subjects')->with('error', 'You do not have permission to update subjects.');
        }
        if ($id === null) {
            return redirect()->to('/admin/subjects')->with('error', 'Subject ID not provided for update.');
        }

        $subjectEntry = $this->subjectModel->find($id);
        if (!$subjectEntry) {
            return redirect()->to('/admin/subjects')->with('error', 'Subject not found for update.');
        }

        $validationRules = $this->subjectModel->getValidationRules([
            'subject_code' => "permit_empty|max_length[20]|is_unique[subjects.subject_code,id,{$id}]"
        ]);

        $subjectData = [
            'subject_name' => $this->request->getPost('subject_name'),
            'subject_code' => $this->request->getPost('subject_code') ?: null,
            'is_pilihan'   => $this->request->getPost('is_pilihan'), // '0' or '1'
        ];
        // Convert 'is_pilihan' to integer 0 or 1 for validation and DB
        $subjectData['is_pilihan'] = ($subjectData['is_pilihan'] == '1') ? 1 : 0;

        if (!$this->validate($validationRules)) {
            // Re-render the 'edit' view with validation errors
            $data = [
                'title'      => 'Edit Subject',
                'subject'    => $subjectEntry, // Pass existing subject data back to the view
                'validation' => $this->validator,
            ];
            // As with create, old input is available via $this->request->getPost() to `old()` helper
            return view('admin/subjects/edit', $data);
        }

        if ($this->subjectModel->update($id, $subjectData)) {
            return redirect()->to('/admin/subjects')->with('success', 'Subject updated successfully.');
        } else {
            // Failed to update, likely a DB issue not caught by validation.
            // Re-render the 'edit' view with an error message.
            $data = [
                'title'      => 'Edit Subject',
                'subject'    => $subjectEntry, // Pass existing subject data back
                'validation' => $this->validator,
                'custom_error' => 'Failed to update the subject in the database. Please try again.',
            ];
            return view('admin/subjects/edit', $data);
        }
    }

    public function delete($id = null)
    {
        if (!hasRole(['Administrator Sistem', 'Staf Tata Usaha'])) {
            // Potentially even restrict further to only 'Administrator Sistem' for deletion
            return redirect()->to('/admin/subjects')->with('error', 'You do not have permission to delete subjects.');
        }
        if ($id === null) {
            return redirect()->to('/admin/subjects')->with('error', 'Subject ID not provided for deletion.');
        }

        $subject = $this->subjectModel->find($id);
        if (!$subject) {
            return redirect()->to('/admin/subjects')->with('error', 'Subject not found for deletion.');
        }

        if ($this->subjectModel->delete($id)) {
            return redirect()->to('/admin/subjects')->with('success', 'Subject deleted successfully.');
        } else {
            return redirect()->to('/admin/subjects')->with('error', 'Failed to delete subject.');
        }
    }
}
