<?php

namespace App\Http\Controllers;

use App\Models\ExternalCertificate;
use App\Models\Student;
use App\Models\ExamResult;
use App\Services\StudentQRService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CertificateController extends Controller
{
    protected StudentQRService $studentQRService;

    public function __construct(StudentQRService $studentQRService)
    {
        $this->studentQRService = $studentQRService;
    }

    /**
     * Display external certificate in modern format
     */
    public function display($id)
    {
        $certificate = ExternalCertificate::with('center')->findOrFail($id);

        // Generate QR code data URI if not exists
        $qrDataUri = null;
        if ($certificate->qr_code_path && Storage::disk('public')->exists($certificate->qr_code_path)) {
            $contents = Storage::disk('public')->get($certificate->qr_code_path);
            $qrDataUri = 'data:image/png;base64,' . base64_encode($contents);
        }

        return view('certificates.modern-display', compact('certificate', 'qrDataUri'));
    }

    /**
     * Preview certificate by TIITVT registration number (auto certificate)
     */
    public function preview($regNo)
    {
        // Convert _ back to / for database lookup
        $originalRegNo = str_replace('_', '/', $regNo);

        // Find the student by TIITVT registration number
        $student = Student::where('tiitvt_reg_no', $originalRegNo)->first();

        if (!$student) {
            abort(404, 'Student not found');
        }

        // Get all exam results for this student grouped by exam
        $examResults = ExamResult::with(['exam.course', 'student', 'category'])
            ->where('student_id', $student->id)
            ->get()
            ->groupBy('exam_id');

        if ($examResults->isEmpty()) {
            abort(404, 'No exam result found for this student');
        }

        // Get the latest exam (most recent)
        $latestExamId = $examResults->keys()->max();
        $latestExamResults = $examResults[$latestExamId];
        $latestExamResult = $latestExamResults->first();

        // Generate QR code for the student using StudentQRService
        $qrDataUri = null;

        // Get or create student QR code
        $studentQR = $student->qrCode;
        if (!$studentQR) {
            $studentQR = $this->studentQRService->generateStudentQR($student);
        }

        // Generate QR code data URI
        $qrDataUri = $this->studentQRService->generateQRCodeDataUri($studentQR->qr_data);

        // Calculate overall statistics from all categories
        $totalMarks = $latestExamResults->sum('total_points') ?: $latestExamResults->count() * 100;
        $totalMarksObtained = $latestExamResults->sum('points_earned') ?: $latestExamResults->sum('score');
        $overallPercentage = $totalMarks > 0 ? ($totalMarksObtained / $totalMarks) * 100 : 0;

        // Create subjects array from categories
        $subjects = $latestExamResults->map(function ($result) {
            $maxMarks = $result->total_points ?: 100;
            $obtainedMarks = $result->points_earned ?: $result->score;
            $percentage = $maxMarks > 0 ? ($obtainedMarks / $maxMarks) * 100 : 0;

            return [
                'name' => $result->category->name ?? 'Subject',
                'maximum' => $maxMarks,
                'obtained' => round($obtainedMarks),
                'result' => $percentage >= 50 ? 'PASS' : 'FAIL'
            ];
        })->values()->toArray();

        // Create certificate data from exam results
        $certificate = (object) [
            'reg_no' => $student->tiitvt_reg_no,
            'student_name' => $student->first_name . ($student->fathers_name ? ' ' . $student->fathers_name : '') . ($student->surname ? ' ' . $student->surname : ''),
            'course_name' => $latestExamResult->exam->course->name,
            'percentage' => round($overallPercentage, 2),
            'grade' => $this->calculateGrade($overallPercentage),
            'issued_on' => $latestExamResult->submitted_at ?? now(),
            'center_name' => $student->center->name ?? 'TIITVT',
            'data' => [
                'subjects' => $subjects,
                'total_marks' => $totalMarks,
                'total_marks_obtained' => round($totalMarksObtained),
                'total_result' => $overallPercentage >= 50 ? 'PASS' : 'FAIL'
            ]
        ];

        return view('certificates.exam-result-preview', compact('certificate', 'qrDataUri'));
    }

    /**
     * Calculate grade based on percentage
     */
    private function calculateGrade($percentage)
    {
        if ($percentage >= 90) return 'A+';
        if ($percentage >= 80) return 'A';
        if ($percentage >= 70) return 'B';
        if ($percentage >= 60) return 'C';
        if ($percentage >= 50) return 'D';
        return 'F';
    }
}
