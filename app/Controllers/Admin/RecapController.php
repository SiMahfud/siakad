<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AttendanceModel;
use App\Models\ClassModel;
use App\Models\StudentModel;
use App\Models\TeacherModel;
use App\Models\StudentSubjectChoiceModel;
use App\Models\SubjectOfferingModel;
use App\Models\SubjectModel;

class RecapController extends BaseController
{
    protected $attendanceModel;
    protected $classModel;
    protected $studentModel;
    protected $teacherModel;
    protected $studentSubjectChoiceModel;
    protected $subjectOfferingModel;
    protected $subjectModel;
    protected $dailyAttendanceModel; // Added

    public function __construct()
    {
        $this->attendanceModel = new AttendanceModel();
        $this->dailyAttendanceModel = new \App\Models\DailyAttendanceModel(); // Added
        $this->classModel = new ClassModel();
        $this->studentModel = new StudentModel();
        $this->teacherModel = new TeacherModel();
        $this->studentSubjectChoiceModel = new StudentSubjectChoiceModel();
        $this->subjectOfferingModel = new SubjectOfferingModel();
        $this->subjectModel = new SubjectModel();
        helper(['form', 'url', 'auth']);
    }

    private function getClassesForAttendanceRecap()
    {
        $user = auth()->user();
        $rawClasses = [];

        if (has_role('Administrator Sistem') || has_role('Staf Tata Usaha') || has_role('Kepala Sekolah')) {
            // Admin, Staff, Kepsek can see all classes
            $rawClasses = $this->classModel
                ->select('classes.*, teachers.full_name as wali_kelas_name')
                ->join('teachers', 'teachers.id = classes.wali_kelas_id', 'left')
                ->orderBy('classes.class_name', 'ASC')
                ->findAll();
        } elseif (has_role('Guru')) {
            $teacher = $this->teacherModel->where('user_id', $user->id)->first();
            if ($teacher) {
                // Wali kelas can see their own class(es)
                $rawClasses = $this->classModel
                    ->select('classes.*, teachers.full_name as wali_kelas_name')
                    ->join('teachers', 'teachers.id = classes.wali_kelas_id', 'left')
                    ->where('classes.wali_kelas_id', $teacher['id'])
                    ->orderBy('classes.class_name', 'ASC')
                    ->findAll();
            }
        }

        // Ensure unique classes if a teacher happens to be wali_kelas for multiple (though unlikely for this method's purpose)
        // Or if other roles might get duplicate listings from complex joins in future
        $classes = [];
        $classIds = [];
        foreach ($rawClasses as $cls) {
            if (!in_array($cls['id'], $classIds)) {
                $classes[] = $cls;
                $classIds[] = $cls['id'];
            }
        }
        return $classes;
    }


    public function attendance()
    {
        $data = [
            'title' => 'Rekapitulasi Presensi Siswa',
            'user_is_admin_or_staff' => has_role('Administrator Sistem') || has_role('Staf Tata Usaha')
        ];

        $availableClasses = $this->getClassesForAttendanceRecap();
        $data['available_classes'] = $availableClasses;

        $selectedClassId = $this->request->getGet('class_id');
        $dateFrom = $this->request->getGet('date_from') ?? date('Y-m-01'); // Default to first day of current month
        $dateTo = $this->request->getGet('date_to') ?? date('Y-m-t'); // Default to last day of current month
        $statusFilter = $this->request->getGet('status') ?? 'ALL'; // Default to all statuses

        $data['selected_class_id'] = $selectedClassId;
        $data['date_from'] = $dateFrom;
        $data['date_to'] = $dateTo;
        $data['selected_status'] = $statusFilter;
        $data['recap_data'] = [];
        $data['daily_summary_for_visuals'] = []; // For per-hour attendance visuals
        $data['daily_general_attendance_summary'] = []; // For daily general attendance summary
        $data['status_map'] = \App\Models\AttendanceModel::getStatusMap(); // Pass status map to view for filter dropdown

        // Security check: if user is Wali Kelas, ensure selected_class_id is one of their classes
        if (has_role('Guru') && !(has_role('Administrator Sistem') || has_role('Staf Tata Usaha') || has_role('Kepala Sekolah'))) {
            $isOwnClass = false;
            foreach ($availableClasses as $cls) {
                if ($cls['id'] == $selectedClassId) {
                    $isOwnClass = true;
                    break;
                }
            }
            if (!$selectedClassId || !$isOwnClass) {
                // If a Wali Kelas tries to access a class not their own, or no class is selected,
                // and they only have one class, auto-select it. Otherwise, show no data or an error.
                if (count($availableClasses) === 1 && $availableClasses[0]['id']) {
                    $selectedClassId = $availableClasses[0]['id'];
                    $data['selected_class_id'] =  $selectedClassId;
                } else if (count($availableClasses) > 1 && !$selectedClassId) {
                     $data['message'] = "Silakan pilih kelas terlebih dahulu.";
                     return view('admin/recaps/attendance_recap', $data);
                } else if ($selectedClassId && !$isOwnClass) {
                    session()->setFlashdata('error', 'Anda tidak memiliki akses ke kelas tersebut.');
                    return view('admin/recaps/attendance_recap', $data);
                }
            }
        }


        if ($selectedClassId && $dateFrom && $dateTo) {
            $filters = [
                'class_id' => $selectedClassId,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                // 'status' => $statusFilter, // The model's getAttendanceRecap currently ignores this for the main summary.
                                          // Status filter might be more for "who was alfa" rather than general HISA recap.
                                          // Or it can be applied to the final $recapData array if needed.
            ];
            $recapData = $this->attendanceModel->getAttendanceRecap($filters);
            $data['recap_data'] = $recapData;

            // Fetch daily summary for visuals (calendar and line chart) - Per Hour/Schedule Based
            if ($selectedClassId) { // Visuals are per class
                 $data['daily_summary_for_visuals'] = $this->attendanceModel->getDailyAttendanceSummaryForClass($filters);

                 // Fetch general daily attendance summary for the selected class and date range
                 $dailyGeneralAttendanceRaw = $this->dailyAttendanceModel
                    ->select('attendance_date, status, COUNT(id) as count')
                    ->where('class_id', $selectedClassId)
                    ->where('attendance_date >=', $dateFrom)
                    ->where('attendance_date <=', $dateTo)
                    ->groupBy('attendance_date, status')
                    ->findAll();

                $processedDailyGeneral = [];
                foreach($dailyGeneralAttendanceRaw as $row){
                    if(!isset($processedDailyGeneral[$row['attendance_date']])){
                        $processedDailyGeneral[$row['attendance_date']] = ['H'=>0, 'S'=>0, 'I'=>0, 'A'=>0];
                    }
                    $statusChar = \App\Models\DailyAttendanceModel::getStatusCharMap()[$row['status']] ?? '?';
                    $processedDailyGeneral[$row['attendance_date']][$statusChar] = $row['count'];
                }
                $data['daily_general_attendance_summary'] = $processedDailyGeneral;
            }

        } elseif ((has_role('Administrator Sistem') || has_role('Staf Tata Usaha') || has_role('Kepala Sekolah')) && !$selectedClassId && $dateFrom && $dateTo) {
            // Admin/Staff/Kepsek: if no class selected, show all students from all classes for the date range
            // Visuals (calendar/chart for per-hour) are disabled in this mode as they are per-class.
            // General daily attendance summary is also not shown in "all classes" mode to avoid complexity.
            $filters = [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ];
            $recapData = $this->attendanceModel->getAttendanceRecap($filters);
            $data['recap_data'] = $recapData;
            $data['daily_summary_for_visuals'] = [];
            $data['daily_general_attendance_summary'] = [];
        }


        return view('admin/recaps/attendance_recap', $data);
    }

