<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\ExamResult;
use App\Models\ExternalCertificate;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    /**
     * Display student result view by TIITVT registration number
     * Also handles certificate results
     */
    public function resultView($regNo)
    {
        // Convert _ back to / for database lookup
        $originalRegNo = str_replace('_', '/', $regNo);

        // First try to find a regular student
        $student = Student::with(['center', 'courses'])->where('tiitvt_reg_no', $originalRegNo)->first();

        if ($student) {
            // Handle regular student results
            $examResults = ExamResult::with(['exam.course', 'category'])
                ->where('student_id', $student->id)
                ->latest()
                ->get();

            if ($examResults->isEmpty()) {
                abort(404, 'No exam results found for this student');
            }

            $latestResult = $examResults->first();
            $totalExams = $examResults->count();
            $averagePercentage = $examResults->avg('percentage');
            $passedExams = $examResults->where('percentage', '>=', 50)->count();

            return view('student.result-view', compact(
                'student',
                'examResults',
                'latestResult',
                'totalExams',
                'averagePercentage',
                'passedExams'
            ));
        }

        // If no student found, try to find a certificate
        $certificate = ExternalCertificate::with('center')
            ->where('reg_no', $originalRegNo)
            ->first();

        if (!$certificate) {
            abort(404, 'Student or certificate not found');
        }

        // Handle certificate results
        $student = (object) [
            'tiitvt_reg_no' => $certificate->reg_no,
            'full_name' => $certificate->student_name,
            'center' => $certificate->center,
        ];

        $examResults = collect([
            (object) [
                'id' => $certificate->id,
                'percentage' => $certificate->percentage,
                'grade' => $certificate->grade,
                'course_name' => $certificate->course_name,
                'issued_on' => $certificate->issued_on,
                'created_at' => $certificate->created_at,
            ]
        ]);

        $latestResult = $examResults->first();
        $totalExams = $examResults->count();
        $averagePercentage = $examResults->avg('percentage');
        $passedExams = $examResults->where('percentage', '>=', 50)->count();

        return view('student.result-view', compact(
            'student',
            'examResults',
            'latestResult',
            'totalExams',
            'averagePercentage',
            'passedExams',
            'certificate'
        ));
    }
}
