<?php

namespace App\Controllers\Siswa;

use App\Controllers\BaseController;
use App\Models\AssessmentModel;
use App\Models\StudentModel;
use App\Models\ClassModel;
use App\Models\SubjectModel;
use App\Models\ClassStudentModel;
use App\Models\TeacherClassSubjectAssignmentModel;

class NilaiController extends BaseController
{
    protected $assessmentModel;
    protected $studentModel;
    protected $classModel;
    protected $subjectModel;
    protected $classStudentModel;
    protected $teacherClassSubjectAssignmentModel;

    public function __construct()
    {
        helper(['auth', 'url']);

        if (!hasRole('Siswa')) {
            // This should ideally be caught by the route filter first.
            // Redirecting to a general unauthorized page or login.
            session()->setFlashdata('error', 'Anda tidak memiliki izin untuk mengakses halaman ini.');
            // The actual redirection will be handled by the filter or a subsequent check if needed.
            // For now, let it proceed, filter should catch it.
            // If directly accessed and filter somehow bypassed:
             if(!session()->get('is_logged_in')) return redirect()->to(site_url('login'));
             // else return redirect()->to(site_url('/')); // or some student dashboard
        }

        $this->assessmentModel = new AssessmentModel();
        $this->studentModel = new StudentModel();
        $this->classModel = new ClassModel();
        $this->subjectModel = new SubjectModel();
        $this->classStudentModel = new ClassStudentModel(); // Pivot table student-class
        $this->teacherClassSubjectAssignmentModel = new TeacherClassSubjectAssignmentModel();
    }

    public function index()
    {
        $loggedInUserId = current_user_id();
        $student = $this->studentModel->where('user_id', $loggedInUserId)->first();

        if (!$student) {
            session()->setFlashdata('error', 'Data siswa tidak ditemukan untuk akun Anda.');
            return redirect()->to('/'); // Redirect to a safe page, e.g., student dashboard or home
        }

        $studentId = $student['id'];

        // Get current class of the student
        $currentClassEnrollment = $this->classStudentModel->where('student_id', $studentId)->first();
        // TODO: Add logic for academic year if student can be in multiple classes over time.
        // For now, assume student is in one active class.

        if (!$currentClassEnrollment) {
            session()->setFlashdata('info', 'Anda saat ini tidak terdaftar di kelas manapun.');
            // Prepare data for view even if no class, so view can display message
            $data = [
                'pageTitle' => 'Transkrip Nilai Sementara',
                'student' => $student,
                'currentClass' => null,
                'mapelDenganNilai' => []
            ];
            return view('siswa/nilai/index', $data);
        }

        $classId = $currentClassEnrollment['class_id'];
        $currentClass = $this->classModel->find($classId);

        // Get subjects taught in that class
        // This could be all subjects assigned to the class, or specific to what the student takes (if KurMer phase F)
        // For now, assume all subjects assigned to any teacher in that class are relevant.
        $subjectsInClass = $this->teacherClassSubjectAssignmentModel->getDistinctSubjectsForClass($classId);

        if(empty($subjectsInClass) && $currentClass){ // If no assignments, maybe list all subjects as a fallback? Or show message.
            // Fallback: get all subjects if no specific assignments found for the class (less accurate)
            // $subjectsInClass = $this->subjectModel->orderBy('subject_name', 'ASC')->findAll();
            // For now, if no subjects are assigned to the class, it will show empty.
        }


        $mapelDenganNilai = [];
        foreach ($subjectsInClass as $subject) {
            $assessments = $this->assessmentModel
                ->where('student_id', $studentId)
                ->where('subject_id', $subject['subject_id'])
                ->where('class_id', $classId) // Ensure assessments are for the current class context
                ->orderBy('assessment_date', 'DESC')
                ->findAll();

            $mapelDenganNilai[] = [
                'subject_info' => $subject,
                'assessments'  => $assessments
            ];
        }

        $data = [
            'pageTitle'        => 'Transkrip Nilai Sementara',
            'student'          => $student,
            'currentClass'     => $currentClass,
            'mapelDenganNilai' => $mapelDenganNilai
        ];

        return view('siswa/nilai/index', $data);
    }
}
