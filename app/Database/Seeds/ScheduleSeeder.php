<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use App\Models\ScheduleModel;
use App\Database\Seeds\ClassSeeder;
use App\Database\Seeds\SubjectSeeder; // Not strictly needed for IDs if subjects are known
use App\Database\Seeds\TeacherSeeder;
use App\Models\SubjectModel; // To fetch subject IDs if needed

class ScheduleSeeder extends Seeder
{
    public static $schedule1Id;

    public function run()
    {
        $scheduleModel = new ScheduleModel();
        $subjectModel = new SubjectModel();

        // Get IDs from other seeders - ensure they have run
        $classId = ClassSeeder::$classX_A_Id ?? null;
        $teacherId = TeacherSeeder::$teacher1Id ?? null;

        // Fetch a subject ID (e.g., Matematika Wajib)
        $subject = $subjectModel->where('subject_code', 'MTK-W')->first();
        $subjectId = $subject['id'] ?? null;

        if (!$classId || !$teacherId || !$subjectId) {
            // echo "Prerequisite data (class, teacher, or subject) not found for ScheduleSeeder. Skipping.\n";
            return;
        }

        $schedules = [
            [
                'class_id'      => $classId,
                'subject_id'    => $subjectId,
                'teacher_id'    => $teacherId,
                'day_of_week'   => 1, // Monday
                'start_time'    => '07:00:00',
                'end_time'      => '08:30:00',
                'academic_year' => '2024/2025',
                'semester'      => 1,
                'notes'         => 'Jadwal Matematika Kelas X-A Senin Sesi 1',
            ],
            // Add more schedules as needed
        ];

        foreach ($schedules as $scheduleData) {
            // Check if similar schedule already exists (optional, depends on business logic)
            $existingSchedule = $scheduleModel->where([
                'class_id' => $scheduleData['class_id'],
                'subject_id' => $scheduleData['subject_id'],
                'teacher_id' => $scheduleData['teacher_id'],
                'day_of_week' => $scheduleData['day_of_week'],
                'start_time' => $scheduleData['start_time'],
                'academic_year' => $scheduleData['academic_year'],
                'semester' => $scheduleData['semester'],
            ])->first();

            if ($existingSchedule) {
                // echo "Schedule already exists. Fetching ID.\n";
                self::$schedule1Id = $existingSchedule['id']; // Assuming first one is schedule1
                continue;
            }

            if ($insertedId = $scheduleModel->insert($scheduleData)) {
                // echo "Schedule created successfully.\n";
                if (!self::$schedule1Id) { // Assign first created schedule's ID
                    self::$schedule1Id = $insertedId;
                }
            } else {
                // echo "Failed to create schedule. Errors: " . implode(', ', $scheduleModel->errors()) . "\n";
            }
        }
    }
}
