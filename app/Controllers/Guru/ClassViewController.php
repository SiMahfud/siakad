<?php

namespace App\Controllers\Guru;

use App\Controllers\BaseController;
use App\Models\ClassModel;
use App\Models\ClassStudentModel;
use App\Models\TeacherModel;
use App\Models\TeacherClassSubjectAssignmentModel;

class ClassViewController extends BaseController
{
    protected $classModel;
    protected $classStudentModel;
    protected $teacherModel;
    protected $teacherClassSubjectAssignmentModel;
    protected $loggedInTeacherId;

    public function __construct()
    {
        helper(['auth', 'url', 'form']);

        if (!hasRole(['Guru', 'Administrator Sistem'])) {
            // Prevent access if not Guru or Admin
            // Redirecting from constructor is tricky, better to handle in routes or individual methods if needed.
            // For now, rely on route filter. If direct access bypasses route filter, this is a fallback.
            // More robust would be to throw an exception.
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('You do not have permission to access this module.');
        }

        $this->classModel = new ClassModel();
        $this->classStudentModel = new ClassStudentModel();
        $this->teacherModel = new TeacherModel();
        $this->teacherClassSubjectAssignmentModel = new TeacherClassSubjectAssignmentModel();

        // Get logged-in teacher's ID
        $loggedInUserId = current_user_id();
        if ($loggedInUserId) {
            $teacher = $this->teacherModel->where('user_id', $loggedInUserId)->first();
            $this->loggedInTeacherId = $teacher ? $teacher['id'] : null;
        }
    }

    public function index()
    {
        if (!$this->loggedInTeacherId && !isAdmin()) {
            // This case should ideally be caught by the role check in constructor or route filter
            // or if an admin is logged in but not associated with a teacher record (pure admin)
            // For pure admin, they might see all classes or none, depending on desired logic.
            // For now, if not a teacher and not an admin with teacher privileges, deny.
             return redirect()->to('/')->with('error', 'Access denied. Teacher information not found.');
        }

        $data['pageTitle'] = 'My Classes';
        $teacherClasses = [];

        // If admin and no specific teacher ID, they might see all classes.
        // However, the context is "My Classes" for a teacher.
        // If an admin wants to see all classes, they use the admin section.
        // So, if loggedInTeacherId is null (e.g. pure admin accessing this URL), show no classes or redirect.
        // Let's assume for now that an admin accessing this page still needs a teacher context,
        // or this page is strictly for users with a teacher record.

        if ($this->loggedInTeacherId) {
            // Get classes where the teacher is a wali kelas
            $waliKelasClasses = $this->classModel
                ->where('wali_kelas_id', $this->loggedInTeacherId)
                ->orderBy('class_name', 'ASC')
                ->findAll();

            // Get classes where the teacher has teaching assignments
            $assignedClassesData = $this->teacherClassSubjectAssignmentModel
                ->select('classes.id, classes.class_name, classes.academic_year, classes.fase')
                ->join('classes', 'classes.id = teacher_class_subject_assignments.class_id')
                ->where('teacher_class_subject_assignments.teacher_id', $this->loggedInTeacherId)
                ->distinct()
                ->orderBy('classes.class_name', 'ASC')
                ->findAll();

            // Merge and make unique
            $allClasses = array_merge($waliKelasClasses, $assignedClassesData);
            $uniqueClasses = [];
            foreach ($allClasses as $class) {
                $uniqueClasses[$class['id']] = $class; // Use class ID as key to automatically handle uniqueness
            }
            $teacherClasses = array_values($uniqueClasses); // Re-index the array

            // Sort by class_name after merging (optional, if order from queries is not sufficient)
            usort($teacherClasses, function ($a, $b) {
                return strcmp($a['class_name'], $b['class_name']);
            });
        } elseif (isAdmin()) {
            // Optional: If an admin accesses this page, decide what to show.
            // For "My Classes", it implies a teacher context.
            // If admin wants to see all classes, they should use Admin controllers.
            // So, an admin without a teacher context (loggedInTeacherId is null) sees an empty list here.
            // Or, provide a message.
            session()->setFlashdata('info', 'Viewing as Admin. This page shows classes associated with a specific teacher. For all classes, please use Admin Management menu.');
        }


        $data['classes'] = $teacherClasses;
        return view('guru/classes/index', $data);
    }

