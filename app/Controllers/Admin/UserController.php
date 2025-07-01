<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Models\RoleModel;

class UserController extends BaseController
{
    protected $userModel;
    protected $roleModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->roleModel = new RoleModel();
        helper(['form', 'url']);
    }

    public function index()
    {
        $data = [
            // Use the method from UserModel to get users with role_name
            'users' => $this->userModel->getAllUsersWithRoles(),
            'title' => 'Manage Users'
        ];
        return view('admin/users/index', $data); // View to be created next
    }

    public function new()
    {
        $data = [
            'title'      => 'Add New User',
            'roles'      => $this->roleModel->orderBy('role_name', 'ASC')->findAll(),
            'validation' => \Config\Services::validation()
        ];
        return view('admin/users/new', $data); // View to be created next
    }

    public function create()
    {
        $validationRules = $this->userModel->getValidationRules();
        // No need to adjust for 'id' on create for is_unique checks like 'username'

        $userData = [
            'username'  => $this->request->getPost('username'),
            'full_name' => $this->request->getPost('full_name'),
            'password'  => $this->request->getPost('password'),
            'role_id'   => $this->request->getPost('role_id'),
            'is_active' => $this->request->getPost('is_active') ?? 0, // Default to inactive if not provided
        ];

        // For password confirmation, if you add it to the form:
        // $validationRules['password_confirm'] = 'required|matches[password]';
        // $userData['password_confirm'] = $this->request->getPost('password_confirm');


        if (!$this->validate($validationRules, $userData)) { // Pass data for context in is_unique if needed
            $data = [
                'title'      => 'Add New User',
                'roles'      => $this->roleModel->orderBy('role_name', 'ASC')->findAll(),
                'validation' => $this->validator
            ];
            return view('admin/users/new', $data);
        }

        // Password hashing is handled by the model's beforeInsert callback
        if ($this->userModel->insert($userData)) {
            return redirect()->to('/admin/users')->with('success', 'User added successfully.');
        } else {
            // This branch might be hard to reach if DB errors throw exceptions
            // or if validation catches all issues.
            $data = [
                'title'      => 'Add New User',
                'roles'      => $this->roleModel->orderBy('role_name', 'ASC')->findAll(),
                'validation' => $this->validator, // Or a generic error from model
                'error'      => 'Failed to add user. Please check the data.' // Generic error
            ];
            return view('admin/users/new', $data);
        }
    }

    public function edit($id = null)
    {
        if ($id === null) {
            return redirect()->to('/admin/users')->with('error', 'User ID not provided.');
        }

        $user = $this->userModel->find($id);
        if (!$user) {
            return redirect()->to('/admin/users')->with('error', 'User not found.');
        }

        $data = [
            'title'      => 'Edit User',
            'user'       => $user,
            'roles'      => $this->roleModel->orderBy('role_name', 'ASC')->findAll(),
            'validation' => \Config\Services::validation()
        ];
        return view('admin/users/edit', $data); // View to be created next
    }

    public function update($id = null)
    {
        if ($id === null) {
            return redirect()->to('/admin/users')->with('error', 'User ID not provided for update.');
        }

        $userExists = $this->userModel->find($id);
        if (!$userExists) {
            return redirect()->to('/admin/users')->with('error', 'User not found for update.');
        }

        // Adjust validation rules for update (e.g., password not required unless changing)
        $validationRules = [
            'username'  => "required|min_length[3]|max_length[100]|is_unique[users.username,id,{$id}]",
            'full_name' => 'permit_empty|max_length[255]',
            'role_id'   => 'required|integer|is_not_unique[roles.id]',
            'is_active' => 'required|in_list[0,1]',
        ];

        $userData = [
            'username'  => $this->request->getPost('username'),
            'full_name' => $this->request->getPost('full_name'),
            'role_id'   => $this->request->getPost('role_id'),
            'is_active' => $this->request->getPost('is_active') ?? 0,
        ];

        // Handle password change
        $password = $this->request->getPost('password');
        if (!empty($password)) {
            $validationRules['password'] = 'required|min_length[8]';
            // If using password confirmation:
            // $validationRules['password_confirm'] = 'required|matches[password]';
            // $userData['password_confirm'] = $this->request->getPost('password_confirm');
            $userData['password'] = $password; // Will be hashed by model's beforeUpdate
        }


        if (!$this->validate($validationRules, $userData)) {
            $data = [
                'title'      => 'Edit User',
                'user'       => array_merge($userExists, $this->request->getPost()), // Repopulate form
                'roles'      => $this->roleModel->orderBy('role_name', 'ASC')->findAll(),
                'validation' => $this->validator
            ];
            return view('admin/users/edit', $data);
        }

        // Password hashing for $userData['password'] is handled by model's beforeUpdate callback
        if ($this->userModel->update($id, $userData)) {
            return redirect()->to('/admin/users')->with('success', 'User updated successfully.');
        } else {
            $data = [
                'title'      => 'Edit User',
                'user'       => array_merge($userExists, $this->request->getPost()),
                'roles'      => $this->roleModel->orderBy('role_name', 'ASC')->findAll(),
                'validation' => $this->validator, // Or generic error
                'error'      => 'Failed to update user. Please check the data.'
            ];
            return view('admin/users/edit', $data);
        }
    }

    public function delete($id = null)
    {
        if ($id === null) {
            return redirect()->to('/admin/users')->with('error', 'User ID not provided for deletion.');
        }

        // Prevent deleting self - good practice, though not strictly in plan yet
        // if ($id == session()->get('user_id')) {
        //     return redirect()->to('/admin/users')->with('error', 'You cannot delete your own account.');
        // }

        $user = $this->userModel->find($id);
        if (!$user) {
            return redirect()->to('/admin/users')->with('error', 'User not found for deletion.');
        }

        if ($this->userModel->delete($id)) {
            return redirect()->to('/admin/users')->with('success', 'User deleted successfully.');
        } else {
            // This might occur due to DB constraints or model internal errors
            return redirect()->to('/admin/users')->with('error', 'Failed to delete user. It might be associated with other records.');
        }
    }
}
