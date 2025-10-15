<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\ExamResult;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    /**
     * Display student result view by TIITVT registration number
     */
    public function resultView($regNo)
    {
        // Convert _ back to / for database lookup
        $originalRegNo = str_replace('_', '/', $regNo);

        // Find the student by TIITVT registration number
        $student = Student::with(['center', 'courses'])->where('tiitvt_reg_no', $originalRegNo)->first();

        if (!$student) {
            abort(404, 'Student not found');
        }

        // Get all exam results for this student
        $examResults = ExamResult::with(['exam.course', 'category'])
            ->where('student_id', $student->id)
            ->latest()
            ->get();

        if ($examResults->isEmpty()) {
            abort(404, 'No exam results found for this student');
        }

        // Get the latest exam result for summary
        $latestResult = $examResults->first();

        // Calculate overall statistics
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
}
