<?php

namespace App\Http\Controllers;

use App\Models\ExternalCertificate;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    /**
     * Display certificate result view by registration number
     */
    public function resultView($regNo)
    {
        // Convert _ back to / for database lookup
        $originalRegNo = str_replace('_', '/', $regNo);

        // Find the certificate by registration number
        $certificate = ExternalCertificate::with('center')
            ->where('reg_no', $originalRegNo)
            ->first();

        if (!$certificate) {
            abort(404, 'Certificate not found');
        }

        // Create a student-like object for compatibility with the view
        $student = (object) [
            'tiitvt_reg_no' => $certificate->reg_no,
            'full_name' => $certificate->student_name,
            'center' => $certificate->center,
        ];

        // Create exam results-like structure for compatibility
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
