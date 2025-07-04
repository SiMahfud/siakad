<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use App\Models\ClassStudentModel; // Assuming you create this model
// Import other seeders to access their static properties
use App\Database\Seeds\StudentSeeder;
use App\Database\Seeds\ClassSeeder;

class ClassStudentSeeder extends Seeder
{
    public function run()
    {
        // It's good practice to have a model for pivot tables too, even if simple.
        // If you don't have ClassStudentModel, you can use db builder: $this->db->table('class_student')->insert($data);
        $classStudentModel = new \App\Models\ClassStudentModel(); // Create this model if it doesn't exist.
                                                                 // Or use query builder directly for simplicity if model is not created.

        $student1Id = StudentSeeder::$student1Id ?? null;
        $classX_A_Id = ClassSeeder::$classX_A_Id ?? null;
        // You can add more students and classes here if your StudentSeeder and ClassSeeder create more.

        $assignments = [];

        if ($student1Id && $classX_A_Id) {
            $assignments[] = ['student_id' => $student1Id, 'class_id' => $classX_A_Id];
        }
        // Example: if you had student2Id and classXI_IPA1_Id
        // $student2Id = StudentSeeder::$student2Id ?? null;
        // $classXI_IPA1_Id = ClassSeeder::$classXI_IPA1_Id ?? null;
        // if ($student2Id && $classXI_IPA1_Id) {
        //     $assignments[] = ['student_id' => $student2Id, 'class_id' => $classXI_IPA1_Id];
        // }


        foreach ($assignments as $assignmentData) {
            // Check if assignment already exists
            $existingAssignment = $this->db->table('class_student')
                                        ->where('student_id', $assignmentData['student_id'])
                                        ->where('class_id', $assignmentData['class_id'])
                                        ->get()->getRowArray();

            if ($existingAssignment) {
                // echo "Student {$assignmentData['student_id']} is already in class {$assignmentData['class_id']}. Skipping.\n";
                continue;
            }

            // Using Query Builder directly as ClassStudentModel might not have complex logic
            // and to avoid issues if the model doesn't exist or isn't configured for insertBatch.
            if ($this->db->table('class_student')->insert($assignmentData)) {
                 // echo "Assigned student {$assignmentData['student_id']} to class {$assignmentData['class_id']}.\n";
            } else {
                // $errors = $classStudentModel->errors(); // If using model
                // For DB builder, error handling might be different or rely on DB exceptions.
                // echo "Failed to assign student {$assignmentData['student_id']} to class {$assignmentData['class_id']}.\n";
            }
        }

        if (empty($assignments)) {
            // echo "No student/class IDs available from previous seeders to create class-student assignments.\n";
        }
    }
}
