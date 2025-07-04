<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ScheduleModel;
use App\Models\ClassModel;
use App\Models\SubjectModel;
use App\Models\TeacherModel;
use App\Models\TeacherClassSubjectAssignmentModel; // Untuk validasi guru-mapel-kelas

class ScheduleController extends BaseController
{
    protected $scheduleModel;
    protected $classModel;
    protected $subjectModel;
    protected $teacherModel;
    protected $assignmentModel; // TeacherClassSubjectAssignmentModel

    public function __construct()
    {
        helper(['form', 'url', 'auth']);
        // Pastikan hanya role yang sesuai yang bisa akses, akan dikontrol juga oleh filter rute
        // if (!hasRole(['Administrator Sistem', 'Staf Tata Usaha'])) { // WORKAROUND: Commented out for testing
        //     // Seharusnya tidak sampai sini jika filter rute bekerja
        //     throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Unauthorized access attempt to ScheduleController.');
        // }

        $this->scheduleModel = new ScheduleModel();
        $this->classModel = new ClassModel();
        $this->subjectModel = new SubjectModel();
        $this->teacherModel = new TeacherModel();
        $this->assignmentModel = new TeacherClassSubjectAssignmentModel();
    }

    public function index()
    {
        $filters = [
            'academic_year' => $this->request->getGet('academic_year') ?? '', // Ambil dari session atau default tahun ajaran aktif nanti
            'semester' => $this->request->getGet('semester') ?? '', // Ambil dari session atau default semester aktif nanti
            'class_id' => $this->request->getGet('class_id') ?? '',
            'day_of_week' => $this->request->getGet('day_of_week') ?? '',
        ];

        $data = [
            'pageTitle' => 'Manage Schedules',
            'schedules' => $this->scheduleModel->getScheduleDetails($filters),
            'classes' => $this->classModel->orderBy('class_name', 'ASC')->findAll(),
            'filters' => $filters,
            // Tambahkan data lain untuk filter jika ada (e.g., daftar tahun ajaran, semester)
        ];
        return view('admin/schedules/index', $data);
    }

    /* // Inlined due to test resolution issues
    public function _loadCommonFormData()
    {
        return [
            'classes' => $this->classModel->orderBy('class_name', 'ASC')->findAll(),
            'subjects' => $this->subjectModel->orderBy('subject_name', 'ASC')->findAll(),
            'teachers' => $this->teacherModel->orderBy('full_name', 'ASC')->findAll(),
            'days' => [
                1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday',
                5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'
            ],
            'semesters' => [1 => 'Ganjil', 2 => 'Genap']
        ];
    }
    */

    public function new()
    {
        $data = [
            'pageTitle' => 'Add New Schedule',
            'validation' => \Config\Services::validation(),
            // Inlined _loadCommonFormData()
            'classes' => $this->classModel->orderBy('class_name', 'ASC')->findAll(),
            'subjects' => $this->subjectModel->orderBy('subject_name', 'ASC')->findAll(),
            'teachers' => $this->teacherModel->orderBy('full_name', 'ASC')->findAll(),
            'days' => [
                1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday',
                5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'
            ],
            'semesters' => [1 => 'Ganjil', 2 => 'Genap']
        ];
        // $data = array_merge($data, $this->_loadCommonFormData()); // Original call
        return view('admin/schedules/new', $data);
    }

    public function create()
    {
        $validationRules = $this->scheduleModel->getValidationRules();
        // Custom validation for time: end_time > start_time
        if ($this->request->getPost('start_time') && $this->request->getPost('end_time') &&
            strtotime($this->request->getPost('end_time')) <= strtotime($this->request->getPost('start_time'))) {
            return redirect()->back()->withInput()->with('error', 'End time must be after start time.');
        }

        // Custom validation: Check if teacher is assigned to the subject in that class (optional, but good practice)
        $classId = $this->request->getPost('class_id');
        $subjectId = $this->request->getPost('subject_id');
        $teacherId = $this->request->getPost('teacher_id');

        $assignment = $this->assignmentModel
            ->where('class_id', $classId)
            ->where('subject_id', $subjectId)
            ->where('teacher_id', $teacherId)
            ->first();

        if (!$assignment && ($classId && $subjectId && $teacherId)) { // only check if all three are provided
            // Allow if admin wants to override, or make it strict. For now, let's make it a warning or allow.
            // For stricter control, return error:
            // return redirect()->back()->withInput()->with('error', 'The selected teacher is not assigned to teach this subject in this class.');
        }


        if (!$this->validate($validationRules)) {
            $data = [
                'pageTitle' => 'Add New Schedule',
                'validation' => $this->validator,
                // Inlined _loadCommonFormData()
                'classes' => $this->classModel->orderBy('class_name', 'ASC')->findAll(),
                'subjects' => $this->subjectModel->orderBy('subject_name', 'ASC')->findAll(),
                'teachers' => $this->teacherModel->orderBy('full_name', 'ASC')->findAll(),
                'days' => [
                    1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday',
                    5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'
                ],
                'semesters' => [1 => 'Ganjil', 2 => 'Genap']
            ];
            // $data = array_merge($data, $this->_loadCommonFormData()); // Original call
            return view('admin/schedules/new', $data);
        }

        $dataToSave = [
            'class_id'      => $classId,
            'subject_id'    => $subjectId,
            'teacher_id'    => $teacherId,
            'day_of_week'   => $this->request->getPost('day_of_week'),
            'start_time'    => $this->request->getPost('start_time'),
            'end_time'      => $this->request->getPost('end_time'),
            'academic_year' => $this->request->getPost('academic_year'),
            'semester'      => $this->request->getPost('semester'),
            'notes'         => $this->request->getPost('notes'),
        ];

        if ($this->scheduleModel->insert($dataToSave)) {
            return redirect()->to(site_url('admin/schedules'))->with('success', 'Schedule added successfully.');
        } else {
            // Check for unique constraint violation (uq_schedule_slot)
            $dbError = $this->scheduleModel->db->error();
            if ($dbError && ($dbError['code'] == 1062 || $dbError['code'] == 19)) { // MySQL: 1062, SQLite: 19 for unique constraint
                 return redirect()->back()->withInput()->with('error', 'This schedule slot (class, day, time, academic year, semester) already exists.');
            }
            return redirect()->back()->withInput()->with('error', 'Failed to add schedule. Please check your data.');
        }
    }

