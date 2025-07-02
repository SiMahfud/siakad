<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\SubjectOfferingModel;
use App\Models\SubjectModel; // To list subjects for selection

class SubjectOfferingController extends BaseController
{
    protected $subjectOfferingModel;
    protected $subjectModel;

    public function __construct()
    {
        helper(['form', 'url', 'auth']);
        if (!hasRole(['Administrator Sistem', 'Staf Tata Usaha'])) { // Or a specific "Curriculum" role
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Unauthorized access attempt.');
        }

        $this->subjectOfferingModel = new SubjectOfferingModel();
        $this->subjectModel = new SubjectModel();
    }

    public function index()
    {
        $filters = [
            'academic_year' => $this->request->getGet('academic_year') ?? '',
            'semester' => $this->request->getGet('semester') ?? '',
        ];

        $data = [
            'pageTitle' => 'Manage Subject Offerings',
            'offerings' => $this->subjectOfferingModel->getOfferingsWithDetails($filters),
            'filters' => $filters,
        ];
        return view('admin/subject_offerings/index', $data);
    }

    private function _loadCommonFormData(array $existingData = [])
    {
        // Only load subjects that are potentially electives, or all if no specific flag.
        // Assuming all subjects can be offered as electives for now.
        // Add a filter here if subjects table has an 'is_elective' flag.
        $subjects = $this->subjectModel->orderBy('subject_name', 'ASC')->findAll();

        return array_merge($existingData, [
            'subjects' => $subjects,
            'semesters' => [1 => 'Ganjil', 2 => 'Genap']
        ]);
    }

    public function new()
    {
        $data = [
            'pageTitle' => 'Add New Subject Offering',
            'validation' => \Config\Services::validation()
        ];
        $data = $this._loadCommonFormData($data);
        return view('admin/subject_offerings/new', $data);
    }

    public function create()
    {
        $validationRules = $this->subjectOfferingModel->getValidationRules();
        if (!$this->validate($validationRules)) {
            $data = [
                'pageTitle' => 'Add New Subject Offering',
                'validation' => $this->validator
            ];
            $data = $this._loadCommonFormData($data);
            return view('admin/subject_offerings/new', $data);
        }

        $dataToSave = [
            'subject_id'    => $this->request->getPost('subject_id'),
            'academic_year' => $this->request->getPost('academic_year'),
            'semester'      => $this->request->getPost('semester'),
            'max_quota'     => $this->request->getPost('max_quota') ?: null, // Store null if empty
            'description'   => $this->request->getPost('description'),
            'is_active'     => $this->request->getPost('is_active') ? 1 : 0,
        ];

        if ($this->subjectOfferingModel->insert($dataToSave)) {
            return redirect()->to(site_url('admin/subject-offerings'))->with('success', 'Subject offering added successfully.');
        } else {
            $dbError = $this->subjectOfferingModel->db->error();
            if ($dbError && ($dbError['code'] == 1062 || $dbError['code'] == 19)) { // MySQL: 1062, SQLite: 19 for unique constraint uq_subject_offering_period
                 return redirect()->back()->withInput()->with('error', 'This subject is already offered for the selected academic year and semester.');
            }
            return redirect()->back()->withInput()->with('error', 'Failed to add subject offering.');
        }
    }

    public function edit($id = null)
    {
        if ($id === null) {
            return redirect()->to(site_url('admin/subject-offerings'))->with('error', 'Offering ID not provided.');
        }
        $offering = $this->subjectOfferingModel->find($id);
        if (!$offering) {
            return redirect()->to(site_url('admin/subject-offerings'))->with('error', 'Subject offering not found.');
        }

        $data = [
            'pageTitle' => 'Edit Subject Offering',
            'offering' => $offering,
            'validation' => \Config\Services::validation()
        ];
        $data = $this._loadCommonFormData($data);
        return view('admin/subject_offerings/edit', $data);
    }

    public function update($id = null)
    {
        if ($id === null) {
            return redirect()->to(site_url('admin/subject-offerings'))->with('error', 'Offering ID not provided for update.');
        }
        $offering = $this->subjectOfferingModel->find($id);
        if (!$offering) {
            return redirect()->to(site_url('admin/subject-offerings'))->with('error', 'Subject offering not found for update.');
        }

        // Prevent changing subject_id, academic_year, semester if enrollment > 0?
        // Or handle cascade effects. For now, allow edit.
        if ($offering['current_enrollment'] > 0) {
            // Potentially restrict editing of subject_id, academic_year, semester if there are enrollments.
            // Or, if allowed, ensure consistency.
            // For now, we allow editing, but this is a business rule to confirm.
            // Example restriction:
            // if ($this->request->getPost('subject_id') != $offering['subject_id'] ||
            //     $this->request->getPost('academic_year') != $offering['academic_year'] ||
            //     $this->request->getPost('semester') != $offering['semester']) {
            //     return redirect()->back()->withInput()->with('error', 'Cannot change subject, academic year, or semester for an offering with existing enrollments.');
            // }
        }


        $validationRules = $this->subjectOfferingModel->getValidationRules();
        if (!$this->validate($validationRules)) {
            $data = [
                'pageTitle' => 'Edit Subject Offering',
                'offering' => array_merge($offering, $this->request->getPost()),
                'validation' => $this->validator
            ];
            $data = $this._loadCommonFormData($data);
            return view('admin/subject_offerings/edit', $data);
        }

        $dataToSave = [
            'subject_id'    => $this->request->getPost('subject_id'),
            'academic_year' => $this->request->getPost('academic_year'),
            'semester'      => $this->request->getPost('semester'),
            'max_quota'     => $this->request->getPost('max_quota') ?: null,
            'description'   => $this->request->getPost('description'),
            'is_active'     => $this->request->getPost('is_active') ? 1 : 0,
            // current_enrollment should not be directly updatable from this form.
        ];

        if ($this->subjectOfferingModel->update($id, $dataToSave)) {
            return redirect()->to(site_url('admin/subject-offerings'))->with('success', 'Subject offering updated successfully.');
        } else {
             $dbError = $this->subjectOfferingModel->db->error();
            if ($dbError && ($dbError['code'] == 1062 || $dbError['code'] == 19)) {
                 return redirect()->back()->withInput()->with('error', 'This subject is already offered for the selected academic year and semester.');
            }
            return redirect()->back()->withInput()->with('error', 'Failed to update subject offering.');
        }
    }

    public function delete($id = null)
    {
        if ($id === null) {
            return redirect()->to(site_url('admin/subject-offerings'))->with('error', 'Offering ID not provided for deletion.');
        }
        $offering = $this->subjectOfferingModel->find($id);
        if (!$offering) {
            return redirect()->to(site_url('admin/subject-offerings'))->with('error', 'Subject offering not found for deletion.');
        }

        // Check if there are any student choices for this offering
        $studentChoiceModel = new \App\Models\StudentSubjectChoiceModel();
        $choicesCount = $studentChoiceModel->where('subject_offering_id', $id)->countAllResults();

        if ($choicesCount > 0) {
            return redirect()->to(site_url('admin/subject-offerings'))->with('error', 'Cannot delete offering. There are students who have chosen this subject offering. Please remove their choices first or deactivate the offering.');
        }

        if ($this->subjectOfferingModel->delete($id)) {
            return redirect()->to(site_url('admin/subject-offerings'))->with('success', 'Subject offering deleted successfully.');
        } else {
            return redirect()->to(site_url('admin/subject-offerings'))->with('error', 'Failed to delete subject offering.');
        }
    }
}