    public function subjectChoices()
    {
        $data = [
            'title' => 'Rekapitulasi Pilihan Mata Pelajaran Siswa',
            'user_is_admin_or_staff' => has_role('Administrator Sistem') || has_role('Staf Tata Usaha')
        ];

        // Filters
        $academicYear = $this->request->getGet('academic_year');
        $semester = $this->request->getGet('semester');
        $subjectId = $this->request->getGet('subject_id');
        $includeStudentNames = $this->request->getGet('include_student_names') === '1';

        // Get distinct academic years and semesters from subject_offerings for filter dropdowns
        $data['academic_years'] = $this->subjectOfferingModel->distinct()->select('academic_year')->orderBy('academic_year', 'DESC')->findAll();
        $data['semesters'] = $this->subjectOfferingModel->distinct()->select('semester')->orderBy('semester', 'ASC')->findAll();
        $data['subjects'] = $this->subjectModel->orderBy('subject_name', 'ASC')->findAll();


        $data['selected_academic_year'] = $academicYear;
        $data['selected_semester'] = $semester;
        $data['selected_subject_id'] = $subjectId;
        $data['include_student_names'] = $includeStudentNames;
        $data['recap_data'] = [];

        // Only fetch recap data if at least academic year and semester are provided, or if no filters are set (show all active by default)
        // For a more focused recap, we might want to enforce selection of academic_year and semester.
        // Let's assume for now that if they are not set, we might show for the most recent or all active.
        // The model's getSubjectChoiceRecap defaults to active offerings.

        $recapFilters = [];
        if ($academicYear) {
            $recapFilters['academic_year'] = $academicYear;
        }
        if ($semester) {
            $recapFilters['semester'] = $semester;
        }
        if ($subjectId) {
            $recapFilters['subject_id'] = $subjectId;
        }
        $recapFilters['include_student_names'] = $includeStudentNames;
        // Default to active offerings, can be overridden if a filter for is_active is added
        // $recapFilters['is_active'] = 1;


        // Fetch data if filters are present, or on initial load (model handles default to active)
        // Let's require academic_year and semester for a meaningful recap for now.
        // if ($academicYear && $semester) {
        //     $data['recap_data'] = $this->studentSubjectChoiceModel->getSubjectChoiceRecap($recapFilters);
        // } elseif (!$academicYear && !$semester && !$subjectId) {
        //     // Initial load without filters, perhaps show for the latest academic year/semester by default
        //     // Or show all active offerings (current behavior of model if no year/semester specified)
        //     $data['recap_data'] = $this->studentSubjectChoiceModel->getSubjectChoiceRecap($recapFilters);
        // }

        // Forcing academic_year and semester to be selected for now to avoid overly broad initial query
        if ($academicYear && $semester) {
             $data['recap_data'] = $this->studentSubjectChoiceModel->getSubjectChoiceRecap($recapFilters);
        } else if (!$academicYear && !$semester && !$subjectId && !$this->request->getGetPost()) {
            // If it's an initial GET request without any parameters, do not load data yet.
            $data['message'] = "Silakan pilih Tahun Ajaran dan Semester untuk melihat rekapitulasi.";
        } else if ($this->request->getGetPost() && (!$academicYear || !$semester)) {
            // If form submitted but year or semester missing
             $data['message'] = "Tahun Ajaran dan Semester harus dipilih.";
        } else {
            // Fallback if only subject_id is chosen without year/semester
            $data['recap_data'] = $this->studentSubjectChoiceModel->getSubjectChoiceRecap($recapFilters);
        }


        return view('admin/recaps/subject_choice_recap', $data);
    }
}
