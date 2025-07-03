<?php

namespace App\Controllers\KepalaSekolah;

use App\Controllers\BaseController;
use App\Models\StudentModel;
use App\Models\TeacherModel;
use App\Models\ClassModel;
use App\Models\P5ProjectModel;
use App\Models\AttendanceModel; // Untuk rekap kehadiran

class DashboardController extends BaseController
{
    protected $helpers = ['auth', 'setting']; // Pastikan setting helper di-load

    public function index()
    {
        if (!has_role('Kepala Sekolah')) {
            // Meskipun rute mungkin sudah difilter, double check di controller itu baik.
            return redirect()->to('/unauthorized-access');
        }

        $studentModel = new StudentModel();
        $teacherModel = new TeacherModel();
        $classModel = new ClassModel();
        $p5ProjectModel = new P5ProjectModel();
        $attendanceModel = new AttendanceModel();

        $summaryData = [
            'total_students' => $studentModel->countAllResults(),
            'total_teachers' => $teacherModel->countAllResults(),
            'total_classes' => $classModel->countAllResults(),
            'active_p5_projects' => $p5ProjectModel
                                        ->where('start_date <=', date('Y-m-d'))
                                        ->where('end_date >=', date('Y-m-d'))
                                        ->countAllResults(),
        ];

        // Kalkulasi rata-rata kehadiran bulan ini (contoh sederhana)
        // Ini bisa menjadi query yang berat jika data attendance sangat besar.
        // Pertimbangkan untuk membuat rekapitulasi periodik atau menggunakan data yang sudah diagregasi.
        $currentMonth = date('Y-m');
        $firstDayOfMonth = $currentMonth . '-01';
        $lastDayOfMonth = date('Y-m-t', strtotime($firstDayOfMonth));

        $totalAttendanceRecords = $attendanceModel
            ->where('date >=', $firstDayOfMonth)
            ->where('date <=', $lastDayOfMonth)
            ->countAllResults();

        $totalPresentRecords = $attendanceModel
            ->where('date >=', $firstDayOfMonth)
            ->where('date <=', $lastDayOfMonth)
            ->where('status', 'H') // Asumsi 'H' untuk Hadir
            ->countAllResults();

        // Menghindari pembagian dengan nol
        if ($totalAttendanceRecords > 0) {
            $summaryData['monthly_attendance_percentage'] = round(($totalPresentRecords / $totalAttendanceRecords) * 100, 2);
        } else {
            $summaryData['monthly_attendance_percentage'] = 0; // Atau 'N/A'
        }


        $data = [
            'title' => 'Dasbor Kepala Sekolah',
            'summary' => $summaryData,
            'school_name' => get_setting('school_name', 'Nama Sekolah Belum Diatur'),
        ];

        return view('kepala_sekolah/dashboard/index', $data);
    }
}
