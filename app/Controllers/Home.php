<?php

namespace App\Controllers;

class Home extends BaseController
{
    public function index(): string
    {
        // If logged in, redirect to a relevant dashboard based on role,
        // otherwise show welcome_message or redirect to login.
        if (session()->get('is_logged_in')) {
            // Example: redirect admin to students page, others to a general dashboard/home
            if (hasRole(['Administrator Sistem', 'Staf Tata Usaha', 'Kepala Sekolah'])) { // Using helper
                return redirect()->to('admin/students');
            }
            // Add other role-based redirects here if needed
            // return redirect()->to('dashboard'); // A general user dashboard
        }
        // If not logged in, or no specific role dashboard, show welcome or redirect to login
        // return view('welcome_message');
        return redirect()->to('/login'); // Default to login if not logged in and no other destination
    }

    public function unauthorizedAccess(): string
    {
        $data['pageTitle'] = 'Access Forbidden';
        $data['error_message'] = session()->getFlashdata('error') ?? 'You do not have the necessary permissions to access the requested page.';

        // Set HTTP status code to 403 Forbidden
        // Note: $this->response is available in controllers extending BaseController
        $this->response->setStatusCode(403);

        return view('errors/unauthorized', $data);
    }
}
