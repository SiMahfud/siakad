<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\P5ProjectModel;
use App\Models\ClassModel;
use App\Models\P5DimensionModel;
use App\Models\StudentModel;
use App\Models\P5ProjectStudentModel;
use App\Models\P5ProjectTargetSubElementModel;
use App\Models\P5AssessmentModel;
use App\Models\P5SubElementModel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class P5ExportController extends BaseController
{
    protected $p5ProjectModel;
    protected $classModel;
    protected $p5DimensionModel;
    protected $studentModel;
    protected $p5ProjectStudentModel;
    protected $p5ProjectTargetSubElementModel;
    protected $p5AssessmentModel;
    protected $p5SubElementModel;
    protected $helpers = ['form', 'url', 'auth'];

    public function __construct()
    {
        $this->p5ProjectModel = new P5ProjectModel();
        $this->classModel = new ClassModel();
        $this->p5DimensionModel = new P5DimensionModel();
        $this->studentModel = new StudentModel();
        $this->p5ProjectStudentModel = new P5ProjectStudentModel();
        $this->p5ProjectTargetSubElementModel = new P5ProjectTargetSubElementModel();
        $this->p5AssessmentModel = new P5AssessmentModel();
        $this->p5SubElementModel = new P5SubElementModel();
    }

    public function exportForm()
    {
        if (!has_permission('manage_p5_projects')) { // Or a more specific export permission
            return redirect()->to('/unauthorized');
        }

        $data = [
            'title' => 'Ekspor Data P5 untuk e-Rapor',
            'projects' => $this->p5ProjectModel->orderBy('name', 'ASC')->findAll(),
            // Classes and Dimensions will be loaded via AJAX based on project selection
        ];

        return view('admin/p5export/export_form', $data);
    }

    // AJAX handler to get classes participating in a project
    public function getClassesForProject($projectId)
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403, 'Forbidden');
        }

        $classes = $this->p5ProjectStudentModel
            ->select('classes.id, classes.class_name')
            ->join('students', 'students.id = p5_project_students.student_id')
            ->join('class_student', 'class_student.student_id = students.id')
            ->join('classes', 'classes.id = class_student.class_id')
            ->where('p5_project_students.p5_project_id', $projectId)
            ->distinct()
            ->orderBy('classes.class_name', 'ASC')
            ->findAll();

        return $this->response->setJSON($classes);
    }

    // AJAX handler to get dimensions targeted in a project
    public function getDimensionsForProject($projectId)
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403, 'Forbidden');
        }

        $dimensions = $this->p5DimensionModel
            ->select('p5_dimensions.id, p5_dimensions.name')
            ->join('p5_elements', 'p5_elements.p5_dimension_id = p5_dimensions.id')
            ->join('p5_sub_elements', 'p5_sub_elements.p5_element_id = p5_elements.id')
            ->join('p5_project_target_sub_elements ptse', 'ptse.p5_sub_element_id = p5_sub_elements.id')
            ->where('ptse.p5_project_id', $projectId)
            ->distinct()
            ->orderBy('p5_dimensions.name', 'ASC')
            ->findAll();

        return $this->response->setJSON($dimensions);
    }

    public function processExport()
    {
        if (!has_permission('manage_p5_projects')) { // Or a more specific export permission
            return redirect()->to('/unauthorized');
        }

        $projectId = $this->request->getPost('project_id');
        $classId = $this->request->getPost('class_id');
        $dimensionId = $this->request->getPost('dimension_id');

        $validation = \Config\Services::validation();
        $validation->setRules([
            'project_id' => 'required|integer',
            'class_id' => 'required|integer',
            'dimension_id' => 'required|integer',
        ]);

        if (!$validation->withRequest($this->request)->run()) {
            return redirect()->back()->withInput()->with('errors', $validation->getErrors());
        }

        $project = $this->p5ProjectModel->find($projectId);
        $class = $this->classModel->find($classId);
        $dimension = $this->p5DimensionModel->find($dimensionId);

        if (!$project || !$class || !$dimension) {
            return redirect()->back()->with('error', 'Data Projek, Kelas, atau Dimensi tidak valid.');
        }

        // 1. Get students in the selected class AND project
        $students = $this->studentModel
            ->select('students.id, students.full_name, students.nisn, students.nis, p5_project_students.id as p5_project_student_id')
            ->join('class_student cs', 'cs.student_id = students.id')
            ->join('p5_project_students', 'p5_project_students.student_id = students.id')
            ->where('cs.class_id', $classId)
            ->where('p5_project_students.p5_project_id', $projectId)
            ->orderBy('students.full_name', 'ASC')
            ->findAll();

        if (empty($students)) {
            return redirect()->back()->with('error', 'Tidak ada siswa yang cocok dengan kriteria (Projek dan Kelas).');
        }

        // 2. Get target sub-elements for the project AND dimension
        $targetSubElements = $this->p5SubElementModel
            ->select('p5_sub_elements.id, p5_sub_elements.name')
            ->join('p5_elements', 'p5_elements.id = p5_sub_elements.p5_element_id')
            ->join('p5_project_target_sub_elements ptse', 'ptse.p5_sub_element_id = p5_sub_elements.id')
            ->where('ptse.p5_project_id', $projectId)
            ->where('p5_elements.p5_dimension_id', $dimensionId)
            ->orderBy('p5_sub_elements.id', 'ASC') // Order is important for column consistency
            ->findAll();

        if (empty($targetSubElements)) {
            return redirect()->back()->with('error', 'Tidak ada sub-elemen target untuk projek dan dimensi yang dipilih.');
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set Header Information
        $sheet->setCellValue('A1', 'FORMAT IMPORT NILAI PROJEK PROFIL PELAJAR PANCASILA');
        $sheet->mergeCells('A1:F1'); // Merge for title
        $sheet->setCellValue('A2', 'SEKOLAH');
        $sheet->setCellValue('B2', ': SMAN 1 CAMPURDARAT'); // Assuming this is fixed
        $sheet->setCellValue('A3', 'KD SEMESTER');
        $sheet->setCellValue('B3', ': ' . ($this->request->getPost('kd_semester') ?: date('Y').(date('m') <= 6 ? '1' : '2'))); // Example: 20242
        $sheet->setCellValue('A4', 'PROJEK P5');
        $sheet->setCellValue('B4', ': ' . $project['name']);
        $sheet->setCellValue('A5', 'KELOMPOK');
        $sheet->setCellValue('B5', ': ' . $class['class_name']);
        $sheet->setCellValue('A6', 'DIMENSI P3');
        $sheet->setCellValue('B6', ': ' . $dimension['name']);
        $sheet->setCellValue('A7', 'ID FORMAT');
        $sheet->setCellValue('B7', ': F_PS3BK'); // Fixed ID

        // Set Table Headers
        $sheet->setCellValue('A9', 'NO');
        $sheet->setCellValue('B9', 'NAMA SISWA');
        $sheet->setCellValue('C9', 'NISN');
        $sheet->setCellValue('D9', 'NIS');

        $sheet->setCellValue('E8', 'NILAI CAPAIAN PER SUB ELEMEN P3'); // Main header for sub-elements
        $startColumnSubElement = 'E';

        // Dynamically create sub-element columns
        $subElementHeaderRow = 9;
        $currentColumn = $startColumnSubElement;
        foreach ($targetSubElements as $subElement) {
            $sheet->setCellValue($currentColumn . $subElementHeaderRow, $subElement['name']);
            $currentColumn++;
        }
        // Merge the "NILAI CAPAIAN PER SUB ELEMEN P3" header if there are sub-elements
        if ($startColumnSubElement != $currentColumn) { // If at least one sub-element
            $endColumnSubElement = chr(ord($currentColumn) - 1); // Previous column
            $sheet->mergeCells($startColumnSubElement.'8:'.$endColumnSubElement.'8');
        }


        // Fill Data
        $rowNumber = 10;
        $no = 1;
        foreach ($students as $student) {
            $sheet->setCellValue('A' . $rowNumber, $no++);
            $sheet->setCellValue('B' . $rowNumber, $student['full_name']);
            $sheet->setCellValue('C' . $rowNumber, $student['nisn']);
            $sheet->setCellValue('D' . $rowNumber, $student['nis']);

            $currentColumn = $startColumnSubElement;
            foreach ($targetSubElements as $subElement) {
                $assessment = $this->p5AssessmentModel
                    ->where('p5_project_student_id', $student['p5_project_student_id'])
                    ->where('p5_sub_element_id', $subElement['id'])
                    ->first();

                $sheet->setCellValue($currentColumn . $rowNumber, $assessment ? $assessment['assessment_value'] : ''); // Output BB/MB/BSH/SB or empty
                $currentColumn++;
            }
            $rowNumber++;
        }

        // Auto size columns for better readability - Optional
        foreach (range('A', $sheet->getHighestDataColumn()) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $fileName = 'Export_P5_' . slug($project['name']) . '_' . slug($class['class_name']) . '_' . slug($dimension['name']) . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . urlencode($fileName) . '"');
        $writer->save('php://output');
        exit();
    }
}
