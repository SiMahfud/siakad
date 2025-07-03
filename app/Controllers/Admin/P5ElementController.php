<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\P5ElementModel;
use App\Models\P5DimensionModel;

class P5ElementController extends BaseController
{
    protected $p5ElementModel;
    protected $p5DimensionModel;
    protected $helpers = ['form', 'url', 'auth'];

    public function __construct()
    {
        $this->p5ElementModel = new P5ElementModel();
        $this->p5DimensionModel = new P5DimensionModel();
    }

    public function index()
    {
        if (!has_permission('manage_p5_elements')) {
            return redirect()->to('/unauthorized');
        }

        $data = [
            'title' => 'Manage P5 Elements',
            'elements' => $this->p5ElementModel->select('p5_elements.*, p5_dimensions.name as dimension_name')
                                            ->join('p5_dimensions', 'p5_dimensions.id = p5_elements.p5_dimension_id')
                                            ->orderBy('p5_elements.id', 'DESC')->findAll(),
        ];
        return view('admin/p5elements/index', $data);
    }

    public function new()
    {
        if (!has_permission('manage_p5_elements')) {
            return redirect()->to('/unauthorized');
        }

        $data = [
            'title' => 'Add New P5 Element',
            'validation' => \Config\Services::validation(),
            'dimensions' => $this->p5DimensionModel->orderBy('name', 'ASC')->findAll(),
        ];
        return view('admin/p5elements/new', $data);
    }

    public function create()
    {
        if (!has_permission('manage_p5_elements')) {
            return redirect()->to('/unauthorized');
        }

        $rules = $this->p5ElementModel->getValidationRules();

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $data = [
            'p5_dimension_id' => $this->request->getPost('p5_dimension_id'),
            'name' => $this->request->getPost('name'),
            'description' => $this->request->getPost('description'),
        ];

        if ($this->p5ElementModel->save($data)) {
            return redirect()->to('admin/p5elements')->with('message', 'P5 Element added successfully.');
        } else {
            return redirect()->back()->withInput()->with('errors', $this->p5ElementModel->errors());
        }
    }

    public function edit($id = null)
    {
        if (!has_permission('manage_p5_elements')) {
            return redirect()->to('/unauthorized');
        }

        $element = $this->p5ElementModel->find($id);
        if (!$element) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('P5 Element not found.');
        }

        $data = [
            'title' => 'Edit P5 Element',
            'element' => $element,
            'validation' => \Config\Services::validation(),
            'dimensions' => $this->p5DimensionModel->orderBy('name', 'ASC')->findAll(),
        ];
        return view('admin/p5elements/edit', $data);
    }

    public function update($id = null)
    {
        if (!has_permission('manage_p5_elements')) {
            return redirect()->to('/unauthorized');
        }

        $element = $this->p5ElementModel->find($id);
        if (!$element) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('P5 Element not found.');
        }

        $rules = $this->p5ElementModel->getValidationRules();
        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $data = [
            'p5_dimension_id' => $this->request->getPost('p5_dimension_id'),
            'name' => $this->request->getPost('name'),
            'description' => $this->request->getPost('description'),
        ];

        if ($this->p5ElementModel->update($id, $data)) {
            return redirect()->to('admin/p5elements')->with('message', 'P5 Element updated successfully.');
        } else {
            return redirect()->back()->withInput()->with('errors', $this->p5ElementModel->errors());
        }
    }

    public function delete($id = null)
    {
        if (!has_permission('manage_p5_elements')) {
            return redirect()->to('/unauthorized');
        }

        $element = $this->p5ElementModel->find($id);
        if (!$element) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('P5 Element not found.');
        }

        // Add check for related sub-elements before deleting if necessary
        if ($this->p5ElementModel->delete($id)) {
            return redirect()->to('admin/p5elements')->with('message', 'P5 Element deleted successfully.');
        } else {
            // Consider checking for DB error for foreign key constraint
            return redirect()->to('admin/p5elements')->with('error', 'Failed to delete P5 Element. It might be in use by P5 Sub-elements.');
        }
    }
}
