<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\UserModel;

class AuthController extends BaseController
{
    protected $userModel;
    protected $session;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->session = \Config\Services::session();
        helper(['form', 'url']);
    }

    public function login()
    {
        // If already logged in, redirect to a default page (e.g., admin dashboard)
        if ($this->session->get('is_logged_in')) {
            // Check role and redirect accordingly, for now, admin/students
            $role_id = $this->session->get('role_id');
            // Assuming role_id 1, 2, 3 (Admin, TU, Kepsek) go to admin dashboard
            // Other roles might go elsewhere in future
            if (in_array($role_id, [1, 2, 3])) { // Example: Admin, TU, Kepala Sekolah
                 return redirect()->to('admin/students');
            }
            // Potentially redirect other roles to their specific dashboards later
        }

        $data = [
            'title' => 'Login SI-AKADEMIK',
            'validation' => \Config\Services::validation()
        ];
        return view('auth/login', $data); // We'll create this view next
    }

    public function attemptLogin()
    {
        $rules = [
            'username' => 'required|min_length[3]',
            'password' => 'required|min_length[3]', // Basic validation, actual length check in UserModel is for creation
        ];

        if (!$this->validate($rules)) {
            return redirect()->to('/login')->withInput()->with('validation', $this->validator);
        }

        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');

        $user = $this->userModel->where('username', $username)->first();

        if (!$user) {
            return redirect()->to('/login')->withInput()->with('error', 'Username not found.');
        }

        if (!$this->userModel->verifyPassword($password, $user['password'])) {
            return redirect()->to('/login')->withInput()->with('error', 'Incorrect password.');
        }

        // Check if user is active
        if (!$user['is_active']) {
            return redirect()->to('/login')->withInput()->with('error', 'Your account is inactive. Please contact an administrator.');
        }

        // Set session data
        $userData = [
            'user_id'       => $user['id'],
            'username'      => $user['username'],
            'full_name'     => $user['full_name'],
            'role_id'       => $user['role_id'],
            'is_logged_in'  => true,
        ];
        $this->session->set($userData);

        // Redirect based on role, for example
        // Role IDs: 1:Admin, 2:TU, 3:Kepsek, 4:Guru, 5:Siswa, 6:Ortu
        switch ($user['role_id']) {
            case 1: // Administrator Sistem
            case 2: // Staf Tata Usaha
            case 3: // Kepala Sekolah
                return redirect()->to('admin/students')->with('success', 'Login successful!');
            // case 4: // Guru
            //     return redirect()->to('guru/dashboard'); // Example
            // case 5: // Siswa
            //     return redirect()->to('siswa/dashboard'); // Example
            default:
                return redirect()->to('/')->with('success', 'Login successful!'); // Fallback to homepage
        }
    }

    public function logout()
    {
        $this->session->destroy();
        return redirect()->to('/login')->with('success', 'You have been logged out.');
    }
}
