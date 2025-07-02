<?php

namespace App\Controllers\Siswa;

use App\Controllers\BaseController;
use App\Models\ScheduleModel;
use App\Models\StudentModel;
use App\Models\ClassStudentModel;
use App\Models\ClassModel; // To get class name

class ScheduleController extends BaseController
{
    protected $scheduleModel;
    protected $studentModel;
    protected $classStudentModel;
    protected $classModel;
    protected $loggedInStudentId;
    protected $loggedInUserCurrentClassId;

    public function __construct()
    {
        helper(['auth', 'url']);
        if (!hasRole(['Siswa'])) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Unauthorized access attempt to Siswa ScheduleController.');
        }

        $this->scheduleModel = new ScheduleModel();
        $this->studentModel = new StudentModel();
        $this->classStudentModel = new ClassStudentModel();
        $this->classModel = new ClassModel();

        $loggedInUserId = current_user_id();
        if ($loggedInUserId) {
            $student = $this->studentModel->where('user_id', $loggedInUserId)->first();
            if ($student) {
                $this->loggedInStudentId = $student['id'];
                // Get current class of the student
                // Assuming a student belongs to one class per active period.
                // This might need adjustment if a student can be in multiple classes or history is kept in class_student
                $classMembership = $this->classStudentModel
                                        ->where('student_id', $this->loggedInStudentId)
                                        // Potentially add filter for current academic year/semester if class_student stores history
                                        // For now, gets the first class found.
                                        ->join('classes', 'classes.id = class_student.class_id')
                                        ->select('classes.id as class_id, classes.class_name')
                                        // Ideally, filter by current academic year for the class
                                        // ->where('classes.academic_year', $currentAcademicYear)
                                        ->first();
                if ($classMembership) {
                    $this->loggedInUserCurrentClassId = $classMembership['class_id'];
                }
            }
        }
    }

    public function classSchedule()
    {
        if (!$this->loggedInStudentId) {
            return redirect()->to('/')->with('error', 'Student data not found.');
        }
        if (!$this->loggedInUserCurrentClassId) {
            return redirect()->to('/')->with('error', 'You are not currently assigned to any class.');
        }

        $filters = [
            'academic_year' => $this->request->getGet('academic_year') ?? '', // Default to current academic year
            'semester' => $this->request->getGet('semester') ?? '', // Default to current semester
            'class_id' => $this->loggedInUserCurrentClassId
        ];

        $schedules = $this->scheduleModel->getScheduleDetails($filters);
        $currentClass = $this->classModel->find($this->loggedInUserCurrentClassId);

        $data = [
            'pageTitle' => 'My Class Schedule' . ($currentClass ? ' - ' . esc($currentClass['class_name']) : ''),
            'schedules' => $schedules,
            'filters' => $filters,
            'className' => $currentClass ? $currentClass['class_name'] : 'N/A'
        ];

        return view('siswa/schedules/class_schedule', $data);
    }
}