    public function viewStudents($class_id = null)
    {
        if (!$this->loggedInTeacherId && !isAdmin()) {
            return redirect()->to('/')->with('error', 'Access denied. Teacher information not found.');
        }

        if ($class_id === null) {
            return redirect()->to(site_url('guru/my-classes'))->with('error', 'Class ID not provided.');
        }

        $class_id = (int)$class_id;
        $classInfo = $this->classModel->find($class_id);

        if (!$classInfo) {
            return redirect()->to(site_url('guru/my-classes'))->with('error', 'Class not found.');
        }

        // Authorization check:
        // Guru must be wali_kelas OR must have a teaching assignment in this class.
        // Admin with a teacher context can also view. Admin without teacher context (pure admin) can also view.
        $isWaliKelas = false;
        $hasTeachingAssignment = false;

        if ($this->loggedInTeacherId) {
            if ($classInfo['wali_kelas_id'] == $this->loggedInTeacherId) {
                $isWaliKelas = true;
            }

            $assignment = $this->teacherClassSubjectAssignmentModel
                ->where('teacher_id', $this->loggedInTeacherId)
                ->where('class_id', $class_id)
                ->first();
            if ($assignment) {
                $hasTeachingAssignment = true;
            }
        }

        // If not admin, and not wali kelas, and no teaching assignment for this teacher
        if (!isAdmin() && !$isWaliKelas && !$hasTeachingAssignment && $this->loggedInTeacherId) {
             return redirect()->to(site_url('guru/my-classes'))->with('error', 'You are not authorized to view students for this class.');
        }
        // If user is admin (even without teacher context), they are allowed.
        // If user is a teacher and authorized, they are allowed.

        $students = $this->classStudentModel->getStudentsInClass($class_id);

        $data = [
            'pageTitle' => 'Students in Class: ' . esc($classInfo['class_name']),
            'classInfo' => $classInfo,
            'students'  => $students,
        ];

        return view('guru/classes/view_students', $data);
    }

    public function mySchedule()
    {
        if (!$this->loggedInTeacherId && !isAdmin()) {
             return redirect()->to('/')->with('error', 'Access denied. Teacher information not found.');
        }

        $filters = [
            'academic_year' => $this->request->getGet('academic_year') ?? '', // Default ke tahun ajaran aktif jika ada
            'semester' => $this->request->getGet('semester') ?? '', // Default ke semester aktif jika ada
            'teacher_id' => null
        ];

        $schedules = [];
        if ($this->loggedInTeacherId) {
            $filters['teacher_id'] = $this->loggedInTeacherId;
            $schedules = $this->scheduleModel->getScheduleDetails($filters);
        } elseif (isAdmin()) {
            // Admin without teacher context accessing this page sees all schedules
            // or we can restrict this page only for actual teachers.
            // For now, let admin see all if they hit this URL directly, or filter by teacher if they provide teacher_id.
            // However, "My Schedule" implies context of logged in teacher.
            // So, if admin is not a teacher, they should probably not use "My Schedule".
            // Let's provide a message if admin is not a teacher.
            // Or, if we want admin to use this to see ANY teacher's schedule:
            if ($this->request->getGet('teacher_id_for_admin')) {
                 $filters['teacher_id'] = $this->request->getGet('teacher_id_for_admin');
                 $schedules = $this->scheduleModel->getScheduleDetails($filters);
            } else {
                session()->setFlashdata('info', 'As an Admin, you can view all schedules via Admin menu, or specify a teacher ID to view their schedule here.');
            }
        }


        $data = [
            'pageTitle' => 'My Teaching Schedule',
            'schedules' => $schedules,
            'filters' => $filters, // Pass filters to view for display or pre-filling filter form
            // Potentially load list of academic years and semesters for filter dropdowns
        ];

        // If an admin is also a teacher, $this->loggedInTeacherId will be set, and they'll see their own schedule.
        // If a pure admin (not a teacher) accesses, $this->loggedInTeacherId is null.
        // The logic above handles this: pure admin gets info message unless they provide a teacher_id_for_admin filter.

        return view('guru/schedules/my_schedule', $data);
    }
}
