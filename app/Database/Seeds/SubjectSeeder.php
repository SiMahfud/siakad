<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use App\Models\SubjectModel;

class SubjectSeeder extends Seeder
{
    public function run()
    {
        $subjectModel = new SubjectModel();

        $subjects = [
            ['subject_code' => 'MTK-W', 'subject_name' => 'Matematika (Wajib)', 'is_pilihan' => 0],
            ['subject_code' => 'IND', 'subject_name' => 'Bahasa Indonesia', 'is_pilihan' => 0],
            ['subject_code' => 'ENG', 'subject_name' => 'Bahasa Inggris', 'is_pilihan' => 0],
            ['subject_code' => 'FIS', 'subject_name' => 'Fisika', 'is_pilihan' => 1],
            ['subject_code' => 'KIM', 'subject_name' => 'Kimia', 'is_pilihan' => 1],
            ['subject_code' => 'BIO', 'subject_name' => 'Biologi', 'is_pilihan' => 1],
            ['subject_code' => 'EKO', 'subject_name' => 'Ekonomi', 'is_pilihan' => 1],
            ['subject_code' => 'GEO', 'subject_name' => 'Geografi', 'is_pilihan' => 1],
            ['subject_code' => 'SOS', 'subject_name' => 'Sosiologi', 'is_pilihan' => 1],
            ['subject_code' => 'SJR', 'subject_name' => 'Sejarah Indonesia', 'is_pilihan' => 0],
            ['subject_code' => 'PKN', 'subject_name' => 'Pendidikan Pancasila dan Kewarganegaraan', 'is_pilihan' => 0],
            ['subject_code' => 'PAI', 'subject_name' => 'Pendidikan Agama Islam', 'is_pilihan' => 0],
            // Add more subjects as needed
        ];

        foreach ($subjects as $subjectData) {
            // Check if subject with the same code already exists
            if ($subjectModel->where('subject_code', $subjectData['subject_code'])->first()) {
                echo "Subject with code {$subjectData['subject_code']} already exists. Skipping.\n";
                continue;
            }

            if ($subjectModel->insert($subjectData)) {
                echo "Subject {$subjectData['subject_name']} created successfully.\n";
            } else {
                echo "Failed to create subject {$subjectData['subject_name']}. Errors: " . implode(', ', $subjectModel->errors()) . "\n";
            }
        }
    }
}
