<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\SettingModel;

class SettingController extends BaseController
{
    protected $settingModel;
    protected $helpers = ['form', 'url', 'auth'];

    // Define the list of allowed setting keys to prevent arbitrary key creation/update
    // and to define the form fields.
    protected $allowedSettingKeys = [
        'school_name' => ['label' => 'Nama Sekolah', 'rules' => 'required|max_length[255]'],
        'school_address' => ['label' => 'Alamat Sekolah', 'rules' => 'permit_empty|max_length[500]'],
        'headmaster_name' => ['label' => 'Nama Kepala Sekolah', 'rules' => 'permit_empty|max_length[255]'],
        'headmaster_nip' => ['label' => 'NIP Kepala Sekolah', 'rules' => 'permit_empty|max_length[50]'],
        'current_academic_year' => ['label' => 'Tahun Ajaran Aktif (cth: 2023/2024)', 'rules' => 'required|max_length[10]'],
        'current_semester' => ['label' => 'Semester Aktif (1 atau 2)', 'rules' => 'required|in_list[1,2]'],
        'current_academic_year_semester_code' => ['label' => 'Kode Semester e-Rapor (cth: 20231 atau 20232)', 'rules' => 'permit_empty|max_length[5]'],
        // Add other settings here as needed
        // 'phone_number' => ['label' => 'Nomor Telepon Sekolah', 'rules' => 'permit_empty|max_length[20]'],
        // 'school_email' => ['label' => 'Email Sekolah', 'rules' => 'permit_empty|valid_email|max_length[100]'],
    ];


    public function __construct()
    {
        $this->settingModel = new SettingModel();
    }

    public function index()
    {
        if (!has_permission('manage_settings')) { // Assuming a 'manage_settings' permission
            // Fallback to isAdmin if 'manage_settings' is not defined yet.
            // It's better to define specific permissions.
            if (!isAdmin()) {
                 return redirect()->to('/unauthorized');
            }
        }

        $settings = $this->settingModel->getAllSettings();
        $viewData = ['title' => 'Pengaturan Umum Sekolah', 'settings' => []];

        foreach ($this->allowedSettingKeys as $key => $config) {
            $viewData['settings'][$key] = [
                'label' => $config['label'],
                'value' => $settings[$key] ?? '', // Use existing value or empty string
                'rules' => $config['rules'] // Pass rules for potential client-side validation or info
            ];
        }

        $viewData['validation'] = \Config\Services::validation();

        return view('admin/settings/index', $viewData);
    }

    public function save()
    {
        if (!has_permission('manage_settings')) {
             if (!isAdmin()) {
                 return redirect()->to('/unauthorized');
            }
        }

        $rules = [];
        foreach ($this->allowedSettingKeys as $key => $config) {
            $rules[$key] = $config['rules'];
        }

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $settingsToSave = [];
        foreach (array_keys($this->allowedSettingKeys) as $key) {
            $settingsToSave[$key] = $this->request->getPost($key);
        }

        if ($this->settingModel->saveSettings($settingsToSave)) {
            session()->setFlashdata('message', 'Pengaturan berhasil disimpan.');
        } else {
            session()->setFlashdata('error', 'Gagal menyimpan pengaturan.');
        }

        return redirect()->to('admin/settings');
    }
}
