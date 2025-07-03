<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\P5ThemeModel;

class P5ThemeController extends BaseController
{
    protected $p5ThemeModel;
    protected $helpers = ['form', 'url', 'auth'];

    public function __construct()
    {
        $this->p5ThemeModel = new P5ThemeModel();
    }

    public function index()
    {
        if (!has_permission('manage_p5_themes')) {
            return redirect()->to('/unauthorized');
        }

        $data = [
            'title' => 'Manage P5 Themes',
            'themes' => $this->p5ThemeModel->orderBy('id', 'DESC')->findAll(),
        ];
        return view('admin/p5themes/index', $data);
    }

    public function new()
    {
        if (!has_permission('manage_p5_themes')) {
            return redirect()->to('/unauthorized');
        }

        $data = [
            'title' => 'Add New P5 Theme',
            'validation' => \Config\Services::validation(),
        ];
        return view('admin/p5themes/new', $data);
    }

    public function create()
    {
        if (!has_permission('manage_p5_themes')) {
            return redirect()->to('/unauthorized');
        }

        $rules = $this->p5ThemeModel->getValidationRules();

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $data = [
            'name' => $this->request->getPost('name'),
            'description' => $this->request->getPost('description'),
        ];

        if ($this->p5ThemeModel->save($data)) {
            return redirect()->to('admin/p5themes')->with('message', 'P5 Theme added successfully.');
        } else {
            return redirect()->back()->withInput()->with('errors', $this->p5ThemeModel->errors());
        }
    }

    public function edit($id = null)
    {
        if (!has_permission('manage_p5_themes')) {
            return redirect()->to('/unauthorized');
        }

        $theme = $this->p5ThemeModel->find($id);
        if (!$theme) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('P5 Theme not found.');
        }

        $data = [
            'title' => 'Edit P5 Theme',
            'theme' => $theme,
            'validation' => \Config\Services::validation(),
        ];
        return view('admin/p5themes/edit', $data);
    }

    public function update($id = null)
    {
        if (!has_permission('manage_p5_themes')) {
            return redirect()->to('/unauthorized');
        }

        $theme = $this->p5ThemeModel->find($id);
        if (!$theme) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('P5 Theme not found.');
        }

        $rules = $this->p5ThemeModel->getValidationRules();
        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $data = [
            'name' => $this->request->getPost('name'),
            'description' => $this->request->getPost('description'),
        ];

        if ($this->p5ThemeModel->update($id, $data)) {
            return redirect()->to('admin/p5themes')->with('message', 'P5 Theme updated successfully.');
        } else {
            return redirect()->back()->withInput()->with('errors', $this->p5ThemeModel->errors());
        }
    }

    public function delete($id = null)
    {
        if (!has_permission('manage_p5_themes')) {
            return redirect()->to('/unauthorized');
        }

        $theme = $this->p5ThemeModel->find($id);
        if (!$theme) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('P5 Theme not found.');
        }

        // Add check for related projects before deleting if necessary
        // For now, direct delete
        if ($this->p5ThemeModel->delete($id)) {
            return redirect()->to('admin/p5themes')->with('message', 'P5 Theme deleted successfully.');
        } else {
            return redirect()->to('admin/p5themes')->with('error', 'Failed to delete P5 Theme.');
        }
    }
}
