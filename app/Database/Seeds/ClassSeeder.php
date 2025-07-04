<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use App\Models\ClassModel;
// Import TeacherSeeder to access its static properties if needed for wali_kelas_id
use App\Database\Seeds\TeacherSeeder;

class ClassSeeder extends Seeder
{
    public static $classX_A_Id;
    public static $classXI_IPA1_Id;

    public function run()
    {
        $classModel = new ClassModel();

        // Ensure TeacherSeeder has run and its static properties are set
        // This assumes TeacherSeeder is called before ClassSeeder in DatabaseSeeder.php
        $waliKelas1Id = TeacherSeeder::$teacher1Id ?? null;
        $waliKelas2Id = TeacherSeeder::$teacher2Id ?? null;

        $classes = [
            [
                'class_name' => 'X-A',
                'academic_year' => '2024/2025',
                'wali_kelas_id' => $waliKelas1Id,
                'fase' => 'E'
            ],
            [
                'class_name' => 'XI-IPA-1',
                'academic_year' => '2024/2025',
                'wali_kelas_id' => $waliKelas2Id,
                'fase' => 'F'
            ],
            [
                'class_name' => 'XII-IPS-2',
                'academic_year' => '2024/2025',
                'wali_kelas_id' => null, // No specific wali kelas from seeder for this one
                'fase' => 'F'
            ],
        ];

        foreach ($classes as $classData) {
            // Check if class with the same name and academic year already exists
            $existingClass = $classModel->where('class_name', $classData['class_name'])
                                       ->where('academic_year', $classData['academic_year'])
                                       ->first();
            if ($existingClass) {
                // echo "Class {$classData['class_name']} ({$classData['academic_year']}) already exists. Fetching ID.\n";
                if ($classData['class_name'] === 'X-A') {
                    self::$classX_A_Id = $existingClass['id'];
                } elseif ($classData['class_name'] === 'XI-IPA-1') {
                    self::$classXI_IPA1_Id = $existingClass['id'];
                }
                continue;
            }

            if ($classId = $classModel->insert($classData)) {
                // echo "Class {$classData['class_name']} ({$classData['academic_year']}) created successfully.\n";
                if ($classData['class_name'] === 'X-A') {
                    self::$classX_A_Id = $classId;
                } elseif ($classData['class_name'] === 'XI-IPA-1') {
                    self::$classXI_IPA1_Id = $classId;
                }
            } else {
                // echo "Failed to create class {$classData['class_name']}. Errors: " . implode(', ', $classModel->errors()) . "\n";
            }
        }
    }
}
