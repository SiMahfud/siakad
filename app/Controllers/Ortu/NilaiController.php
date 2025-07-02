<?php

namespace App\Controllers\Ortu;

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

        if (!hasRole('Orang Tua')) {
            if(!session()->get('is_logged_in')) return redirect()->to(site_url('login'));
            // else return redirect()->to(site_url('/')); // or some parent dashboard
        }

        $this->assessmentModel = new AssessmentModel();
        $this->studentModel = new StudentModel(); // Will add findByParentUserId if not exists
        $this->classModel = new ClassModel();
        $this->subjectModel = new SubjectModel();
        $this->classStudentModel = new ClassStudentModel();
        $this->teacherClassSubjectAssignmentModel = new TeacherClassSubjectAssignmentModel();
    }

    public function index()
    {
        $loggedInParentUserId = current_user_id();
        $children = $this->studentModel->findByParentUserId($loggedInParentUserId);

        if (empty($children)) {
            session()->setFlashdata('info', 'Tidak ada data siswa yang terkait dengan akun Anda.');
            $data = [
                'pageTitle' => 'Pilih Siswa',
                'children' => []
            ];
            return view('ortu/nilai/select_student', $data);
        }

        if (count($children) === 1) {
            return redirect()->to(route_to('ortu_nilai_recap_siswa', $children[0]['id']));
        }

        $data = [
            'pageTitle' => 'Pilih Siswa untuk Melihat Nilai',
            'children'  => $children
        ];
        return view('ortu/nilai/select_student', $data);
    }

    public function showStudentRecap($studentId)
    {
        $loggedInParentUserId = current_user_id();
        $student = $this->studentModel->find($studentId);

        // Validate if the student belongs to the logged-in parent
        if (!$student || $student['parent_user_id'] != $loggedInParentUserId) {
            session()->setFlashdata('error', 'Anda tidak berhak mengakses data nilai siswa ini atau data siswa tidak ditemukan.');
            return redirect()->to(route_to('ortu_nilai_index'));
        }

        // Logic similar to Siswa/NilaiController::index()
        $currentClassEnrollment = $this->classStudentModel->where('student_id', $studentId)->first();

        if (!$currentClassEnrollment) {
            session()->setFlashdata('info', 'Siswa ini saat ini tidak terdaftar di kelas manapun.');
            $data = [
                'pageTitle' => 'Transkrip Nilai Sementara - ' . esc($student['full_name']),
                'student' => $student,
                'currentClass' => null,
                'mapelDenganNilai' => []
            ];
            return view('ortu/nilai/recap_display', $data);
        }

        $classId = $currentClassEnrollment['class_id'];
        $currentClass = $this->classModel->find($classId);

        $subjectsInClass = $this->teacherClassSubjectAssignmentModel->getDistinctSubjectsForClass($classId);

        $mapelDenganNilai = [];
        foreach ($subjectsInClass as $subject) {
            $assessments = $this->assessmentModel
                ->where('student_id', $studentId)
                ->where('subject_id', $subject['subject_id'])
                ->where('class_id', $classId)
                ->orderBy('assessment_date', 'DESC')
                ->findAll();

            $mapelDenganNilai[] = [
                'subject_info' => $subject,
                'assessments'  => $assessments
            ];
        }

        $data = [
            'pageTitle'        => 'Transkrip Nilai Sementara - ' . esc($student['full_name']),
            'student'          => $student,
            'currentClass'     => $currentClass,
            'mapelDenganNilai' => $mapelDenganNilai
        ];

        return view('ortu/nilai/recap_display', $data);
    }
}
