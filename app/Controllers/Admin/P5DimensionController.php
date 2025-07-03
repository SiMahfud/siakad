<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\P5DimensionModel;

class P5DimensionController extends BaseController
{
    protected $p5DimensionModel;
    protected $helpers = ['form', 'url', 'auth'];

    public function __construct()
    {
        $this->p5DimensionModel = new P5DimensionModel();
    }

    public function index()
    {
        if (!has_permission('manage_p5_dimensions')) {
            return redirect()->to('/unauthorized');
        }

        $data = [
            'title' => 'Manage P5 Dimensions',
            'dimensions' => $this->p5DimensionModel->orderBy('id', 'DESC')->findAll(),
        ];
        return view('admin/p5dimensions/index', $data);
    }

    public function new()
    {
        if (!has_permission('manage_p5_dimensions')) {
            return redirect()->to('/unauthorized');
        }

        $data = [
            'title' => 'Add New P5 Dimension',
            'validation' => \Config\Services::validation(),
        ];
        return view('admin/p5dimensions/new', $data);
    }

    public function create()
    {
        if (!has_permission('manage_p5_dimensions')) {
            return redirect()->to('/unauthorized');
        }

        $rules = $this->p5DimensionModel->getValidationRules();

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $data = [
            'name' => $this->request->getPost('name'),
            'description' => $this->request->getPost('description'),
        ];

        if ($this->p5DimensionModel->save($data)) {
            return redirect()->to('admin/p5dimensions')->with('message', 'P5 Dimension added successfully.');
        } else {
            return redirect()->back()->withInput()->with('errors', $this->p5DimensionModel->errors());
        }
    }

    public function edit($id = null)
    {
        if (!has_permission('manage_p5_dimensions')) {
            return redirect()->to('/unauthorized');
        }

        $dimension = $this->p5DimensionModel->find($id);
        if (!$dimension) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('P5 Dimension not found.');
        }

        $data = [
            'title' => 'Edit P5 Dimension',
            'dimension' => $dimension,
            'validation' => \Config\Services::validation(),
        ];
        return view('admin/p5dimensions/edit', $data);
    }

    public function update($id = null)
    {
        if (!has_permission('manage_p5_dimensions')) {
            return redirect()->to('/unauthorized');
        }

        $dimension = $this->p5DimensionModel->find($id);
        if (!$dimension) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('P5 Dimension not found.');
        }

        $rules = $this->p5DimensionModel->getValidationRules();
        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $data = [
            'name' => $this->request->getPost('name'),
            'description' => $this->request->getPost('description'),
        ];

        if ($this->p5DimensionModel->update($id, $data)) {
            return redirect()->to('admin/p5dimensions')->with('message', 'P5 Dimension updated successfully.');
        } else {
            return redirect()->back()->withInput()->with('errors', $this->p5DimensionModel->errors());
        }
    }

    public function delete($id = null)
    {
        if (!has_permission('manage_p5_dimensions')) {
            return redirect()->to('/unauthorized');
        }

        $dimension = $this->p5DimensionModel->find($id);
        if (!$dimension) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('P5 Dimension not found.');
        }

        // Add check for related elements before deleting if necessary
        if ($this->p5DimensionModel->delete($id)) {
            return redirect()->to('admin/p5dimensions')->with('message', 'P5 Dimension deleted successfully.');
        } else {
            return redirect()->to('admin/p5dimensions')->with('error', 'Failed to delete P5 Dimension. It might be in use by P5 Elements.');
        }
    }
}
