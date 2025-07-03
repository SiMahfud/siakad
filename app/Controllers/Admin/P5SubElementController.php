<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\P5SubElementModel;
use App\Models\P5ElementModel; // To select parent P5 Element

class P5SubElementController extends BaseController
{
    protected $p5SubElementModel;
    protected $p5ElementModel;
    protected $helpers = ['form', 'url', 'auth'];

    public function __construct()
    {
        $this->p5SubElementModel = new P5SubElementModel();
        $this->p5ElementModel = new P5ElementModel();
    }

    public function index()
    {
        if (!has_permission('manage_p5_sub_elements')) {
            return redirect()->to('/unauthorized');
        }

        $data = [
            'title' => 'Manage P5 Sub-elements',
            'sub_elements' => $this->p5SubElementModel->select('p5_sub_elements.*, p5_elements.name as element_name')
                                                    ->join('p5_elements', 'p5_elements.id = p5_sub_elements.p5_element_id')
                                                    ->orderBy('p5_sub_elements.id', 'DESC')->findAll(),
        ];
        return view('admin/p5subelements/index', $data);
    }

    public function new()
    {
        if (!has_permission('manage_p5_sub_elements')) {
            return redirect()->to('/unauthorized');
        }

        $data = [
            'title' => 'Add New P5 Sub-element',
            'validation' => \Config\Services::validation(),
            'elements' => $this->p5ElementModel->select('p5_elements.*, p5_dimensions.name as dimension_name')
                                            ->join('p5_dimensions', 'p5_dimensions.id = p5_elements.p5_dimension_id')
                                            ->orderBy('p5_elements.name', 'ASC')->findAll(),
        ];
        return view('admin/p5subelements/new', $data);
    }

    public function create()
    {
        if (!has_permission('manage_p5_sub_elements')) {
            return redirect()->to('/unauthorized');
        }

        $rules = $this->p5SubElementModel->getValidationRules();

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $data = [
            'p5_element_id' => $this->request->getPost('p5_element_id'),
            'name' => $this->request->getPost('name'),
            'description' => $this->request->getPost('description'),
        ];

        if ($this->p5SubElementModel->save($data)) {
            return redirect()->to('admin/p5subelements')->with('message', 'P5 Sub-element added successfully.');
        } else {
            return redirect()->back()->withInput()->with('errors', $this->p5SubElementModel->errors());
        }
    }

    public function edit($id = null)
    {
        if (!has_permission('manage_p5_sub_elements')) {
            return redirect()->to('/unauthorized');
        }

        $sub_element = $this->p5SubElementModel->find($id);
        if (!$sub_element) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('P5 Sub-element not found.');
        }

        $data = [
            'title' => 'Edit P5 Sub-element',
            'sub_element' => $sub_element,
            'validation' => \Config\Services::validation(),
            'elements' => $this->p5ElementModel->select('p5_elements.*, p5_dimensions.name as dimension_name')
                                            ->join('p5_dimensions', 'p5_dimensions.id = p5_elements.p5_dimension_id')
                                            ->orderBy('p5_elements.name', 'ASC')->findAll(),
        ];
        return view('admin/p5subelements/edit', $data);
    }

    public function update($id = null)
    {
        if (!has_permission('manage_p5_sub_elements')) {
            return redirect()->to('/unauthorized');
        }

        $sub_element = $this->p5SubElementModel->find($id);
        if (!$sub_element) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('P5 Sub-element not found.');
        }

        $rules = $this->p5SubElementModel->getValidationRules();
        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $data = [
            'p5_element_id' => $this->request->getPost('p5_element_id'),
            'name' => $this->request->getPost('name'),
            'description' => $this->request->getPost('description'),
        ];

        if ($this->p5SubElementModel->update($id, $data)) {
            return redirect()->to('admin/p5subelements')->with('message', 'P5 Sub-element updated successfully.');
        } else {
            return redirect()->back()->withInput()->with('errors', $this->p5SubElementModel->errors());
        }
    }

    public function delete($id = null)
    {
        if (!has_permission('manage_p5_sub_elements')) {
            return redirect()->to('/unauthorized');
        }

        $sub_element = $this->p5SubElementModel->find($id);
        if (!$sub_element) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('P5 Sub-element not found.');
        }

        // Add check for related project targets or assessments before deleting if necessary
        if ($this->p5SubElementModel->delete($id)) {
            return redirect()->to('admin/p5subelements')->with('message', 'P5 Sub-element deleted successfully.');
        } else {
             // Consider checking for DB error for foreign key constraint
            return redirect()->to('admin/p5subelements')->with('error', 'Failed to delete P5 Sub-element. It might be targeted by P5 Projects or used in Assessments.');
        }
    }
}
