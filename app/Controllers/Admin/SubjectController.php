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
        helper(['form', 'url']);
    }

    public function index()
    {
        $data = [
            'subjects' => $this->subjectModel->orderBy('subject_name', 'ASC')->findAll(),
            'title'    => 'Manage Subjects'
        ];
        return view('admin/subjects/index', $data);
    }

    public function new()
    {
        $data = [
            'title'      => 'Add New Subject',
            'validation' => \Config\Services::validation()
        ];
        return view('admin/subjects/new', $data);
    }

    public function create()
    {
        $validationRules = $this->subjectModel->getValidationRules();

        $subjectData = [
            'subject_name' => $this->request->getPost('subject_name'),
            'subject_code' => $this->request->getPost('subject_code') ?: null,
            'is_pilihan'   => $this->request->getPost('is_pilihan'), // '0' or '1'
        ];

        // Ensure boolean is correctly handled for is_pilihan
        $subjectData['is_pilihan'] = ($subjectData['is_pilihan'] == '1');


        if (!$this->validate($validationRules)) {
            // Convert boolean back for form repopulation if needed, or handle in view
            $this->request->setGlobal('request', $this->request->withParsedBody(array_merge($this->request->getPost(), ['is_pilihan' => $this->request->getPost('is_pilihan')])));
            return redirect()->back()->withInput()->with('validation', $this->validator);
        }

        if ($this->subjectModel->insert($subjectData)) {
            return redirect()->to('/admin/subjects')->with('success', 'Subject added successfully.');
        } else {
            // Convert boolean back for form repopulation if needed
            $this->request->setGlobal('request', $this->request->withParsedBody(array_merge($this->request->getPost(), ['is_pilihan' => $this->request->getPost('is_pilihan')])));
            return redirect()->back()->withInput()->with('error', 'Failed to add subject. Check data and try again.');
        }
    }

    public function edit($id = null)
    {
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
        // Ensure boolean is correctly handled for is_pilihan
        $subjectData['is_pilihan'] = ($subjectData['is_pilihan'] == '1');

        if (!$this->validate($validationRules)) {
            // Convert boolean back for form repopulation if needed
            $this->request->setGlobal('request', $this->request->withParsedBody(array_merge($this->request->getPost(), ['is_pilihan' => $this->request->getPost('is_pilihan')])));
            return redirect()->back()->withInput()->with('validation', $this->validator);
        }

        if ($this->subjectModel->update($id, $subjectData)) {
            return redirect()->to('/admin/subjects')->with('success', 'Subject updated successfully.');
        } else {
            $this->request->setGlobal('request', $this->request->withParsedBody(array_merge($this->request->getPost(), ['is_pilihan' => $this->request->getPost('is_pilihan')])));
            return redirect()->back()->withInput()->with('error', 'Failed to update subject. Check data and try again.');
        }
    }

    public function delete($id = null)
    {
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
