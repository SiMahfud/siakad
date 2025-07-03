<?php

namespace App\Controllers\WaliKelas;

use App\Controllers\BaseController;
use App\Models\ClassModel;
use App\Models\TeacherModel;
use App\Models\AssessmentModel;
use App\Models\SubjectOfferingModel; // Untuk mendapatkan tahun ajaran & semester
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class EraporController extends BaseController
{
    protected $classModel;
    protected $teacherModel;
    protected $assessmentModel;
    protected $subjectOfferingModel;

    public function __construct()
    {
        $this->classModel = new ClassModel();
        $this->teacherModel = new TeacherModel();
        $this->assessmentModel = new AssessmentModel();
        $this->subjectOfferingModel = new SubjectOfferingModel();
        helper(['form', 'url', 'auth']);
    }

    public function exportForm()
    {
        $user = auth()->user();
        if (!$user || !has_role('Guru')) {
            return redirect()->to('unauthorized-access');
        }

        $teacher = $this->teacherModel->where('user_id', $user->id)->first();
        if (!$teacher) {
            session()->setFlashdata('error', 'Data guru tidak ditemukan untuk pengguna ini.');
            return redirect()->back();
        }

        // Dapatkan kelas dimana guru ini adalah wali kelas
        $classes = $this->classModel
                        ->where('wali_kelas_id', $teacher['id'])
                        ->orderBy('class_name', 'ASC')
                        ->findAll();

        if (empty($classes)) {
            session()->setFlashdata('error', 'Anda bukan wali kelas untuk kelas manapun atau tidak ada kelas yang ditugaskan.');
            // Sebaiknya redirect ke dashboard guru atau halaman lain yang sesuai
            return redirect()->to('guru/my-classes')->with('error', 'Anda bukan wali kelas untuk kelas manapun.');
        }

        // Ambil tahun ajaran dan semester unik dari subject_offerings untuk filter
        // Ini mungkin perlu disesuaikan jika ada sumber data tahun ajaran/semester yang lebih definitif
        $academicYears = $this->subjectOfferingModel->distinct()->select('academic_year')->orderBy('academic_year', 'DESC')->findAll();
        $semesters = $this->subjectOfferingModel->distinct()->select('semester')->orderBy('semester', 'ASC')->findAll(); // Asumsi 1=Ganjil, 2=Genap

        $data = [
            'title' => 'Ekspor Data ke e-Rapor',
            'classes' => $classes,
            'academic_years' => $academicYears,
            'semesters' => $semesters,
            'current_academic_year' => $this->request->getGet('academic_year'),
            'current_semester' => $this->request->getGet('semester'),
            'current_class_id' => $this->request->getGet('class_id'),
        ];

        return view('wali_kelas/erapor/export_form', $data);
    }

    public function processExport()
    {
        $user = auth()->user();
        if (!$user || !has_role('Guru')) {
             return $this->response->setStatusCode(403)->setJSON(['error' => 'Akses ditolak.']);
        }
        $teacher = $this->teacherModel->where('user_id', $user->id)->first();
        if (!$teacher) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Data guru tidak valid.']);
        }

        $classId = $this->request->getPost('class_id');
        $academicYear = $this->request->getPost('academic_year');
        $semester = $this->request->getPost('semester');

        // Validasi input
        if (empty($classId) || empty($academicYear) || empty($semester)) {
            session()->setFlashdata('error', 'Kelas, Tahun Ajaran, dan Semester harus dipilih.');
            return redirect()->back()->withInput();
        }

        // Verifikasi bahwa wali kelas memang mengampu kelas yang dipilih
        $isWaliKelas = $this->classModel->where('id', $classId)->where('wali_kelas_id', $teacher['id'])->first();
        if (!$isWaliKelas) {
            session()->setFlashdata('error', 'Anda tidak memiliki hak akses untuk mengekspor data kelas ini.');
            return redirect()->back()->withInput();
        }

        $className = $isWaliKelas['class_name']; // Untuk nama file

        // Ambil data nilai sumatif yang sudah diolah (misal, rata-rata per mapel)
        // Ini memerlukan method baru di AssessmentModel
        $exportData = $this->assessmentModel->getExportDataForErapor($classId, $academicYear, $semester);

        if (empty($exportData['students']) || empty($exportData['subjects'])) {
            session()->setFlashdata('error', 'Tidak ada data nilai yang ditemukan untuk kriteria yang dipilih.');
            return redirect()->back()->withInput();
        }

        // Buat file Excel menggunakan PhpSpreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header Kolom - sesuaikan dengan format e-Rapor yang sebenarnya
        $header = ['NISN', 'NIS', 'Nama Siswa'];
        foreach ($exportData['subjects'] as $subject) {
            // Menggunakan subject_code dan subject_name untuk header
            // Sesuai AGENTS.md, e-Rapor mungkin memerlukan subject_code.
            // Format: KODE - NAMA (Sumatif)
            $header[] = esc($subject['subject_code'] ?? 'KODE_ERROR') . ' - ' . esc($subject['subject_name']) . " (Sumatif)";
        }
        $sheet->fromArray($header, NULL, 'A1');

        // Data Baris
        $rowIndex = 2;
        foreach ($exportData['students'] as $studentId => $student) {
            $rowData = [
                $student['nisn'] ?? '',
                $student['nis'] ?? '',
                $student['full_name'] ?? '',
            ];
            foreach ($exportData['subjects'] as $subject) {
                $rowData[] = $student['scores'][$subject['id']] ?? ''; // Skor sumatif akhir
            }
            $sheet->fromArray($rowData, NULL, 'A' . $rowIndex);
            $rowIndex++;
        }

        // Auto size kolom
        foreach (range('A', $sheet->getHighestDataColumn()) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Set filename
        $filename = 'eRapor_' . preg_replace('/[^A-Za-z0-9_.-]/', '_', $className) . '_' . $academicYear . '_Semester_' . $semester . '.xlsx';

        // Redirect output to a client’s web browser (Xlsx)
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit; // Penting untuk menghentikan eksekusi skrip setelah mengirim file
    }
}