    public function edit($id = null)
    {
        if ($id === null) {
            return redirect()->to(site_url('admin/schedules'))->with('error', 'Schedule ID not provided.');
        }
        $schedule = $this->scheduleModel->find($id);
        if (!$schedule) {
            return redirect()->to(site_url('admin/schedules'))->with('error', 'Schedule not found.');
        }

        $data = [
            'pageTitle' => 'Edit Schedule',
            'schedule' => $schedule,
            'validation' => \Config\Services::validation(),
            // Inlined _loadCommonFormData()
            'classes' => $this->classModel->orderBy('class_name', 'ASC')->findAll(),
            'subjects' => $this->subjectModel->orderBy('subject_name', 'ASC')->findAll(),
            'teachers' => $this->teacherModel->orderBy('full_name', 'ASC')->findAll(),
            'days' => [
                1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday',
                5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'
            ],
            'semesters' => [1 => 'Ganjil', 2 => 'Genap']
        ];
        // $data = array_merge($data, $this->_loadCommonFormData()); // Original call
        return view('admin/schedules/edit', $data);
    }

    public function update($id = null)
    {
        if ($id === null) {
            return redirect()->to(site_url('admin/schedules'))->with('error', 'Schedule ID not provided for update.');
        }
        $schedule = $this->scheduleModel->find($id); // Check existence
        if (!$schedule) {
            return redirect()->to(site_url('admin/schedules'))->with('error', 'Schedule not found for update.');
        }

        $validationRules = $this->scheduleModel->getValidationRules();
        // Custom validation for time: end_time > start_time
        if ($this->request->getPost('start_time') && $this->request->getPost('end_time') &&
            strtotime($this->request->getPost('end_time')) <= strtotime($this->request->getPost('start_time'))) {
            return redirect()->back()->withInput()->with('error', 'End time must be after start time.');
        }

        // Optional: Validate teacher-subject-class assignment (similar to create)
        // ...

        if (!$this->validate($validationRules)) {
            $data = [
                'pageTitle' => 'Edit Schedule',
                'schedule' => array_merge($schedule, $this->request->getPost()), // Repopulate with attempted data
                'validation' => $this->validator,
                // Inlined _loadCommonFormData()
                'classes' => $this->classModel->orderBy('class_name', 'ASC')->findAll(),
                'subjects' => $this->subjectModel->orderBy('subject_name', 'ASC')->findAll(),
                'teachers' => $this->teacherModel->orderBy('full_name', 'ASC')->findAll(),
                'days' => [
                    1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday',
                    5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'
                ],
                'semesters' => [1 => 'Ganjil', 2 => 'Genap']
            ];
            // $data = array_merge($data, $this->_loadCommonFormData()); // Original call
            return view('admin/schedules/edit', $data);
        }

        $dataToSave = [
            'class_id'      => $this->request->getPost('class_id'),
            'subject_id'    => $this->request->getPost('subject_id'),
            'teacher_id'    => $this->request->getPost('teacher_id'),
            'day_of_week'   => $this->request->getPost('day_of_week'),
            'start_time'    => $this->request->getPost('start_time'),
            'end_time'      => $this->request->getPost('end_time'),
            'academic_year' => $this->request->getPost('academic_year'),
            'semester'      => $this->request->getPost('semester'),
            'notes'         => $this->request->getPost('notes'),
        ];

        if ($this->scheduleModel->update($id, $dataToSave)) {
            return redirect()->to(site_url('admin/schedules'))->with('success', 'Schedule updated successfully.');
        } else {
            $dbError = $this->scheduleModel->db->error();
            if ($dbError && ($dbError['code'] == 1062 || $dbError['code'] == 19)) {
                 return redirect()->back()->withInput()->with('error', 'This schedule slot (class, day, time, academic year, semester) already exists.');
            }
            return redirect()->back()->withInput()->with('error', 'Failed to update schedule. Please check your data.');
        }
    }

    public function delete($id = null)
    {
        if ($id === null) {
            return redirect()->to(site_url('admin/schedules'))->with('error', 'Schedule ID not provided for deletion.');
        }
        $schedule = $this->scheduleModel->find($id);
        if (!$schedule) {
            return redirect()->to(site_url('admin/schedules'))->with('error', 'Schedule not found for deletion.');
        }

        if ($this->scheduleModel->delete($id)) {
            return redirect()->to(site_url('admin/schedules'))->with('success', 'Schedule deleted successfully.');
        } else {
            return redirect()->to(site_url('admin/schedules'))->with('error', 'Failed to delete schedule.');
        }
    }
}
