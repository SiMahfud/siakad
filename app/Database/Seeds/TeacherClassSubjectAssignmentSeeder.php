<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use App\Models\TeacherClassSubjectAssignmentModel;
use App\Models\SubjectModel; // To fetch subject IDs if needed

// Import other seeders to access their static properties
use App\Database\Seeds\TeacherSeeder;
use App\Database\Seeds\ClassSeeder;


class TeacherClassSubjectAssignmentSeeder extends Seeder
{
    public function run()
    {
        $assignmentModel = new TeacherClassSubjectAssignmentModel();
        $subjectModel = new SubjectModel();

        // Fetch IDs from other seeders
        $teacher1Id = TeacherSeeder::$teacher1Id ?? null;
        $teacher2Id = TeacherSeeder::$teacher2Id ?? null;
        $classX_A_Id = ClassSeeder::$classX_A_Id ?? null;
        $classXI_IPA1_Id = ClassSeeder::$classXI_IPA1_Id ?? null;

        // Fetch some subject IDs (example: Matematika Wajib, Bahasa Indonesia, Fisika)
        $subjectMTKW = $subjectModel->where('subject_code', 'MTK-W')->first();
        $subjectIND = $subjectModel->where('subject_code', 'IND')->first();
        $subjectFIS = $subjectModel->where('subject_code', 'FIS')->first();
        $subjectKIM = $subjectModel->where('subject_code', 'KIM')->first();


        $assignments = [];

        // Guru 1 assignments
        if ($teacher1Id && $classX_A_Id && $subjectMTKW) {
            $assignments[] = ['teacher_id' => $teacher1Id, 'class_id' => $classX_A_Id, 'subject_id' => $subjectMTKW['id']];
        }
        if ($teacher1Id && $classX_A_Id && $subjectIND) {
            $assignments[] = ['teacher_id' => $teacher1Id, 'class_id' => $classX_A_Id, 'subject_id' => $subjectIND['id']];
        }
        if ($teacher1Id && $classXI_IPA1_Id && $subjectFIS) { // Guru 1 mengajar Fisika di XI IPA 1
            $assignments[] = ['teacher_id' => $teacher1Id, 'class_id' => $classXI_IPA1_Id, 'subject_id' => $subjectFIS['id']];
        }


        // Guru 2 assignments
        if ($teacher2Id && $classXI_IPA1_Id && $subjectKIM) {
            $assignments[] = ['teacher_id' => $teacher2Id, 'class_id' => $classXI_IPA1_Id, 'subject_id' => $subjectKIM['id']];
        }
        if ($teacher2Id && $classX_A_Id && $subjectIND) { // Guru 2 juga mengajar Bahasa Indonesia di X-A
            $assignments[] = ['teacher_id' => $teacher2Id, 'class_id' => $classX_A_Id, 'subject_id' => $subjectIND['id']];
        }


        foreach ($assignments as $assignmentData) {
            // Check if assignment already exists
            $existingAssignment = $assignmentModel->where([
                'teacher_id' => $assignmentData['teacher_id'],
                'class_id'   => $assignmentData['class_id'],
                'subject_id' => $assignmentData['subject_id']
            ])->first();

            if ($existingAssignment) {
                echo "Teacher assignment for T:{$assignmentData['teacher_id']} C:{$assignmentData['class_id']} S:{$assignmentData['subject_id']} already exists. Skipping.\n";
                continue;
            }

            if ($assignmentModel->insert($assignmentData)) {
                 echo "Assigned Teacher T:{$assignmentData['teacher_id']} to Class C:{$assignmentData['class_id']} for Subject S:{$assignmentData['subject_id']}.\n";
            } else {
                echo "Failed to create assignment for T:{$assignmentData['teacher_id']} C:{$assignmentData['class_id']} S:{$assignmentData['subject_id']}. Errors: " . implode(', ', $assignmentModel->errors()) . "\n";
            }
        }

        if (empty($assignments)) {
            echo "No teacher/class/subject IDs available from previous seeders/DB to create teacher-class-subject assignments.\n";
        }
    }
}
